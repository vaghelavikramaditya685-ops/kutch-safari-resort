<?php
/* ===========================================================================
 *  Price and money checks for changing a booking (docs/22, docs/30).
 *
 *      php bin/test-changes.php
 *
 *  Works on a THROWAWAY COPY of the SQLite database: the copy is made in the
 *  system temp folder, every test booking goes into it, and it is deleted at
 *  the end. The real database is never opened for writing. Needs the SQLite
 *  setup (config.local.php with driver sqlite), i.e. a development PC.
 * ======================================================================== */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../lib/db.php';
if (cfg('db.driver') !== 'sqlite' || !is_file((string) cfg('db.sqlite_path'))) {
    exit("Run this on a development PC with the SQLite database (config.local.php → db.driver = sqlite).\n");
}
$copy = tempnam(sys_get_temp_dir(), 'ksr-test-') . '.sqlite';
copy((string) cfg('db.sqlite_path'), $copy);
register_shutdown_function(function () use ($copy) { $GLOBALS['__pdo_close'] = true; @unlink($copy); @unlink(preg_replace('/\.sqlite$/', '', $copy)); });
$GLOBALS['CONFIG']['db']['sqlite_path'] = $copy;
$GLOBALS['CONFIG']['rules']['guest_details_optional'] = true;
$GLOBALS['CONFIG']['mail']['smtp']['enabled'] = false;
require_once __DIR__ . '/../lib/booking.php';
require_once __DIR__ . '/../lib/payment.php';
foreach (['booking_rooms','booking_addons','payments','bookings','rates','inventory','coupons'] as $t) db()->exec("DELETE FROM $t");

$in  = date('Y-m-d', strtotime('+20 days')); $out = date('Y-m-d', strtotime('+23 days'));   // 3 nights
$K = ['room_type_id' => 1, 'rate_plan_id' => 1, 'rooms' => 1];
$D = ['room_type_id' => 2, 'rate_plan_id' => 4, 'rooms' => 1];
$innova = (int) q1("SELECT id FROM addons WHERE code = 'transfer-innova'")['id'];
$candle = (int) q1("SELECT id FROM addons WHERE code = 'candlelight-dinner'")['id'];
$pass = 0; $fail = 0;
$check = function ($ok, $what, $detail) use (&$pass, &$fail) { $ok ? $pass++ : $fail++; echo ($ok ? 'PASS ' : 'FAIL ') . "$what — $detail\n"; };
// Every change must add up: old total + added − removed ± changed = new total.
$adds = function ($q) { $c = $q['changes']; return abs($q['old_total'] + $c['added_total'] + $c['removed_total'] + $c['changed_total'] - $q['total']) < 0.005; };
$show = function ($q) { foreach (['added','removed','changed'] as $k) foreach ($q['changes'][$k] as $l) echo "       $k: {$l['what']}  {$l['amount']}\n"; };
$book = function (array $cart) {
    $r = create_booking($cart + ['property' => 'kutch-safari-resort', 'payment_mode' => 'full'], ['name' => '', 'phone' => '']);
    if (!$r['ok']) die('booking failed: ' . $r['error'] . "\n");
    return (int) $r['booking_id'];
};
// What the booking looks like now, as the change page would start from it.
$current = function (int $id) {
    $b = get_booking($id); $rooms = []; $occ = [];
    foreach ($b['rooms'] as $l) {
        preg_match_all('/Room \d+ (Single|Double|Triple)/', $l['rate_plan_name'], $m);
        for ($i = 0; $i < $l['rooms']; $i++) {
            $rooms[] = ['room_type_id' => (int) $l['room_type_id'], 'rate_plan_id' => (int) $l['rate_plan_id'], 'rooms' => 1];
            $occ[] = ['Single' => 1, 'Double' => 2, 'Triple' => 3][$m[1][$i] ?? 'Double'];
        }
    }
    $addons = array_map(fn($a) => ['addon_id' => (int) $a['addon_id'], 'quantity' => (int) $a['quantity']], $b['addons']);
    return ['check_in' => $b['check_in'], 'check_out' => $b['check_out'], 'rooms' => $rooms, 'occupancy' => $occ, 'addons' => $addons];
};
$total = fn($id) => (float) get_booking($id)['total'];

