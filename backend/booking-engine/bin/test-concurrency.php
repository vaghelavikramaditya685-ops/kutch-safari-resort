<?php
/* ===========================================================================
 *  Race checks: many guests, staff or addresses doing the same thing at the
 *  same instant (docs/09 "How to test").
 *
 *      php bin/test-concurrency.php
 *
 *  Each check starts up to 20 separate PHP processes that wait for the same
 *  moment and then act on the same booking, cottage or payment, each from its
 *  own address. Afterwards the database must make sense: no cottage sold twice,
 *  no payment counted twice, one confirmation, one cancellation, nobody's details
 *  mixed with anyone else's.
 *
 *  Works on a THROWAWAY COPY of the SQLite database in the system temp folder,
 *  deleted at the end; the real database is never opened for writing. On the
 *  live MySQL database the same code relies on row locks (FOR UPDATE); run this
 *  there only against a copy, too.
 * ======================================================================== */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

/* --- A worker: one process, one action, at the agreed moment ---------------- */
if (($argv[1] ?? '') === '--worker') {
    [, , $copy, $scenario, $i, $start, $arg] = $argv + [6 => ''];
    $_SERVER['REMOTE_ADDR'] = str_starts_with($scenario, 'ratelimit_sameip') ? '203.0.113.7' : '203.0.113.' . (10 + (int) $i);
    if ($scenario === 'admin_lock') session_id('ksrtest' . $i);
    require_once __DIR__ . '/../lib/db.php';
    $GLOBALS['CONFIG']['db']['sqlite_path'] = $copy;
    $GLOBALS['CONFIG']['mail']['enabled'] = false;
    $GLOBALS['CONFIG']['test_payments']['enabled'] = true;
    $GLOBALS['CONFIG']['upi'] = ['enabled' => true, 'vpa' => 'test@upi', 'payee_name' => 'Test', 'hold_minutes' => 45, 'confirm_hours' => 48];
    $GLOBALS['CONFIG']['stayflexi']['enabled'] = false;
    $GLOBALS['CONFIG']['rules']['guest_details_optional'] = true;
    require_once __DIR__ . '/../lib/booking.php';
    require_once __DIR__ . '/../lib/payment.php';
    if ($scenario === 'admin_lock') require_once __DIR__ . '/../admin/_auth.php';
    if (str_starts_with($scenario, 'ratelimit')) { ob_start(); require_once __DIR__ . '/../api/_init.php'; ob_end_clean(); }
    db();   // connect before the start line, as a running server would be
    while (microtime(true) < (float) $start) usleep(500);

    $id = (int) $arg;
    $r = match ($scenario) {
        'last_cottages', 'many_dates' => create_booking(json_decode($arg, true), [
            'name' => "Guest $i", 'phone' => '90000' . str_pad((string) $i, 5, '0', STR_PAD_LEFT), 'email' => "guest$i@example.com"]),
        'test_pay'      => test_payment_settle($id, (string) qval("SELECT manage_token FROM bookings WHERE id = ?", [$id])),
        'settle_same'   => settle_payment($id, 'pay_SAME', ['method' => 'card']),
        'upi_received'  => upi_mark_received($id, "Staff $i"),
        'desk_payment'  => record_offline_payment($id, 7450, 'cash', "Staff $i"),
        'cancel_same'   => cancel_booking($id, "Staff $i cancelled", "Staff $i"),
        'cancel_or_pay' => $i % 2 ? cancel_booking($id, 'race', "Staff $i")
                                  : test_payment_settle($id, (string) qval("SELECT manage_token FROM bookings WHERE id = ?", [$id])),
        'modify_same'   => modify_booking($id, ['check_in' => date('Y-m-d', strtotime('+50 days')),
                              'check_out' => date('Y-m-d', strtotime('+' . (51 + (int) $i) . ' days')),
                              'rooms' => [['room_type_id' => 1, 'rate_plan_id' => 1, 'rooms' => 1]], 'occupancy' => [2], 'addons' => []], "Staff $i"),
        'ratelimit_sameip', 'ratelimit_manyip' => (function () use ($scenario) {
            // A refusal ends the request (json_fail exits); report it as not let through.
            register_shutdown_function(function () { if (empty($GLOBALS['rl_through'])) { while (ob_get_level()) ob_end_clean(); echo json_encode(['ok' => false, 'limited' => true]); } });
            ob_start();
            rate_limit('race', $scenario === 'ratelimit_sameip' ? 12 : 1, 300);
            ob_end_clean();
            $GLOBALS['rl_through'] = true;
            return ['ok' => true];
        })(),
        'admin_lock'    => ['ok' => admin_lock_take(['id' => 1, 'name' => "Admin $i"]) === null],
    };
    echo json_encode($r);
    exit;
}

