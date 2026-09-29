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
 *  The booking test (create, look up, cancel) never touches real data:
 *    - on SQLite it runs on a temporary copy of the database, deleted after;
 *    - on MySQL it is skipped unless you pass --write-test (only for a fresh
 *      install with no real bookings yet). Nothing is ever charged.
 * ======================================================================== */

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../lib/db.php';

// Point the whole check at a throwaway copy before anything opens the database.
$scratch = null;
if (cfg('db.driver') === 'sqlite' && is_file((string) cfg('db.sqlite_path'))) {
    $scratch = tempnam(sys_get_temp_dir(), 'ksr-check-');
    copy((string) cfg('db.sqlite_path'), $scratch);
    $GLOBALS['CONFIG']['db']['sqlite_path'] = $scratch;
    register_shutdown_function(function () use ($scratch) { @unlink($scratch); });
}
$GLOBALS['CONFIG']['mail']['smtp']['enabled'] = false;   // no emails from a check
$write_test = $scratch !== null || in_array('--write-test', $argv ?? [], true);

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

// Test the first property that is on sale, with its own rooms, plans and extras.
$prop  = q1("SELECT * FROM properties WHERE active = 1 ORDER BY id LIMIT 1");
$rt    = $prop ? q1("SELECT * FROM room_types WHERE property_id = ? AND active = 1 ORDER BY sort_order, id LIMIT 1", [$prop['id']]) : null;
$plan  = $rt ? q1("SELECT * FROM rate_plans WHERE room_type_id = ? AND active = 1 ORDER BY sort_order, id LIMIT 1", [$rt['id']]) : null;
$addon = $prop ? q1("SELECT * FROM addons WHERE property_id = ? AND active = 1 AND price_type IN ('per_booking','per_night') ORDER BY id LIMIT 1", [$prop['id']]) : null;
if (!$prop || !$rt || !$plan) {
    line('FAIL', 'A property with rooms on sale', 'none found');
} else {
    // Dates in the property's season (or two months ahead), two nights.
    $in  = !empty($prop['season_start']) && $prop['season_start'] > date('Y-m-d') ? $prop['season_start'] : date('Y-m-d', strtotime('+60 days'));
    $out = date('Y-m-d', strtotime("$in +2 days"));
    line('OK', 'Checking', $prop['name'] . ', ' . $rt['name'] . ", $in to $out");

    $avail = search_availability($prop, $in, $out, 2, 0, 1);
    count($avail)
        ? line('OK', 'Availability search', count($avail) . ' room types offered')
        : line('FAIL', 'Availability search', 'returned nothing');

    $cart = ['property' => $prop['code'], 'check_in' => $in, 'check_out' => $out, 'occupancy' => [2],
             'rooms' => [['room_type_id' => (int) $rt['id'], 'rate_plan_id' => (int) $plan['id'], 'rooms' => 1]],
             'addons' => $addon ? [['addon_id' => (int) $addon['id'], 'quantity' => 1]] : [], 'payment_mode' => 'advance'];
    $quote = quote_cart($cart);
    $quote['ok']
        ? line('OK', 'Quoting, tax and add-ons', 'total ' . $quote['total'] . ', due now ' . $quote['amount_due_now'])
        : line('FAIL', 'Quoting', $quote['error']);

    // GST slabs: a price above the 7,500 line must be taxed differently from one below it.
    $low = tax_split(5000, prices_include_tax($prop)); $high = tax_split(9000, prices_include_tax($prop));
    round($low[1] / $low[0] * 100) !== round($high[1] / $high[0] * 100)
        ? line('OK', 'GST slabs applied per night', round($low[1] / $low[0] * 100) . '% and ' . round($high[1] / $high[0] * 100) . '%')
        : line('WARN', 'GST slabs', 'the same rate either side of 7,500');

    if ($write_test) {
        $made = create_booking(['payment_mode' => 'full'] + $cart, ['name' => 'System Check', 'phone' => '9000000001', 'email' => 'check@example.com']);
        if ($made['ok']) {
            line('OK', 'Creating a booking', $made['ref'] . ($scratch ? ' (temporary copy)' : ''));
            line(find_booking($made['ref'], '9000000001') ? 'OK' : 'FAIL', 'Guest lookup by reference + phone');
            $cancelled = cancel_booking((int) $made['booking_id'], 'automated system check', 'check');
            $cancelled['ok']
                ? line('OK', 'Cancellation and refund rules', $cancelled['charge_percent'] . '% charge applied')
                : line('FAIL', 'Cancellation', $cancelled['error']);
        } else {
            line('FAIL', 'Creating a booking', $made['error']);
        }
    } else {
        line('WARN', 'Booking test skipped', 'MySQL: run with --write-test only before real bookings exist');
    }

    $over = quote_cart(['rooms' => [['room_type_id' => (int) $rt['id'], 'rate_plan_id' => (int) $plan['id'], 'rooms' => (int) $rt['total_rooms'] + 1]],
                        'occupancy' => array_fill(0, (int) $rt['total_rooms'] + 1, 2), 'staff_edit' => true] + $cart);
    !$over['ok'] && stripos((string) $over['error'], 'property') === false
        ? line('OK', 'Overbooking refused', substr((string) $over['error'], 0, 34))
        : line('FAIL', 'Overbooking NOT refused', $over['ok'] ? 'this would oversell rooms' : $over['error']);
}

// Season limits, if any property on sale is seasonal.
$seasonal = q1("SELECT * FROM properties WHERE active = 1 AND season_start IS NOT NULL AND season_start <> '' LIMIT 1");
if ($seasonal) {
    $outside = date('Y-m-d', strtotime($seasonal['season_end'] . ' +30 days'));
    validate_dates($seasonal, $outside, date('Y-m-d', strtotime("$outside +2 days")))
        ? line('OK', 'Season limits enforced', $seasonal['name'])
        : line('FAIL', 'Season limits', 'out-of-season dates were accepted');
} else {
    line('OK', 'Season limits', 'no seasonal property on sale');
}

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

$live = (int) qval("SELECT COUNT(*) FROM bookings WHERE status IN ('pending','confirmed') AND guest_name <> 'System Check'", [], 0);
line('OK', 'Bookings currently in the system', (string) $live);

echo "\n" . str_repeat('=', 72) . "\n";
printf("  %d passed    %d warnings    %d blocking\n\n", $counts['ok'], $counts['warn'], $counts['fail']);
