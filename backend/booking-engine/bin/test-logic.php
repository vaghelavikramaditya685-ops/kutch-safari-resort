<?php
/* ===========================================================================
 *  Logic and money checks, with made-up bookings (docs/09 "How to test").
 *
 *      php bin/test-logic.php
 *
 *  Every rule that decides money or a booking's state, run against synthetic
 *  bookings: cancellation days and charges, what is payable, refunds, payments
 *  after a cancellation, no-shows, UPI waits, the receipt, Stayflexi replies,
 *  setup --reset, and a few hundred random carts whose sums must add up.
 *
 *  Works on a THROWAWAY COPY of the SQLite database in the system temp folder,
 *  deleted at the end; the real database is never opened for writing.
 *  The race tests (many requests at the same moment) are bin/test-concurrency.php.
 * ======================================================================== */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../lib/db.php';
if (cfg('db.driver') !== 'sqlite' || !is_file((string) cfg('db.sqlite_path'))) {
    exit("Run this on a development PC with the SQLite database (config.local.php → db.driver = sqlite).\n");
}
$copy = tempnam(sys_get_temp_dir(), 'ksr-logic-') . '.sqlite';
copy((string) cfg('db.sqlite_path'), $copy);
register_shutdown_function(function () use ($copy) {
    foreach ([$copy, "$copy-wal", "$copy-shm", preg_replace('/\.sqlite$/', '', $copy)] as $f) @unlink($f);
});
$GLOBALS['CONFIG']['db']['sqlite_path'] = $copy;
$GLOBALS['CONFIG']['rules']['guest_details_optional'] = true;
$GLOBALS['CONFIG']['mail']['enabled'] = false;
$GLOBALS['CONFIG']['test_payments']['enabled'] = true;
$GLOBALS['CONFIG']['upi'] = ['enabled' => true, 'vpa' => 'test@upi', 'payee_name' => 'Test', 'hold_minutes' => 45, 'confirm_hours' => 48];
$GLOBALS['CONFIG']['stayflexi']['enabled'] = false;
$GLOBALS['CONFIG']['razorpay']['enabled'] = false;
require_once __DIR__ . '/../lib/booking.php';
require_once __DIR__ . '/../lib/payment.php';
require_once __DIR__ . '/../lib/documents.php';
foreach (['booking_rooms', 'booking_addons', 'payments', 'holds', 'bookings', 'rates', 'inventory', 'coupons'] as $t) db()->exec("DELETE FROM $t");

$pass = 0; $fail = 0;
$check = function ($ok, string $what, string $detail = '') use (&$pass, &$fail) {
    $ok ? $pass++ : $fail++;
    echo ($ok ? 'PASS ' : 'FAIL ') . $what . ($detail !== '' ? " — $detail" : '') . "\n";
};
$day = fn(int $n) => date('Y-m-d', strtotime(($n >= 0 ? '+' : '') . $n . ' days'));
$K = ['room_type_id' => 1, 'rate_plan_id' => 1, 'rooms' => 1];   // Kutchi AC Cottage, ₹7,450 a night (double)
$D = ['room_type_id' => 2, 'rate_plan_id' => 4, 'rooms' => 1];   // Deluxe AC Cottage, ₹6,500
$book = function (int $in, int $nights = 1, string $mode = 'full', array $rooms = null, string $occ = '2', array $extra = []) use ($day, $K) {
    $r = create_booking(['property' => 'kutch-safari-resort', 'check_in' => $day($in), 'check_out' => $day($in + $nights),
                         'rooms' => $rooms ?? [$K], 'occupancy' => $occ, 'payment_mode' => $mode] + $extra,
                        ['name' => 'Synthetic Guest', 'phone' => '9000000000', 'email' => 'synthetic@example.com']);
    if (!$r['ok']) throw new RuntimeException('booking failed: ' . $r['error']);
    return (int) $r['booking_id'];
};
$pay = fn(int $id) => test_payment_settle($id, (string) get_booking($id)['manage_token']);
// Move a booking's dates as if time had passed (create_booking only takes future stays).
$shift = function (int $id, string $in, string $out) {
    update('bookings', $id, ['check_in' => $in, 'check_out' => $out]);
};

