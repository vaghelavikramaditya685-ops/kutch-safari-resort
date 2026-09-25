<?php
/* Razorpay calls this server-to-server. It is the reliable confirmation —
   the browser callback can be lost if the guest closes the tab. */
require_once __DIR__ . '/../lib/payment.php';
header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$sig = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

if (!razorpay_verify_webhook($raw, $sig)) {
    audit('webhook_bad_signature', null, null, ['ip' => $_SERVER['REMOTE_ADDR'] ?? null]);
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit;
}

$event = json_decode($raw, true) ?: [];
$type  = $event['event'] ?? '';
$pay   = $event['payload']['payment']['entity'] ?? [];

audit('webhook_received', 'payment', $pay['id'] ?? null, ['event' => $type]);

if ($type === 'payment.captured' && !empty($pay['order_id'])) {
    $row = q1("SELECT * FROM payments WHERE order_id = ?", [$pay['order_id']]);
    if ($row) settle_payment((int) $row['id'], $pay['id'], $pay);
}

if ($type === 'payment.failed' && !empty($pay['order_id'])) {
    exec_sql("UPDATE payments SET status = 'failed', raw_response = ? WHERE order_id = ? AND status <> 'paid'",
             [json_encode($pay), $pay['order_id']]);
}

http_response_code(200);
echo json_encode(['ok' => true]);
