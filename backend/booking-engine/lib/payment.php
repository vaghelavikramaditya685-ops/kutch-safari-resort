<?php
/* ===========================================================================
 *  Payments — Razorpay, and a direct UPI QR to the hotel's own bank account.
 *
 *  THE DIFFERENCE, IN ONE PARAGRAPH
 *  ---------------------------------------------------------------------------
 *  Razorpay tells us the moment a payment succeeds, so the booking confirms
 *  itself and the room is closed on Stayflexi within seconds. A direct UPI QR
 *  pays your bank account with no gateway fee, but nothing tells this website
 *  the money arrived — so the booking waits as 'awaiting payment' until a
 *  member of staff checks the bank and marks it received in the admin panel.
 *  Both are offered; Razorpay is the default because it confirms instantly.
 * ======================================================================== */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/booking.php';
require_once __DIR__ . '/channel.php';

/* =====================================================================
 * RAZORPAY
 * ================================================================== */

function razorpay_enabled(): bool {
    return (bool) cfg('razorpay.enabled', false) && cfg('razorpay.key_id') && cfg('razorpay.key_secret');
}

function rzp_request(string $method, string $path, ?array $payload = null): array {
    $ch = curl_init('https://api.razorpay.com/v1/' . ltrim($path, '/'));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_USERPWD        => cfg('razorpay.key_id') . ':' . cfg('razorpay.key_secret'),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 20,
    ]);
    curl_trust_system_certs($ch);
    if ($payload !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $body   = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);

    $decoded = json_decode($body ?: '', true) ?? [];
    return [$status >= 200 && $status < 300, $status, $decoded];
}

/**
 * Create a Razorpay order for whatever is payable today.
 * Amounts go to Razorpay in paise, so ₹6,250.00 becomes 625000.
 */
function razorpay_create_order(int $booking_id): array {
    if (!razorpay_enabled()) return ['ok' => false, 'error' => 'Online payment is not switched on yet.'];

    $b = get_booking($booking_id);
    if (!$b) return ['ok' => false, 'error' => 'Booking not found.'];

    $amount = (float) $b['amount_due_now'];
    if ($amount <= 0) return ['ok' => false, 'error' => 'Nothing is payable online for this booking.'];

    [$ok, $status, $res] = rzp_request('POST', 'orders', [
        'amount'   => (int) round($amount * 100),
        'currency' => cfg('razorpay.currency', 'INR'),
        'receipt'  => $b['ref'],
        'notes'    => [
            'booking_ref' => $b['ref'],
            'property'    => $b['property_name'],
            'check_in'    => $b['check_in'],
            'check_out'   => $b['check_out'],
            'guest'       => $b['guest_name'],
        ],
    ]);

    if (!$ok) {
        audit('razorpay_order_failed', 'booking', $booking_id, $res);
        return ['ok' => false, 'error' => 'We could not start the payment. Please try again or call us.'];
    }

    insert('payments', [
        'booking_id' => $booking_id,
        'provider'   => 'razorpay',
        'purpose'    => 'booking',
        'order_id'   => $res['id'],
        'amount'     => $amount,
        'status'     => 'created',
        'raw_response' => json_encode($res),
        'created_at' => now(),
    ]);
    audit('razorpay_order_created', 'booking', $booking_id, ['order' => $res['id'], 'amount' => $amount]);

    return [
        'ok'       => true,
        'order_id' => $res['id'],
        'amount'   => $amount,
        'amount_paise' => (int) round($amount * 100),
        'currency' => cfg('razorpay.currency', 'INR'),
        'key_id'   => cfg('razorpay.key_id'),
        'prefill'  => [
            'name'    => $b['guest_name'],
            'email'   => (string) $b['guest_email'],
            'contact' => $b['guest_phone'],
        ],
        'booking_ref' => $b['ref'],
    ];
}

/**
 * Verify the signature the browser hands back after checkout.
 * Razorpay signs order_id|payment_id with your key secret.
 */
function razorpay_verify_signature(string $order_id, string $payment_id, string $signature): bool {
    $expected = hash_hmac('sha256', $order_id . '|' . $payment_id, cfg('razorpay.key_secret'));
    return hash_equals($expected, $signature);
}

