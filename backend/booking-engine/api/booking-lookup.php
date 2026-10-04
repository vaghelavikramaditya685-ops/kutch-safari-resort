<?php
/* A guest retrieving their booking: ref + phone or email (POST, so the phone or
   email never sits in an address, a server log or the browser history). */
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../lib/booking.php';
rate_limit('lookup', 15, 300);

// Reference + mobile/email, or reference + the booking's private token (the
// "Check status" link on the confirmation and after a refresh).
$b = in_str('token') !== ''
   ? find_booking_by_token(in_str('ref'), in_str('token'))
   : find_booking(in_str('ref'), in_str('contact'));
// A mistyped code or phone is an ordinary answer, not a server error: a 404 status made
// the browser log "Failed to load resource" every time. manage.php reads ok/error.
if (!$b) json_fail('We could not find a booking with those details.', 200);

$money = booking_money($b);
$today = date('Y-m-d');
$status = booking_lapsed($b) ? 'not paid'
        : ($b['status'] === 'confirmed' && $b['check_out'] <= $today ? 'checked out'
        : (['no_show' => 'no-show', 'completed' => 'checked out'][$b['status']] ?? $b['status']));
$cm = $b['status'] === 'cancelled' ? cancellation_money($b) : null;

json_out(['ok' => true, 'booking' => [
    'ref' => $b['ref'], 'status' => $status,
    'property' => $b['property_name'], 'property_phone' => $b['property_phone'],
    'property_code' => $b['property_code'],
    'check_in' => $b['check_in'], 'check_out' => $b['check_out'], 'nights' => (int) $b['nights'],
    'adults' => (int) $b['adults'], 'children' => (int) $b['children'],
    'guest_name' => $b['guest_name'],
    'rooms' => array_map(fn($r) => [
        'name' => $r['room_type_name'], 'plan' => $r['rate_plan_name'],
        'rooms' => (int) $r['rooms'], 'subtotal' => (float) $r['subtotal'],
        'tax_amount' => (float) $r['tax_amount']], $b['rooms']),
    'addons' => array_map(fn($a) => [
        'name' => $a['addon_name'], 'quantity' => (int) $a['quantity'],
        'subtotal' => (float) $a['subtotal'], 'tax_amount' => (float) $a['tax_amount']], $b['addons']),
    'tax_amount' => (float) $b['tax_amount'],
    'prices_include_tax' => prices_include_tax(['code' => $b['property_code']]),
    'total' => (float) $b['total'],
    'amount_paid' => (float) $b['amount_paid'],
    'balance' => round((float) $b['total'] - (float) $b['amount_paid'], 2),
    // Split by how the guest chose to pay: 50% advance leaves the rest for check-in.
    'due_now' => $money['due_now'],
    'due_later' => $money['later'],
    'arrived' => $money['arrived'],      // once the stay has started, what is unpaid is paid at the desk
    // After the desk changes a booking to something cheaper, money is owed back.
    'refund_due' => $money['refund'],
    // A cancelled booking: when, what the cancellation kept, and what it gives back.
    'cancelled_at' => $b['status'] === 'cancelled' ? $b['cancelled_at'] : null,
    'cancellation_charge' => $cm ? $cm['kept'] : 0,
    'cancellation_percent' => $cm ? $cm['percent'] : null,
    'refund_amount' => $cm ? $cm['refund'] : 0,
    'refund_given' => $cm ? $cm['given'] : 0,
    'refund_outstanding' => $cm ? $cm['outstanding'] : 0,
    'modified_at' => booking_modified_at((int) $b['id']),
    'payment_mode' => $b['payment_mode'],
    'cancellation' => cancellation_schedule($b['check_in'], (float) $b['total']),
    'can_cancel' => in_array($b['status'], ['pending', 'confirmed'], true) && $b['check_in'] >= $today,
    'manage_token' => $b['manage_token'],
]]);