/* --- The checks -------------------------------------------------------------- */
require_once __DIR__ . '/../lib/db.php';
if (cfg('db.driver') !== 'sqlite' || !is_file((string) cfg('db.sqlite_path'))) {
    exit("Run this on a development PC with the SQLite database (config.local.php → db.driver = sqlite).\n");
}
$copy = tempnam(sys_get_temp_dir(), 'ksr-race-') . '.sqlite';
copy((string) cfg('db.sqlite_path'), $copy);
register_shutdown_function(function () use ($copy) {
    foreach ([$copy, "$copy-wal", "$copy-shm", preg_replace('/\.sqlite$/', '', $copy)] as $f) @unlink($f);
});
$GLOBALS['CONFIG']['db']['sqlite_path'] = $copy;
$GLOBALS['CONFIG']['mail']['enabled'] = false;
$GLOBALS['CONFIG']['test_payments']['enabled'] = true;
$GLOBALS['CONFIG']['upi'] = ['enabled' => true, 'vpa' => 'test@upi', 'payee_name' => 'Test', 'hold_minutes' => 45, 'confirm_hours' => 48];
$GLOBALS['CONFIG']['stayflexi']['enabled'] = false;
$GLOBALS['CONFIG']['rules']['guest_details_optional'] = true;
require_once __DIR__ . '/../lib/booking.php';
require_once __DIR__ . '/../lib/payment.php';
foreach (['booking_rooms', 'booking_addons', 'payments', 'holds', 'bookings', 'rates', 'inventory', 'coupons', 'audit_log'] as $t) db()->exec("DELETE FROM $t");
try { db()->exec("DELETE FROM admin_lock"); } catch (Throwable $e) {}

$pass = 0; $fail = 0;
$check = function ($ok, string $what, string $detail = '') use (&$pass, &$fail) {
    $ok ? $pass++ : $fail++;
    echo ($ok ? 'PASS ' : 'FAIL ') . $what . ($detail !== '' ? " — $detail" : '') . "\n";
};
$day = fn(int $n) => date('Y-m-d', strtotime("+$n days"));
$K = ['room_type_id' => 1, 'rate_plan_id' => 1, 'rooms' => 1];

/** Start $n workers on one scenario at the same instant; their results, in order. $args: one per worker, or one for all. */
$race = function (string $scenario, int $n, $args = '') use ($copy): array {
    db()->exec('PRAGMA wal_checkpoint(TRUNCATE)');   // every worker sees what the parent wrote
    $start = microtime(true) + 2.5;
    $procs = [];
    for ($i = 0; $i < $n; $i++) {
        $arg = is_array($args) ? $args[$i] : $args;
        $cmd = [PHP_BINARY, __FILE__, '--worker', $copy, $scenario, (string) $i, sprintf('%.4F', $start), (string) $arg];
        $p = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $procs[] = [$p, $pipes];
    }
    $out = [];
    foreach ($procs as [$p, $pipes]) {
        $o = stream_get_contents($pipes[1]); $e = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]); proc_close($p);
        $out[] = json_decode($o, true) ?? ['ok' => false, 'error' => 'worker crashed: ' . trim($o . ' ' . $e)];
    }
    return $out;
};
$oks = fn(array $res) => count(array_filter($res, fn($r) => !empty($r['ok'])));
$audits = fn(string $action, $id = null) => (int) qval("SELECT COUNT(*) FROM audit_log WHERE action = ?" . ($id === null ? '' : ' AND entity_id = ?'),
                                                       $id === null ? [$action] : [$action, (string) $id], 0);
$pay = fn(int $id) => test_payment_settle($id, (string) get_booking($id)['manage_token']);
$book = function (int $in, int $nights = 1, string $mode = 'full') use ($day, $K) {
    $r = create_booking(['property' => 'kutch-safari-resort', 'check_in' => $day($in), 'check_out' => $day($in + $nights),
                         'rooms' => [$K], 'occupancy' => '2', 'payment_mode' => $mode], ['name' => 'Race Guest', 'email' => 'race@example.com']);
    if (!$r['ok']) throw new RuntimeException($r['error']);
    return (int) $r['booking_id'];
};