/* --- B1: cancellation days are whole calendar days, the same as the guest's table --- */
foreach ([[45, 0], [31, 0], [30, 0], [29, 75], [22, 75], [21, 75], [20, 100], [1, 100], [0, 100]] as [$days, $want]) {
    $id = $book(max(1, $days));
    if ($days === 0) $shift($id, $day(0), $day(1));
    $pay($id);
    $b = get_booking($id);
    $table = array_values(array_filter(cancellation_schedule($b['check_in'], (float) $b['total']), fn($c) => !$c['past']))[0]['charge_percent'] ?? null;
    $r = cancel_booking($id, 'test', 'desk');
    $refund = money((float) $b['amount_paid'] - money((float) $b['total'] * $want / 100));
    $check($r['ok'] && (float) $r['charge_percent'] === (float) $want && (float) $table === (float) $want && abs($r['refund_due'] - max(0, $refund)) < 0.01,
           "B1 cancel $days days before arrival", "charged {$r['charge_percent']}%, guest's table says {$table}%, expected $want%, refund {$r['refund_due']}");
}

/* --- B8: 50% only while the balance date is still ahead; no promise that has passed --- */
$m31 = available_payment_modes($day(31)); $m30 = available_payment_modes($day(30)); $m10 = available_payment_modes($day(10));
$check(isset($m31['advance']) && !isset($m30['advance']) && !isset($m10['advance']), 'B8 50% offered at 31 days, not at 30 or 10',
       implode('/', array_keys($m31)) . ' · ' . implode('/', array_keys($m30)) . ' · ' . implode('/', array_keys($m10)));
$check(stripos($m10['full']['note'], 'free cancellation') === false && stripos(available_payment_modes($day(40))['full']['note'], 'free cancellation') !== false,
       'B8 "free cancellation" shown at 40 days, not at 10', '"' . $m10['full']['note'] . '"');
$q = quote_cart(['property' => 'kutch-safari-resort', 'check_in' => $day(10), 'check_out' => $day(11), 'rooms' => [$K], 'occupancy' => '2', 'payment_mode' => 'advance']);
$check($q['ok'] && $q['payment_mode'] === 'full' && abs($q['amount_due_now'] - $q['total']) < 0.01, 'B8 asking for 50% at 10 days gets full payment', "mode {$q['payment_mode']}, due now {$q['amount_due_now']} of {$q['total']}");

/* --- B3: the desk changes an unpaid 50% booking; the guest pays 50% of the NEW total --- */
$id = $book(40, 1, 'advance');
$c = ['check_in' => $day(40), 'check_out' => $day(44), 'rooms' => [$K], 'occupancy' => [2], 'addons' => []];
$r = modify_booking($id, $c, 'desk');
$b = get_booking($id);
$owed = payable_now($b);
$p = $pay($id); $b = get_booking($id); $m = booking_money($b);
$check($r['ok'] && abs($b['total'] - 29800) < 0.01 && abs($owed - 14900) < 0.01 && abs($b['amount_paid'] - 14900) < 0.01 && $b['status'] === 'confirmed'
       && $m['due_now'] < 0.01 && abs($m['later'] - 14900) < 0.01,
       'B3 unpaid 50% booking changed from 1 to 4 nights', "total {$b['total']}, charged {$b['amount_paid']} (expected 14900), later {$m['later']}");

/* --- B5: "I've paid (test)" pressed twice --- */
$id = $book(40);
$a = $pay($id); $b2 = $pay($id);
$n = (int) qval("SELECT COUNT(*) FROM payments WHERE booking_id = ? AND status = 'paid'", [$id]);
$check($a['ok'] && !$b2['ok'] && $n === 1 && abs(get_booking($id)['amount_paid'] - 7450) < 0.01, 'B5 test payment twice', "second: " . ($b2['error'] ?? 'accepted') . "; paid rows $n");

