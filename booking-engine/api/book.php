<?php
/* Create the booking. POST: cart fields + guest {}. */
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../lib/booking.php';
require_once __DIR__ . '/../lib/payment.php';
require_post();
rate_limit('book', 12, 300);

$cart  = input();
$guest = is_array($cart['guest'] ?? null) ? $cart['guest'] : [];

$res = create_booking($cart, $guest);
if (!$res['ok']) json_out($res, 200);

$booking_id = (int) $res['booking_id'];
$mode = $res['quote']['payment_mode'];

$out = [
    'ok'          => true,
    'booking_id'  => $booking_id,
    'ref'         => $res['ref'],
    'manage_token'=> $res['manage_token'],
    'payment_mode'=> $mode,
    'amount_due_now' => $res['quote']['amount_due_now'],
    'total'       => $res['quote']['total'],
];

if ($mode === 'hotel') {
    // Nothing to pay online — it is already confirmed.
    require_once __DIR__ . '/../lib/mail.php';
    send_booking_confirmation($booking_id);
    $out['status'] = 'confirmed';
    $out['next']   = 'done';
} else {
    $out['status'] = 'pending';
    $out['next']   = 'payment';
    $out['methods'] = [
        'razorpay' => razorpay_enabled(),
        'upi_qr'   => upi_enabled(),
    ];
}
json_out($out);
