<?php
/* Start a payment. POST: booking_id, method = razorpay | upi_qr */
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../lib/payment.php';
require_post();
rate_limit('pay', 30, 300);

$booking_id = in_int('booking_id');
$method     = in_str('method', 'razorpay');
if (!$booking_id) json_fail('Which booking?');

json_out($method === 'upi_qr'
    ? upi_payment_request($booking_id)
    : razorpay_create_order($booking_id));