/* --- B18: UPI "Money received" after the booking was cancelled --- */
$id = $book(40);
$u = upi_payment_request($id);
$cx = cancel_booking($id, 'guest called', 'desk');
$prow = (int) qval("SELECT id FROM payments WHERE booking_id = ? AND provider = 'upi_qr'", [$id]);
$mr = upi_mark_received($prow, 'desk');
$b = get_booking($id);
$listed = (int) qval("SELECT COUNT(*) FROM payments pay JOIN bookings b ON b.id = pay.booking_id
                       WHERE pay.provider = 'upi_qr' AND pay.status = 'awaiting_confirmation' AND b.status = 'pending' AND b.id = ?", [$id]);
$check($u['ok'] && $cx['ok'] && !$mr['ok'] && $b['status'] === 'cancelled' && (float) $b['amount_paid'] === 0.0
       && qval("SELECT status FROM payments WHERE id = ?", [$prow]) === 'void' && $listed === 0,
       'B18 Money received on a cancelled booking', 'refused: ' . ($mr['error'] ?? '') . '; booking ' . $b['status']);

/* --- A card payment that lands after the booking was cancelled --- */
$id = $book(40);
$row = insert('payments', ['booking_id' => $id, 'provider' => 'razorpay', 'purpose' => 'booking', 'order_id' => 'order_T' . $id,
                           'amount' => 7450, 'status' => 'created', 'created_at' => now()]);
cancel_booking($id, 'guest called', 'desk');
$s = settle_payment($row, 'pay_T' . $id, ['method' => 'card']);
$b = get_booking($id); $cm = cancellation_money($b);
$check(!$s['ok'] && !empty($s['after_cancel']) && $b['status'] === 'cancelled' && abs($b['amount_paid'] - 7450) < 0.01 && abs($cm['outstanding'] - 7450) < 0.01,
       'Payment after cancellation is recorded and owed back', "status {$b['status']}, paid {$b['amount_paid']}, owed back {$cm['outstanding']}");
$r1 = record_offline_refund($id, 5000, 'cash', 'desk');
$r2 = record_offline_refund($id, 5000, 'cash', 'desk');          // only 2,450 left
$r3 = record_offline_refund($id, 2450, 'bank transfer', 'desk');
$b = get_booking($id); $cm = cancellation_money($b);
$check($r1['ok'] && !$r2['ok'] && $r3['ok'] && $cm['outstanding'] < 0.01 && abs($cm['given'] - 7450) < 0.01 && abs((float) $b['amount_paid']) < 0.01,
       'B2 refunds on a cancelled booking, never more than owed', "given {$cm['given']}, still owed {$cm['outstanding']}, paid now {$b['amount_paid']}");

/* --- B2 / B17: the refund shows for staff and guest; a late 50% cancellation keeps only what was paid --- */
$id = $book(40, 2, 'advance');
$pay($id);                                   // 7,450 of 14,900
$shift($id, $day(25), $day(27));             // time passes: now 25 days out (75% charge)
$r = cancel_booking($id, '', 'desk');
$b = get_booking($id); $cm = cancellation_money($b);
$check($r['ok'] && (float) $r['charge_percent'] === 75.0 && $r['refund_due'] < 0.01 && stripos($r['note'], 'nothing more is collected') !== false
       && abs($cm['kept'] - 7450) < 0.01 && abs((float) $cm['charge'] - 11175) < 0.01,
       'B17 50% paid, cancelled at 25 days (75% = 11,175)', "kept {$cm['kept']}, refund {$r['refund_due']}; note: {$r['note']}");

/* --- B9: a stay that has started is a no-show or an early departure, not a cancellation --- */
$id = $book(40, 3); $pay($id); $shift($id, $day(-1), $day(2));
$rt = q1("SELECT * FROM room_types WHERE id = 1");
$free_before = rooms_free($rt, $day(0));
$cx = cancel_booking($id, '', 'desk');
$ns = end_stay_early($id, 'no_show', 'desk');
$free_after = rooms_free($rt, $day(0));
$b = get_booking($id); $m = booking_money($b);
$check(!$cx['ok'] && stripos($cx['error'], 'already started') !== false && $ns['ok'] && $b['status'] === 'no_show'
       && $free_after === $free_before + 1 && $m['due_now'] == 0 && abs($b['amount_paid'] - 22350) < 0.01,
       'B9 in-house booking: cancel refused, no-show frees the cottage', "free $free_before → $free_after, money kept {$b['amount_paid']}");
$id = $book(40, 3, 'advance'); $pay($id); $shift($id, $day(-1), $day(2));
$f0 = rooms_free($rt, $day(0));
$le = end_stay_early($id, 'completed', 'desk');
$f1 = rooms_free($rt, $day(0));
$b = get_booking($id); $m = booking_money($b);
$check($le['ok'] && $b['status'] === 'completed' && abs($m['due_now'] - 11175) < 0.01 && $f1 === $f0 + 1,
       'B9 left early: cottage freed, unpaid half still to collect', "free $f0 → $f1, collect {$m['due_now']}");

/* --- B11: a UPI wait holds the cottage for 48 hours, not for ever --- */
$id = $book(60);
upi_payment_request($id);
$old = date('Y-m-d H:i:s', time() - 3600 * 50);
exec_sql("UPDATE bookings SET created_at = ? WHERE id = ?", [$old, $id]);
$held_new = rooms_booked(1, $day(60));
exec_sql("UPDATE payments SET created_at = ? WHERE booking_id = ?", [$old, $id]);
$held_old = rooms_booked(1, $day(60));
$check($held_new === 1 && $held_old === 0 && booking_lapsed(get_booking($id)), 'B11 UPI wait: holds for 48 h, released after', "held $held_new → $held_old");

/* --- Money arrives after the hold ended and the cottage was taken: no double booking --- */
$id = $book(70);
exec_sql("UPDATE bookings SET created_at = ? WHERE id = ?", [date('Y-m-d H:i:s', time() - 7200), $id]);   // card window over
insert('inventory', ['room_type_id' => 1, 'stay_date' => $day(70), 'rooms_open' => 0, 'note' => 'test']);  // sold elsewhere
$p = $pay($id); $b = get_booking($id);
$check(!$p['ok'] && !empty($p['no_room']) && $b['status'] === 'cancelled' && abs((float) $b['refund_amount'] - (float) $b['amount_paid']) < 0.01 && (float) $b['amount_paid'] > 0,
       'Late payment for a cottage sold meanwhile: cancelled, refunded in full', "status {$b['status']}, paid {$b['amount_paid']}, refund {$b['refund_amount']}");
exec_sql("DELETE FROM inventory");

/* --- B12: once the guest has arrived, the unpaid half is to collect at the desk --- */
$id = $book(40, 2, 'advance'); $pay($id); $shift($id, $day(0), $day(2));
$m = booking_money(get_booking($id));
$check($m['arrived'] && abs($m['due_now'] - 7450) < 0.01 && $m['later'] == 0 && due_now_label($m) === 'Collect at the desk',
       'B12 arrived with half unpaid', "due now {$m['due_now']}, later {$m['later']}, label " . due_now_label($m));

/* --- B13: no empty "If you need to cancel" on the receipt once the stay has started --- */
$pdf_text = function (string $pdf): string {
    preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $m);
    return implode("\n", array_map(fn($s) => @gzuncompress($s) ?: $s, $m[1]));
};
$future = $pdf_text(receipt_pdf(get_booking($book(40))));
$shift($id, $day(-1), $day(1));   // arrived yesterday (on the arrival day itself it can still be cancelled, at 100%)
$started = $pdf_text(receipt_pdf(get_booking($id)));
$f1 = str_contains($future, 'IF YOU NEED TO CANCEL'); $f2 = !str_contains($started, 'IF YOU NEED TO CANCEL'); $f3 = str_contains($started, 'To pay at the desk');
$check($f1 && $f2 && $f3, 'B13 receipt: cancel section only while it can be cancelled',
       'future stay has it: ' . ($f1 ? 'yes' : 'NO') . '; started stay has none: ' . ($f2 ? 'yes' : 'NO') . '; "To pay at the desk": ' . ($f3 ? 'yes' : 'NO'));

