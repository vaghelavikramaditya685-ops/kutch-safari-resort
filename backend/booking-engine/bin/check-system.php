<?php
/* ===========================================================================
 *  Whole-system readiness check.
 *
 *      php bin/check-system.php
 *
 *  Exercises the real booking code — availability, quoting, creating a booking,
 *  guest lookup, cancellation, overbooking refusal — then reports on payments,
 *  the channel manager, and what still stands between you and real guests.
 *
 *  It creates one throwaway booking and cancels it again, so it is safe to run
 *  whenever you want. Nothing is charged.
 * ======================================================================== */

require_once __DIR__ . '/../lib/booking.php';
require_once __DIR__ . '/../lib/payment.php';
require_once __DIR__ . '/../lib/channel.php';

$counts = ['ok' => 0, 'warn' => 0, 'fail' => 0];

function line(string $state, string $label, string $detail = ''): void {
    global $counts;
    $counts[strtolower($state)]++;
    $tag = ['OK' => '  [ ok ] ', 'WARN' => '  [warn] ', 'FAIL' => '  [FAIL] '][$state];
    printf("%s%-40s %s\n", $tag, $label, $detail);
}
function head(string $t): void { echo "\n$t\n" . str_repeat('-', 72) . "\n"; }

head('BOOKING ENGINE');

$camp = q1("SELECT * FROM properties WHERE code = ?", ['white-rann-camp']);
$avail = search_availability($camp, '2026-12-28', '2026-12-30', 2, 0, 1);
count($avail)
    ? line('OK', 'Availability search', count($avail) . ' room types offered')
    : line('FAIL', 'Availability search', 'returned nothing');

$quote = quote_cart([
    'property' => 'white-rann-camp', 'check_in' => '2026-12-28', 'check_out' => '2026-12-30',
    'adults' => 2, 'rooms' => [['room_type_id' => 4, 'rate_plan_id' => 8, 'rooms' => 1]],
    'addons' => [['addon_id' => 6, 'quantity' => 2]], 'payment_mode' => 'advance',
]);
$quote['ok']
    ? line('OK', 'Quoting, tax and add-ons', 'total ' . $quote['total'] . ', due now ' . $quote['amount_due_now'])
    : line('FAIL', 'Quoting', $quote['error']);

// GST slabs: below and above the 7,500 line should be taxed differently.
$plans = $avail ? array_merge(...array_map(fn($r) => $r['rate_plans'], $avail)) : [];
$rates = array_unique(array_map(fn($p) => round($p['tax'] / max(0.01, $p['subtotal']) * 100), $plans));
count($rates) > 1
    ? line('OK', 'GST slabs applied per night', implode('% and ', $rates) . '%')
    : line('WARN', 'GST slabs', 'only one rate seen — check the tariffs straddle 7,500');

$made = create_booking([
    'property' => 'white-rann-camp', 'check_in' => '2027-01-05', 'check_out' => '2027-01-07',
    'adults' => 2, 'rooms' => [['room_type_id' => 3, 'rate_plan_id' => 7, 'rooms' => 1]],
    'payment_mode' => 'hotel',
], ['name' => 'System Check', 'phone' => '9000000001', 'email' => 'check@example.com']);

if ($made['ok']) {
    line('OK', 'Creating a booking', $made['ref']);
    line(find_booking($made['ref'], '9000000001') ? 'OK' : 'FAIL', 'Guest lookup by reference + phone');
    $cancelled = cancel_booking((int) $made['booking_id'], 'automated system check', 'check');
    $cancelled['ok']
        ? line('OK', 'Cancellation and refund rules', $cancelled['charge_percent'] . '% charge applied')
        : line('FAIL', 'Cancellation', $cancelled['error']);
} else {
    line('FAIL', 'Creating a booking', $made['error']);
}

$over = quote_cart([
    'property' => 'white-rann-camp', 'check_in' => '2026-12-28', 'check_out' => '2026-12-30',
    'adults' => 2, 'rooms' => [['room_type_id' => 3, 'rate_plan_id' => 7, 'rooms' => 99]],
    'payment_mode' => 'full',
]);
!$over['ok']
    ? line('OK', 'Overbooking refused', substr($over['error'], 0, 34))
    : line('FAIL', 'Overbooking NOT refused', 'this would oversell rooms');

validate_dates($camp, '2027-03-10', '2027-03-12')
    ? line('OK', 'Season limits enforced', 'camp closed outside Dec-Jan')
    : line('FAIL', 'Season limits', 'out-of-season dates were accepted');

head('PAYMENTS');

$key  = (string) cfg('razorpay.key_id');
$mode = str_starts_with($key, 'rzp_test_') ? 'TEST'
      : (str_starts_with($key, 'rzp_live_') ? 'LIVE' : 'none/unknown');

if ($mode === 'TEST') {
    line('OK', 'Razorpay key is a TEST key', 'safe to book repeatedly');
} elseif ($mode === 'LIVE') {
    line('FAIL', 'Razorpay key is a LIVE key', 'a test booking would charge a real card');
} else {
    line('WARN', 'No Razorpay key', 'paste one into config.local.php');
}

cfg('razorpay.enabled')
    ? line($mode === 'TEST' ? 'OK' : 'WARN', 'Razorpay switched on', 'mode: ' . $mode)
    : line('WARN', 'Razorpay switched off', 'guests cannot pay online yet');

cfg('razorpay.webhook_secret')
    ? line('OK', 'Webhook secret set')
    : line('WARN', 'Webhook secret missing', 'webhooks rejected; set before real guests');

if (upi_enabled()) {
    $vpa = (string) cfg('upi.vpa');
    str_contains($vpa, 'test')
        ? line('WARN', 'UPI QR uses a placeholder id', $vpa . ' — a scan will fail')
        : line('OK', 'UPI QR configured', $vpa);
} else {
    line('OK', 'UPI QR switched off');
}

head('CHANNEL MANAGER');

channel_enabled()
    ? line('OK', 'Stayflexi connected')
    : line('FAIL', 'Stayflexi NOT connected', 'rooms can be sold twice once payments go live');

$unmapped = (int) qval("SELECT COUNT(*) FROM room_types WHERE sf_room_type_id IS NULL OR sf_room_type_id = ''", [], 0);
$unmapped
    ? line('WARN', 'Room mapping incomplete', "$unmapped room types have no Stayflexi id")
    : line('OK', 'Rooms mapped to Stayflexi');

head('BEFORE REAL GUESTS');

cfg('debug') ? line('WARN', 'Debug mode is on', 'shows error detail to guests') : line('OK', 'Debug off');
cfg('db.driver') === 'mysql'
    ? line('OK', 'Running on MySQL')
    : line('WARN', 'Running on SQLite', 'fine for testing; cPanel will use MySQL');
str_contains((string) cfg('base_url'), 'trycloudflare')
    ? line('WARN', 'Temporary tunnel address', 'dies when the Mac sleeps')
    : line('OK', 'Stable address', (string) cfg('base_url'));
qval("SELECT id FROM admin_users LIMIT 1") ? line('OK', 'Staff login exists') : line('FAIL', 'No staff login');

$live = (int) qval("SELECT COUNT(*) FROM bookings WHERE status IN ('pending','confirmed')", [], 0);
line('OK', 'Bookings currently in the system', (string) $live);

echo "\n" . str_repeat('=', 72) . "\n";
printf("  %d passed    %d warnings    %d blocking\n\n", $counts['ok'], $counts['warn'], $counts['fail']);
