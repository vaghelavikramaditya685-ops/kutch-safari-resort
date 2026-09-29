<?php
/* TESTING ONLY: "I've paid (test)". Confirms the booking with no money taken.
   POST: booking_id, manage_token. Refused unless config test_payments.enabled. */
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../lib/booking.php';
require_once __DIR__ . '/../lib/payment.php';
require_post();
rate_limit('pay_test', 30, 300);

$res = test_payment_settle(in_int('booking_id'), in_str('manage_token'));
if ($res['ok']) {
    $b = get_booking((int) $res['booking_id']);
    $res['ref'] = $b['ref'];
    $res['manage_token'] = $b['manage_token'];
}
json_out($res);
