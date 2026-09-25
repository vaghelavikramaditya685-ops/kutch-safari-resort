<?php
/* A guest retrieving their booking: ref + phone or email. */
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../lib/booking.php';
rate_limit('lookup', 15, 300);

$b = find_booking(in_str('ref'), in_str('contact'));
if (!$b) json_fail('We could not find a booking with those details.', 404);

json_out(['ok' => true, 'booking' => [
    'ref' => $b['ref'], 'status' => $b['status'],
    'property' => $b['property_name'], 'property_phone' => $b['property_phone'],
    'check_in' => $b['check_in'], 'check_out' => $b['check_out'], 'nights' => (int) $b['nights'],
    'adults' => (int) $b['adults'], 'children' => (int) $b['children'],
    'guest_name' => $b['guest_name'],
    'rooms' => array_map(fn($r) => [
        'name' => $r['room_type_name'], 'plan' => $r['rate_plan_name'],
        'rooms' => (int) $r['rooms'], 'subtotal' => (float) $r['subtotal']], $b['rooms']),
    'addons' => array_map(fn($a) => [
        'name' => $a['addon_name'], 'quantity' => (int) $a['quantity'],
        'subtotal' => (float) $a['subtotal']], $b['addons']),
    'total' => (float) $b['total'],
    'amount_paid' => (float) $b['amount_paid'],
    'balance' => round((float) $b['total'] - (float) $b['amount_paid'], 2),
    'payment_mode' => $b['payment_mode'],
    'cancellation' => cancellation_schedule($b['check_in'], (float) $b['total']),
    'can_cancel' => $b['status'] !== 'cancelled' && $b['check_in'] >= date('Y-m-d'),
    'manage_token' => $b['manage_token'],
]]);