/* --- B14: Stayflexi gets what was charged per night, not the base price --- */
$id = $book(40, 3, 'full', [$D], '1');   // a Deluxe single
$line = get_booking($id)['rooms'][0];
$sent = charged_nightly($line);
$base = json_decode($line['nightly'], true);
$check(abs(array_sum($sent) - (float) $line['subtotal']) < 0.01 && array_sum($sent) < array_sum($base),
       'B14 nightly amounts for a single add up to what was charged', 'sent ' . implode('/', $sent) . ' vs base ' . implode('/', $base));

/* --- B23: a junk Stayflexi reply is "could not confirm", never "sold out" --- */
$check(sf_min_available(['raw' => '<html>502 Bad Gateway</html>']) === null && sf_min_available(['data' => []]) === null
       && sf_min_available(['data' => [['date' => 'x']]]) === null && sf_min_available(['data' => [['available' => 3], ['availableRooms' => 1]]]) === 1,
       'B23 Stayflexi reply reading');

/* --- B7: rate-limit index, and the guest's address behind a known proxy --- */
$idx = qval("SELECT 1 FROM sqlite_master WHERE type = 'index' AND name = 'idx_audit_rl'");
$GLOBALS['CONFIG']['trusted_proxies'] = ['10.0.0.1'];
$_SERVER['REMOTE_ADDR'] = '10.0.0.1'; $_SERVER['HTTP_X_FORWARDED_FOR'] = '6.6.6.6, 49.36.1.2';
$ip1 = client_ip();
$_SERVER['REMOTE_ADDR'] = '49.36.9.9';
$ip2 = client_ip();
$GLOBALS['CONFIG']['trusted_proxies'] = []; unset($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_X_FORWARDED_FOR']);
$check($idx && $ip1 === '49.36.1.2' && $ip2 === '49.36.9.9', 'B7 index present; client address behind a proxy', "via proxy $ip1, direct $ip2");

/* --- Random carts: every quote adds up, and a saved booking keeps exactly the quote --- */
mt_srand(20261004);
$addon_ids = array_map('intval', array_column(q("SELECT id FROM addons WHERE property_id = 1 AND active = 1 AND price_type <> 'per_person'"), 'id'));
$bad = []; $saved = 0;
for ($i = 0; $i < 250; $i++) {
    $nights = mt_rand(1, 7); $in = mt_rand(31, 400);
    $rooms = []; $occ = [];
    for ($r = 0, $nr = mt_rand(1, 3); $r < $nr; $r++) { $rooms[] = mt_rand(0, 1) ? $K : $D; $occ[] = mt_rand(1, 3); }
    $addons = [];
    foreach ($addon_ids as $aid) if (mt_rand(0, 3) === 0) $addons[] = ['addon_id' => $aid, 'quantity' => mt_rand(1, 3)];
    $cart = ['property' => 'kutch-safari-resort', 'check_in' => $day($in), 'check_out' => $day($in + $nights), 'rooms' => $rooms,
             'occupancy' => $occ, 'addons' => $addons, 'payment_mode' => mt_rand(0, 1) ? 'advance' : 'full'];
    $q = quote_cart($cart);
    if (!$q['ok']) { $bad[] = "#$i refused: {$q['error']}"; continue; }
    $parts = money($q['rooms_subtotal'] + $q['addons_subtotal'] - $q['discount'] + $q['tax_amount']);
    $lines = money(array_sum(array_map(fn($l) => $l['subtotal'] + $l['tax_amount'], array_merge($q['rooms'], $q['addons']))) - $q['discount']);
    $due = $q['payment_mode'] === 'advance' ? money($q['total'] / 2) : $q['total'];
    // The tariff includes GST: each room costs exactly its nightly tariff for its occupancy.
    $tariff = 0.0;
    foreach ($q['rooms'] as $l) foreach ($l['units'] as $u) {
        $plan = q1("SELECT * FROM rate_plans WHERE id = ?", [$l['rate_plan_id']]); $rt = q1("SELECT * FROM room_types WHERE id = ?", [$l['room_type_id']]);
        $single_off = $plan['single_price'] !== null ? (float) $plan['base_price'] - (float) $plan['single_price'] : 0;
        $tariff += $nights * (($u['guests'] <= 1 ? (float) $plan['base_price'] - $single_off : (float) $plan['base_price'])
                              + max(0, $u['guests'] - (int) $rt['base_occupancy']) * (float) $rt['extra_adult_price']);
    }
    $rooms_paid = array_sum(array_map(fn($l) => $l['subtotal'] + $l['tax_amount'], $q['rooms']));
    $neg = array_filter(array_merge($q['rooms'], $q['addons']), fn($l) => $l['subtotal'] < 0 || $l['tax_amount'] < 0);
    if (abs($parts - $q['total']) > 0.011 || abs($lines - $q['total']) > 0.03 || abs($q['amount_due_now'] - $due) > 0.011
        || abs($rooms_paid - $tariff) > 0.01 * $nights * count($occ) + 0.01 || $neg) {
        $bad[] = "#$i total {$q['total']} parts $parts lines $lines due {$q['amount_due_now']}/$due rooms $rooms_paid tariff $tariff";
    }
    if ($i % 10 === 0) {   // save some, and compare what was stored with the quote
        $r = create_booking($cart, ['name' => 'Random ' . $i]);
        if ($r['ok']) {
            $b = get_booking((int) $r['booking_id']); $saved++;
            if (abs($b['total'] - $q['total']) > 0.001 || abs($b['amount_due_now'] - $q['amount_due_now']) > 0.001 || abs($b['tax_amount'] - $q['tax_amount']) > 0.001) {
                $bad[] = "#$i saved {$b['total']} vs quoted {$q['total']}";
            }
        }
    }
}
$check(!$bad, "250 random carts add up ($saved saved and compared)", $bad ? implode(' | ', array_slice($bad, 0, 5)) : 'rooms + extras − discount + GST = total; lines = total; 50% = half; rooms = tariff');

/* --- GST: where the slab cannot be decided from a GST-inclusive price (for the accountant) --- */
$odd = [];
foreach ([[7450, 'Kutchi double'], [7450 + 1500, 'Kutchi triple'], [6500, 'Deluxe double'], [6500 + 1500, 'Deluxe triple'], [6500 - 1000, 'Deluxe single']] as [$t, $what]) {
    [$net, $tax] = tax_split((float) $t, true);
    $rate = round($tax / $net * 100);
    if (($rate == 18 && $net <= 7500) || ($rate == 5 && $net > 7500)) $odd[] = "$what ₹$t: GST {$rate}% on a room value of ₹" . number_format($net, 2);
}
echo 'INFO GST slab check (not a pass/fail): ' . ($odd ? implode('; ', $odd) : 'every tariff lands clearly in one slab') . "\n";

/* --- B26: setup --reset over bookings changes nothing --- */
$before = [qval("SELECT COUNT(*) FROM room_types"), qval("SELECT COUNT(*) FROM addons"), qval("SELECT COUNT(*) FROM properties")];
$runner = tempnam(sys_get_temp_dir(), 'ksr-setup-') . '.php';
file_put_contents($runner, '<?php $argv = ["setup.php", "--reset"]; require ' . var_export(__DIR__ . '/../lib/db.php', true) . ';'
    . ' $GLOBALS["CONFIG"]["db"]["sqlite_path"] = ' . var_export($copy, true) . '; require ' . var_export(__DIR__ . '/setup.php', true) . ';');
db()->exec('PRAGMA wal_checkpoint(TRUNCATE)');
exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($runner) . ' 2>&1', $out, $code);
@unlink($runner);
$after = [qval("SELECT COUNT(*) FROM room_types"), qval("SELECT COUNT(*) FROM addons"), qval("SELECT COUNT(*) FROM properties")];
$check($code === 1 && $before === $after && str_contains(implode("\n", $out), 'none of it was changed'), 'B26 setup --reset over bookings is all or nothing',
       'exit ' . $code . ', rooms/extras/properties ' . implode('/', $before) . ' → ' . implode('/', $after));

echo "\n$pass passed, $fail failed\n";
exit($fail ? 1 : 0);