/** Called from the browser once checkout reports success. */
function razorpay_confirm(string $order_id, string $payment_id, string $signature): array {
    $pay = q1("SELECT * FROM payments WHERE order_id = ?", [$order_id]);
    if (!$pay) return ['ok' => false, 'error' => 'We do not recognise that payment.'];

    if (!razorpay_verify_signature($order_id, $payment_id, $signature)) {
        update('payments', (int) $pay['id'], ['status' => 'failed', 'raw_response' => 'signature mismatch']);
        audit('razorpay_signature_mismatch', 'payment', $pay['id']);
        return ['ok' => false, 'error' => 'That payment could not be verified. You have not been charged twice — please call us.'];
    }

    // Trust the API over the browser: ask Razorpay what really happened.
    [$ok, $status, $res] = rzp_request('GET', 'payments/' . $payment_id);
    if (!$ok || ($res['status'] ?? '') !== 'captured') {
        // Authorised-but-not-captured still counts as money taken; capture it.
        if ($ok && ($res['status'] ?? '') === 'authorized') {
            [$cok, , $cres] = rzp_request('POST', 'payments/' . $payment_id . '/capture',
                ['amount' => $res['amount'], 'currency' => $res['currency']]);
            if ($cok) $res = $cres;
        }
        if (($res['status'] ?? '') !== 'captured') {
            update('payments', (int) $pay['id'], ['status' => 'failed', 'raw_response' => json_encode($res)]);
            return ['ok' => false, 'error' => 'The payment did not complete. Please try again.'];
        }
    }

    return settle_payment((int) $pay['id'], $payment_id, $res);
}

/** Shared by the browser callback and the webhook, so either can confirm. */
function settle_payment(int $payment_row_id, string $payment_id, array $res): array {
    $pay = q1("SELECT * FROM payments WHERE id = ?", [$payment_row_id]);
    if (!$pay) return ['ok' => false, 'error' => 'Unknown payment.'];

    // Idempotent: the webhook and the browser often both arrive.
    if ($pay['status'] === 'paid') {
        return ['ok' => true, 'already' => true, 'booking_id' => (int) $pay['booking_id']];
    }

    update('payments', $payment_row_id, [
        'payment_id'   => $payment_id,
        'method'       => $res['method'] ?? null,
        'status'       => 'paid',
        'raw_response' => json_encode($res),
        'paid_at'      => now(),
    ]);

    $booking_id = (int) $pay['booking_id'];
    // Payments minus any refunds, counted the same way everywhere (refresh_amount_paid).
    $paid = refresh_amount_paid($booking_id);
    $b = get_booking($booking_id);

    update('bookings', $booking_id, [
        'status'      => 'confirmed',
        'updated_at'  => now(),
    ]);
    audit('payment_settled', 'booking', $booking_id, ['payment' => $payment_id, 'paid' => $paid]);

    // Now the money is real, close the room on every OTA.
    $push = channel_push_booking($booking_id);

    require_once __DIR__ . '/mail.php';
    send_booking_confirmation($booking_id);

    return ['ok' => true, 'booking_id' => $booking_id, 'channel' => $push];
}

/** Webhooks are signed over the whole raw body. */
function razorpay_verify_webhook(string $raw_body, string $signature): bool {
    $secret = cfg('razorpay.webhook_secret');
    if (!$secret) return false;
    return hash_equals(hash_hmac('sha256', $raw_body, $secret), $signature);
}

