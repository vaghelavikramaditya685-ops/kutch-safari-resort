<?php
/* A guest retrieving their booking: ref + phone or email. */
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

json_out(['ok' => true, 'booking' => [
    'ref' => $b['ref'], 'status' => booking_lapsed($b) ? 'not paid' : $b['status'],
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
    'due_now' => booking_money($b)['due_now'],
    'due_later' => booking_money($b)['later'],
    // After the desk changes a booking to something cheaper, money is owed back.
    'refund_due' => $b['status'] === 'cancelled' ? 0 : max(0, round((float) $b['amount_paid'] - (float) $b['total'], 2)),
    'modified_at' => booking_modified_at((int) $b['id']),
    'payment_mode' => $b['payment_mode'],
    'cancellation' => cancellation_schedule($b['check_in'], (float) $b['total']),
    'can_cancel' => $b['status'] !== 'cancelled' && $b['check_in'] >= date('Y-m-d'),
    'manage_token' => $b['manage_token'],
]]);