// 1–5: a normal booking — Kutchi Double + Deluxe Double, 3 nights, 1 Innova.
$id = $book(['check_in' => $in, 'check_out' => $out, 'occupancy' => '2,2', 'rooms' => [$K, $D], 'addons' => [['addon_id' => $innova, 'quantity' => 1]]]);
$t0 = $total($id);
$c = $current($id); $c['addons'][] = ['addon_id' => $candle, 'quantity' => 2];
$q = quote_modification($id, $c); $check(abs($q['total'] - ($t0 + 6000)) < 0.02, '1 add 2 candlelight dinners', "was $t0, now {$q['total']}, expected " . ($t0 + 6000));
$c = $current($id); $c['addons'] = [];
$q = quote_modification($id, $c); $check(abs($q['total'] - ($t0 - 2100)) < 0.02, '2 remove the Innova', "now {$q['total']}, expected " . ($t0 - 2100));
$c = $current($id); array_pop($c['rooms']); array_pop($c['occupancy']);
$q = quote_modification($id, $c); $check(abs($q['total'] - ($t0 - 3 * 6500)) < 0.02, '3 remove the Deluxe room', "now {$q['total']}, expected " . ($t0 - 3 * 6500));
$c = $current($id); $c['check_out'] = date('Y-m-d', strtotime($out . ' +1 day'));
$q = quote_modification($id, $c); $check(abs($q['total'] - ($t0 + 7450 + 6500)) < 0.02, '4 add one night', "now {$q['total']}, expected " . ($t0 + 7450 + 6500));
$c = $current($id); $c['occupancy'][0] = 3;
$q = quote_modification($id, $c); $check(abs($q['total'] - ($t0 + 3 * 1500)) < 0.02, '5 Kutchi Double → Triple', "now {$q['total']}, expected " . ($t0 + 3 * 1500));

// 6: booked while a special price was on, special price later removed.
foreach ([0, 1, 2] as $n) insert('rates', ['rate_plan_id' => 1, 'stay_date' => date('Y-m-d', strtotime("$in +$n days")), 'price' => 5000, 'min_stay' => 1, 'closed' => 0]);
$id6 = $book(['check_in' => $in, 'check_out' => $out, 'occupancy' => '2', 'rooms' => [$K]]);
$t6 = $total($id6);
db()->exec("DELETE FROM rates");
$c = $current($id6); $c['addons'][] = ['addon_id' => $innova, 'quantity' => 1];
$q = quote_modification($id6, $c);
$check(abs($q['total'] - ($t6 + 2100)) < 0.02, '6 add an Innova to a booking made at a special price', "booked at $t6 (3 × ₹5,000); now {$q['total']}, expected " . ($t6 + 2100));

// 7: booked with a discount code that has since been used up.
insert('coupons', ['code' => 'RANN10', 'discount_type' => 'percent', 'amount' => 10, 'min_nights' => 1, 'max_uses' => 1, 'times_used' => 0, 'active' => 1]);
$id7 = $book(['check_in' => $in, 'check_out' => $out, 'occupancy' => '2', 'rooms' => [$K], 'coupon' => 'RANN10']);
$b7 = get_booking($id7);
$c = $current($id7); $c['addons'][] = ['addon_id' => $innova, 'quantity' => 1];
$q = quote_modification($id7, $c);
$check((float) $b7['discount'] > 0 && abs($q['total'] - ((float) $b7['total'] + 2100)) < 0.02, '7 change a booking made with a discount code that is now used up',
       "booked {$b7['total']} with discount {$b7['discount']}; now {$q['total']} (discount {$q['discount']}), expected " . ((float) $b7['total'] + 2100));

// 8–11: dates.
$c = $current($id); $c['check_out'] = date('Y-m-d', strtotime($out . ' -1 day'));
$q = quote_modification($id, $c); $show($q); $check(abs($q['total'] - ($t0 - 7450 - 6500)) < 0.02 && $adds($q), '8 one night shorter', "now {$q['total']}, expected " . ($t0 - 7450 - 6500));
$c = $current($id); $c['check_in'] = date('Y-m-d', strtotime($in . ' +1 day')); $c['check_out'] = date('Y-m-d', strtotime($out . ' +1 day'));
$q = quote_modification($id, $c); $show($q); $check(abs($q['total'] - $t0) < 0.02 && $adds($q), '9 same length, a day later', "now {$q['total']}, expected $t0");
$c = $current($id6); $c['check_out'] = date('Y-m-d', strtotime($out . ' +1 day'));
$q = quote_modification($id6, $c); $show($q); $check(abs($q['total'] - ($t6 + 7450)) < 0.02 && $adds($q), '10 special-price stay one night longer', "was $t6, now {$q['total']}, expected " . ($t6 + 7450));
$c = $current($id6); $c['check_out'] = date('Y-m-d', strtotime($out . ' -1 day'));
$q = quote_modification($id6, $c); $check(abs($q['total'] - ($t6 - 5000)) < 0.02 && $adds($q), '11 special-price stay one night shorter', "now {$q['total']}, expected " . ($t6 - 5000));