// 1. Twenty guests, three Kutchi cottages left: exactly three bookings, nobody's details mixed up.
insert('inventory', ['room_type_id' => 1, 'stay_date' => $day(90), 'rooms_open' => 3, 'note' => 'race']);
$cart = json_encode(['property' => 'kutch-safari-resort', 'check_in' => $day(90), 'check_out' => $day(91), 'rooms' => [$K], 'occupancy' => '2', 'payment_mode' => 'full']);
$res = $race('last_cottages', 20, $cart);
$held = rooms_booked(1, $day(90));
$locked = $audits('booking_failed');
$names_ok = !array_filter(q("SELECT guest_name, guest_phone, guest_email FROM bookings WHERE check_in = ?", [$day(90)]),
    fn($b) => !preg_match('/^Guest (\d+)$/', $b['guest_name'], $m) || $b['guest_email'] !== "guest{$m[1]}@example.com"
              || $b['guest_phone'] !== '90000' . str_pad($m[1], 5, '0', STR_PAD_LEFT));
$check($oks($res) === 3 && $held === 3 && $locked === 0 && $names_ok, '20 guests, 3 cottages left, same instant',
       "booked {$oks($res)}, held $held of 3, 'database locked' failures $locked, details " . ($names_ok ? 'all matched' : 'MIXED'));

// 2. Twenty guests on twenty different dates: all twenty saved (B4: 17 used to fail with "database is locked").
$carts = array_map(fn($i) => json_encode(['property' => 'kutch-safari-resort', 'check_in' => $day(100 + $i * 2), 'check_out' => $day(101 + $i * 2),
                                           'rooms' => [$K], 'occupancy' => '2', 'payment_mode' => 'full']), range(0, 19));
$res = $race('many_dates', 20, $carts);
$refs = array_column(array_filter($res, fn($r) => !empty($r['ok'])), 'ref');
$tokens = array_column(q("SELECT manage_token FROM bookings"), 'manage_token');
$check($oks($res) === 20 && count(array_unique($refs)) === 20 && count(array_unique($tokens)) === count($tokens), 'B4 20 bookings on different dates at once',
       "saved {$oks($res)} of 20, unique codes " . count(array_unique($refs)) . ', private links all different: ' . (count(array_unique($tokens)) === count($tokens) ? 'yes' : 'NO'));

// 3. "I've paid (test)" pressed by twenty at once: one payment (B5).
$id = $book(120);
$res = $race('test_pay', 20, $id);
$b = get_booking($id);
$rows = (int) qval("SELECT COUNT(*) FROM payments WHERE booking_id = ? AND status = 'paid'", [$id]);
$check($oks($res) === 1 && $rows === 1 && abs($b['amount_paid'] - 7450) < 0.01 && $b['status'] === 'confirmed', 'B5 20 test payments at once',
       "accepted {$oks($res)}, paid rows $rows, amount paid {$b['amount_paid']} of {$b['total']}");

// 4. One card payment confirmed twenty times at once (browser return, webhook, retries): one email, one confirmation (B6).
$id = $book(122);
$row = insert('payments', ['booking_id' => $id, 'provider' => 'razorpay', 'purpose' => 'booking', 'order_id' => 'order_RACE',
                           'amount' => 7450, 'status' => 'created', 'created_at' => now()]);
$mails0 = $audits('mail_skipped');
$res = $race('settle_same', 20, $row);
$b = get_booking($id);
$settled = $audits('payment_settled', $id); $mails = $audits('mail_skipped') - $mails0;
$check($oks($res) === 20 && $settled === 1 && $mails === 1 && abs($b['amount_paid'] - 7450) < 0.01 && $b['status'] === 'confirmed',
       'B6 one payment confirmed by 20 requests at once', "confirmations $settled, emails $mails (each used to happen up to 15 times), paid {$b['amount_paid']}");

