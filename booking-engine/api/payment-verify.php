<?php
/* Razorpay checkout finished in the browser. Verify and confirm. */
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../lib/payment.php';
require_post();
rate_limit('verify', 30, 300);

$res = razorpay_confirm(
    in_str('razorpay_order_id'),
    in_str('razorpay_payment_id'),
    in_str('razorpay_signature'));

if ($res['ok']) {
    $b = get_booking((int) $res['booking_id']);
    $res['ref'] = $b['ref'];
    $res['manage_token'] = $b['manage_token'];
}
json_out($res);