// 12: many changes at once — must still add up, and saving keeps exactly that.
$c = $current($id); $c['rooms'][] = $D; $c['occupancy'][] = 1; $c['occupancy'][0] = 1; $c['addons'] = [['addon_id' => $candle, 'quantity' => 2]];
$c['check_out'] = date('Y-m-d', strtotime($out . ' +1 day'));
$q = quote_modification($id, $c); $show($q);
$r = modify_booking($id, $c, 'test'); $b = get_booking($id);
$parts = round($b['rooms_subtotal'] + $b['addons_subtotal'] - $b['discount'] + $b['tax_amount'], 2);
$lines = round(array_sum(array_map(fn($l) => $l['subtotal'] + $l['tax_amount'], array_merge($b['rooms'], $b['addons']))), 2);
$check($adds($q) && $r['ok'] && abs($b['total'] - $q['total']) < 0.005 && abs($parts - $b['total']) < 0.005 && abs($lines - $b['total']) < 0.02,
       '12 several changes at once, then saved', "old $t0, quote {$q['total']}, saved {$b['total']}, parts $parts, lines $lines");
// 13: after saving, changing nothing changes nothing.
$q = quote_modification($id, $current($id));
$check(abs($q['total'] - $b['total']) < 0.005 && !$q['changes']['added'] && !$q['changes']['removed'] && !$q['changes']['changed'], '13 no change → same total', "{$q['total']}");

// 14–16: paid 50% or in full.
$id14 = $book(['check_in' => $in, 'check_out' => $out, 'occupancy' => '2', 'rooms' => [$D]]);
update('bookings', $id14, ['payment_mode' => 'advance', 'amount_paid' => 9750, 'status' => 'confirmed']);
$c = $current($id14); $c['addons'][] = ['addon_id' => $innova, 'quantity' => 1];
$q = quote_modification($id14, $c); $m = $q['money'];
$check(abs($q['total'] - 21600) < 0.02 && abs($m['due_now'] - 1050) < 0.02 && abs($m['later'] - 10800) < 0.02,
       '14 50% paid, add Innova', "total {$q['total']}, collect now {$m['due_now']}, before arrival {$m['later']} (expected 1050 / 10800)");
$c = $current($id14); $c['check_out'] = date('Y-m-d', strtotime($out . ' -2 day'));
$q = quote_modification($id14, $c); $m = $q['money'];
$check(abs($q['total'] - 6500) < 0.02 && abs($m['refund'] - 3250) < 0.02 && $m['due_now'] == 0 && $m['later'] == 0,
       '15 50% paid, cut to one night', "total {$q['total']}, refund {$m['refund']} (expected 3250)");
update('bookings', $id14, ['payment_mode' => 'full', 'amount_paid' => 19500]);
$c = $current($id14); $c['addons'][] = ['addon_id' => $innova, 'quantity' => 1];
$q = quote_modification($id14, $c); $m = $q['money'];
$check(abs($m['due_now'] - 2100) < 0.02 && $m['later'] == 0, '16 paid in full, add Innova', "collect now {$m['due_now']}, before arrival {$m['later']}");

// 17: amount paid is payments minus refunds, whichever way money comes in.
$id17 = $book(['check_in' => $in, 'check_out' => date('Y-m-d', strtotime($in . ' +2 days')), 'occupancy' => '2', 'rooms' => [$D]]);
update('bookings', $id17, ['payment_mode' => 'advance', 'amount_due_now' => 6500]);
test_payment_settle($id17, get_booking($id17)['manage_token']);
record_offline_refund($id17, 1000, 'cash', 'test');
record_offline_payment($id17, 500, 'cash', 'test');
$b = get_booking($id17); $m = booking_money($b);
$check(abs($b['amount_paid'] - 6000) < 0.01 && abs($m['due_now'] - 500) < 0.01 && abs($m['later'] - 6500) < 0.01,
       '17 paid 6,500, gave back 1,000, took 500', "paid {$b['amount_paid']}, due now {$m['due_now']}, later {$m['later']}");

echo "\n$pass passed, $fail failed\n";
exit($fail ? 1 : 0);
