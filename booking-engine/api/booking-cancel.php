<?php
/* Guest cancels. Needs the ref and the token they got by email, so a
   reference alone is not enough to cancel someone else's stay. */
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../lib/booking.php';
require_once __DIR__ . '/../lib/payment.php';
require_post();
rate_limit('cancel', 10, 600);

$ref   = strtoupper(in_str('ref'));
$token = in_str('manage_token');
$b = q1("SELECT * FROM bookings WHERE ref = ?", [$ref]);
if (!$b || !hash_equals($b['manage_token'], $token)) json_fail('We could not verify that booking.', 403);

$res = cancel_booking((int) $b['id'], in_str('reason'), 'guest');

// Put the refund through automatically when money was taken by card.
if ($res['ok'] && $res['refund_due'] > 0 && razorpay_enabled()) {
    $res['refund'] = razorpay_refund((int) $b['id'], (float) $res['refund_due'], 'Guest cancelled online');
}
json_out($res);
