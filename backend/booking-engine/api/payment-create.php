<?php
/* Start a payment. POST: booking_id, manage_token (the guest's private code), method = razorpay | upi_qr */
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../lib/payment.php';
require_post();
rate_limit('pay', 30, 300);

$booking_id = in_int('booking_id');
$method     = in_str('method', 'razorpay');
if (!$booking_id) json_fail('Which booking?');

// Only the guest who made the booking (who holds its private code) can start a
// payment for it, and only while it is still waiting for payment. Otherwise
// anyone could guess booking numbers and leave strangers' bookings "waiting for
// UPI", which holds their rooms.
$b = get_booking($booking_id);
if (!$b || !hash_equals((string) $b['manage_token'], in_str('manage_token', ''))) json_fail('Booking not found.');
if ($b['status'] !== 'pending') json_fail($b['status'] === 'confirmed' ? 'This booking is already paid.' : 'This booking was cancelled.');

json_out($method === 'upi_qr'
    ? upi_payment_request($booking_id)
    : razorpay_create_order($booking_id));