/** Refund through Razorpay after a cancellation. */
function razorpay_refund(int $booking_id, float $amount, string $reason = ''): array {
    if (!razorpay_enabled()) return ['ok' => false, 'error' => 'Razorpay is not switched on.'];
    $pay = q1("SELECT * FROM payments WHERE booking_id = ? AND status = 'paid' AND provider = 'razorpay'
                ORDER BY id DESC LIMIT 1", [$booking_id]);
    if (!$pay) return ['ok' => false, 'error' => 'No online payment found to refund.'];

    [$ok, , $res] = rzp_request('POST', 'payments/' . $pay['payment_id'] . '/refund', [
        'amount' => (int) round($amount * 100),
        'notes'  => ['reason' => $reason ?: 'Booking cancelled'],
    ]);
    if (!$ok) return ['ok' => false, 'error' => 'Refund failed at Razorpay.', 'body' => $res];

    insert('payments', [
        'booking_id' => $booking_id,
        'provider'   => 'razorpay',
        'purpose'    => 'refund',
        'payment_id' => $res['id'] ?? null,
        'amount'     => -1 * $amount,
        'status'     => 'refunded',
        'raw_response' => json_encode($res),
        'created_at' => now(),
        'paid_at'    => now(),
    ]);
    audit('refund_issued', 'booking', $booking_id, ['amount' => $amount]);
    return ['ok' => true, 'refund' => $res];
}

/* =====================================================================
 * DIRECT UPI QR
 * ================================================================== */

/** Testing only (config test_payments.enabled): confirm a booking as paid with no money taken. */
function test_payments_enabled(): bool {
    return (bool) cfg('test_payments.enabled', false);
}

/**
 * "I've paid (test)". Settles the booking through settle_payment() — the same
 * path a real Razorpay payment takes — so inventory, status, the admin panel
 * and the confirmation email all behave as they will for real. The booking's
 * manage_token is required, so only the guest who made it can do this.
 */
function test_payment_settle(int $booking_id, string $manage_token): array {
    if (!test_payments_enabled()) return ['ok' => false, 'error' => 'Test payments are switched off.'];

    $b = get_booking($booking_id);
    if (!$b || !hash_equals((string) $b['manage_token'], $manage_token)) {
        return ['ok' => false, 'error' => 'Booking not found.'];
    }
    if ($b['status'] === 'cancelled') return ['ok' => false, 'error' => 'This booking was cancelled.'];

    $amount = (float) $b['amount_due_now'];
    if ($amount <= 0) return ['ok' => false, 'error' => 'Nothing is payable for this booking.'];

    $row = insert('payments', [
        'booking_id' => $booking_id,
        'provider'   => 'test',
        'purpose'    => 'booking',
        'method'     => 'test',
        'amount'     => $amount,
        'status'     => 'created',
        'created_at' => now(),
    ]);
    audit('test_payment', 'booking', $booking_id, ['amount' => $amount]);
    return settle_payment($row, 'TEST-' . $b['ref'], ['method' => 'test', 'note' => 'Test payment — no money taken']);
}

function upi_enabled(): bool {
    return (bool) cfg('upi.enabled', false) && cfg('upi.vpa');
}

/**
 * Build the UPI intent string. Any Indian UPI app can read this from a QR:
 *   upi://pay?pa=<vpa>&pn=<payee>&am=<amount>&cu=INR&tn=<note>&tr=<ref>
 * The browser renders it as a QR — no image library needed on the server.
 */
function upi_payment_request(int $booking_id): array {
    if (!upi_enabled()) return ['ok' => false, 'error' => 'UPI payment is not configured yet.'];

    $b = get_booking($booking_id);
    if (!$b) return ['ok' => false, 'error' => 'Booking not found.'];

    $amount = (float) $b['amount_due_now'];
    if ($amount <= 0) return ['ok' => false, 'error' => 'Nothing is payable for this booking.'];

    // The transaction ref is what ties the bank statement line to the booking.
    $tr = preg_replace('/[^A-Za-z0-9]/', '', $b['ref']) . substr((string) time(), -4);

    $intent = 'upi://pay?' . http_build_query([
        'pa' => cfg('upi.vpa'),
        'pn' => cfg('upi.payee_name'),
        'am' => number_format($amount, 2, '.', ''),
        'cu' => 'INR',
        'tn' => 'Booking ' . $b['ref'],
        'tr' => $tr,
    ], '', '&', PHP_QUERY_RFC3986);

    insert('payments', [
        'booking_id' => $booking_id,
        'provider'   => 'upi_qr',
        'purpose'    => 'booking',
        'upi_ref'    => $tr,
        'amount'     => $amount,
        'status'     => 'awaiting_confirmation',
        'created_at' => now(),
    ]);
    audit('upi_qr_issued', 'booking', $booking_id, ['ref' => $tr, 'amount' => $amount]);

    return [
        'ok'          => true,
        'intent'      => $intent,
        'vpa'         => cfg('upi.vpa'),
        'payee'       => cfg('upi.payee_name'),
        'amount'      => $amount,
        'reference'   => $tr,
        'booking_ref' => $b['ref'],
        'expires_in'  => (int) cfg('upi.hold_minutes', 45) * 60,
        'instructions'=> 'Scan with any UPI app, pay the exact amount, and keep the reference number. '
                       . 'We confirm your booking as soon as the payment shows in our account, usually within the hour.',
    ];
}

/**
 * A staff member has seen the money in the bank and is confirming it.
 * This is the only way a direct UPI payment ever becomes 'paid'.
 */
function upi_mark_received(int $payment_id, string $staff_name, string $bank_ref = ''): array {
    $pay = q1("SELECT * FROM payments WHERE id = ? AND provider = 'upi_qr'", [$payment_id]);
    if (!$pay) return ['ok' => false, 'error' => 'UPI payment not found.'];
    if ($pay['status'] === 'paid') return ['ok' => false, 'error' => 'Already marked as received.'];

    update('payments', $payment_id, [
        'status'       => 'paid',
        'verified_by'  => $staff_name,
        'raw_response' => json_encode(['bank_reference' => $bank_ref, 'confirmed_by' => $staff_name]),
        'paid_at'      => now(),
    ]);

    $booking_id = (int) $pay['booking_id'];
    $paid = refresh_amount_paid($booking_id);   // payments minus any refunds
    update('bookings', $booking_id, ['status' => 'confirmed', 'updated_at' => now()]);

    audit('upi_confirmed', 'booking', $booking_id, ['payment' => $payment_id, 'bank_ref' => $bank_ref], $staff_name);
    channel_push_booking($booking_id);

    require_once __DIR__ . '/mail.php';
    send_booking_confirmation($booking_id);

    return ['ok' => true, 'booking_id' => $booking_id, 'paid' => $paid];
}

/** Record cash/card taken at the property, or a bank transfer. */
function record_offline_payment(int $booking_id, float $amount, string $method, string $staff_name, string $note = ''): array {
    insert('payments', [
        'booking_id'  => $booking_id,
        'provider'    => 'offline',
        'purpose'     => 'balance',
        'method'      => $method,
        'amount'      => $amount,
        'status'      => 'paid',
        'verified_by' => $staff_name,
        'raw_response'=> json_encode(['note' => $note]),
        'created_at'  => now(),
        'paid_at'     => now(),
    ]);
    $paid = refresh_amount_paid($booking_id);
    audit('offline_payment', 'booking', $booking_id, ['amount' => $amount, 'method' => $method], $staff_name);

    // A booking still waiting for payment is confirmed once what it needs now is paid
    // (all of it, or the 50% advance) — the same as an online payment: status,
    // Stayflexi and the confirmation email.
    $b = get_booking($booking_id);
    if ($b && $b['status'] === 'pending' && booking_money($b)['due_now'] <= 0.5) {
        update('bookings', $booking_id, ['status' => 'confirmed', 'updated_at' => now()]);
        audit('payment_settled', 'booking', $booking_id, ['payment' => 'offline', 'paid' => $paid], $staff_name);
        channel_push_booking($booking_id);
        require_once __DIR__ . '/mail.php';
        send_booking_confirmation($booking_id);
        return ['ok' => true, 'paid' => $paid, 'confirmed' => true];
    }
    return ['ok' => true, 'paid' => $paid];
}

/** Money given back at the property after a change made the stay cheaper. */
function record_offline_refund(int $booking_id, float $amount, string $method, string $staff_name, string $note = ''): array {
    insert('payments', [
        'booking_id'  => $booking_id,
        'provider'    => 'offline',
        'purpose'     => 'refund',
        'method'      => $method,
        'amount'      => -1 * abs($amount),
        'status'      => 'refunded',
        'verified_by' => $staff_name,
        'raw_response'=> json_encode(['note' => $note]),
        'created_at'  => now(),
        'paid_at'     => now(),
    ]);
    $paid = refresh_amount_paid($booking_id);
    audit('offline_refund', 'booking', $booking_id, ['amount' => $amount, 'method' => $method], $staff_name);
    return ['ok' => true, 'paid' => $paid];
}