// 5. Two (here ten) desk staff press "Money received" on the same UPI payment (B6).
$id = $book(124);
upi_payment_request($id);
$prow = (int) qval("SELECT id FROM payments WHERE booking_id = ? AND provider = 'upi_qr'", [$id]);
$res = $race('upi_received', 10, $prow);
$b = get_booking($id);
$check($oks($res) === 1 && $audits('upi_confirmed', $id) === 1 && abs($b['amount_paid'] - 7450) < 0.01, 'B6 10 staff confirm the same UPI payment',
       "accepted {$oks($res)}, paid {$b['amount_paid']}");

// 6. Twenty desk payments of the same ₹7,450 balance at once: taken once, never overpaid.
$id = $book(126, 2, 'advance'); $pay($id);
$res = $race('desk_payment', 20, $id);
$b = get_booking($id);
$check($oks($res) === 1 && abs($b['amount_paid'] - (float) $b['total']) < 0.01, '20 desk payments of the balance at once',
       "accepted {$oks($res)}, paid {$b['amount_paid']} of {$b['total']}");

// 7. Twenty staff cancel the same booking at once: one cancellation, one refund.
$id = $book(128); $pay($id);
$res = $race('cancel_same', 20, $id);
$b = get_booking($id);
$check($oks($res) === 1 && $audits('booking_cancelled', $id) === 1 && $b['status'] === 'cancelled' && abs((float) $b['refund_amount'] - 7450) < 0.01,
       '20 cancellations of one booking at once', "accepted {$oks($res)}, refund {$b['refund_amount']}");

// 8. Half the requests cancel, half pay, at the same instant: never both "confirmed" and "cancelled", money all accounted for.
$bad = [];
for ($k = 0; $k < 3; $k++) {
    $id = $book(130 + $k);
    $res = $race('cancel_or_pay', 10, $id);
    $b = get_booking($id);
    $sum = (float) qval("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE booking_id = ? AND status = 'paid'", [$id]);
    $paid_rows = (int) qval("SELECT COUNT(*) FROM payments WHERE booking_id = ? AND status = 'paid'", [$id]);
    $cancels = $audits('booking_cancelled', $id);
    if ($paid_rows > 1 || abs($sum - (float) $b['amount_paid']) > 0.01 || $cancels > 1
        || ($b['status'] === 'cancelled' && abs((float) $b['refund_amount'] - (float) $b['amount_paid']) > 0.01)   // 0% charge at 130 days
        || ($b['status'] === 'confirmed' && $cancels)) {
        $bad[] = "#$k {$b['status']} paid {$b['amount_paid']} rows $paid_rows refund {$b['refund_amount']} cancels $cancels";
    }
}
$check(!$bad, 'Cancel and pay racing on the same booking (3 rounds of 10)', $bad ? implode(' | ', $bad) : 'every round ended in one consistent state');

// 9. Ten desk staff change the same booking at once: one change is saved, the rest are told to check again.
$id = $book(50); $pay($id);
$res = $race('modify_same', 10, $id);
$b = get_booking($id);
$lines = money(array_sum(array_map(fn($l) => $l['subtotal'] + $l['tax_amount'], array_merge($b['rooms'], $b['addons']))) - (float) $b['discount']);
$check($oks($res) === 1 && abs($lines - (float) $b['total']) < 0.02 && abs($b['total'] - 7450 * (int) $b['nights']) < 0.02,
       '10 desk changes to one booking at once', "saved {$oks($res)}, now {$b['nights']} nights for {$b['total']}");

// 10. Thirty requests from one address at the same instant: the limit (12) holds.
$res = $race('ratelimit_sameip', 30);
$check($oks($res) === 12, 'Rate limit: 30 requests from one address at once', "let through {$oks($res)} of 30 (limit 12)");

// 11. Twenty different addresses at once, a limit of one each: nobody blocks anybody else.
$res = $race('ratelimit_manyip', 20);
$check($oks($res) === 20, 'Rate limit: 20 different addresses at once', "let through {$oks($res)} of 20");

// 12. Twenty people sign in to the admin at the same instant: exactly one gets it (B10).
$res = $race('admin_lock', 20);
$holder = q1("SELECT * FROM admin_lock WHERE id = 1");
$check($oks($res) === 1 && $holder && str_starts_with((string) $holder['admin_name'], 'Admin '), 'B10 20 admin sign-ins at once',
       "got the admin panel: {$oks($res)} (" . ($holder['admin_name'] ?? 'nobody') . ')');

echo "\n$pass passed, $fail failed\n";
exit($fail ? 1 : 0);
