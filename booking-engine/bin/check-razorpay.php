<?php
/* ===========================================================================
 *  Check that your Razorpay keys work — without ever printing the secret.
 *
 *      php bin/check-razorpay.php
 *
 *  It reads the keys from config.local.php, asks Razorpay to create a ₹1 order,
 *  and tells you whether the credentials were accepted. Creating an order costs
 *  nothing and charges nobody; it is just the cheapest way to prove the key and
 *  secret are a matching, working pair.
 * ======================================================================== */

require_once __DIR__ . '/../lib/payment.php';

function mask(?string $s): string {
    if (!$s) return '(not set)';
    return strlen($s) <= 10 ? str_repeat('•', strlen($s))
         : substr($s, 0, 8) . str_repeat('•', max(4, strlen($s) - 12)) . substr($s, -4);
}

$key    = (string) cfg('razorpay.key_id');
$secret = (string) cfg('razorpay.key_secret');
$hook   = (string) cfg('razorpay.webhook_secret');

echo "\nRazorpay configuration\n----------------------\n";
printf("  enabled        : %s\n", cfg('razorpay.enabled') ? 'yes' : 'NO — set razorpay.enabled to true');
printf("  key id         : %s\n", mask($key));
printf("  key secret     : %s\n", $secret ? 'set (' . strlen($secret) . ' characters)' : '(not set)');
printf("  webhook secret : %s\n", $hook ? 'set' : '(not set — webhooks will be rejected)');

if (!$key || !$secret) {
    echo "\nAdd both keys to config.local.php, then run this again.\n\n";
    exit(1);
}

$mode = str_starts_with($key, 'rzp_live_') ? 'LIVE' : (str_starts_with($key, 'rzp_test_') ? 'TEST' : 'UNKNOWN');
printf("  mode           : %s\n", $mode);
if ($mode === 'LIVE') {
    echo "\n  ! These are LIVE keys. Real cards will be charged real money.\n";
    echo "    Test with rzp_test_ keys first if you have not already.\n";
}
if ($mode === 'UNKNOWN') {
    echo "\n  ! That key id does not start with rzp_test_ or rzp_live_ — check you copied the Key Id,\n";
    echo "    not the Key Secret or the webhook secret.\n";
}

echo "\nTalking to Razorpay ...\n";
[$ok, $status, $res] = rzp_request('POST', 'orders', [
    'amount'   => 100,                      // ₹1, never paid — just proves the key works
    'currency' => 'INR',
    'receipt'  => 'key-check-' . date('YmdHis'),
    'notes'    => ['purpose' => 'credential check from the booking engine'],
]);

if ($ok) {
    echo "  SUCCESS — Razorpay accepted your credentials.\n";
    printf("  test order id  : %s\n", $res['id'] ?? '?');
    echo "\n  Nothing was charged. The order simply expires unpaid.\n";
    echo "  Online payment is ready. Make one real booking end to end before going live.\n\n";
    exit(0);
}

echo "  FAILED — Razorpay refused the request (HTTP $status).\n";
$desc = $res['error']['description'] ?? null;
if ($desc) echo "  Razorpay says: $desc\n";

echo "\n  Most likely causes:\n";
if ($status === 401) {
    echo "   • The key id and key secret do not match, or one was copied with a stray space.\n";
    echo "   • A test key id paired with a live secret (or the other way round).\n";
} elseif ($status === 400) {
    echo "   • The account is not activated yet — finish KYC in the Razorpay dashboard.\n";
} else {
    echo "   • No internet connection, or Razorpay is unreachable from this machine.\n";
}
echo "\n";
exit(1);
