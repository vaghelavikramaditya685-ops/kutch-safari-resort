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

/**
 * What a guest pays now, worked out from the booking as it is today: the full
 * amount, or the 50% advance, less anything already paid. Not the amount_due_now
 * stored at checkout: after the desk changed an unpaid 50% booking from ₹14,900
 * to ₹29,800, that still charged ₹7,450 and confirmed the booking.
 */
function payable_now(array $b): float {
    if ($b['status'] !== 'pending') return 0.0;
    return booking_money($b)['due_now'];
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

    $amount = payable_now($b);
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
        // A forged or garbled return never undoes a payment that is already confirmed.
        exec_sql("UPDATE payments SET status = 'failed', raw_response = 'signature mismatch' WHERE id = ? AND status <> 'paid'", [(int) $pay['id']]);
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
            exec_sql("UPDATE payments SET status = 'failed', raw_response = ? WHERE id = ? AND status <> 'paid'", [json_encode($res), (int) $pay['id']]);
            return ['ok' => false, 'error' => 'The payment did not complete. Please try again.'];
        }
    }

    return settle_payment((int) $pay['id'], $payment_id, $res);
}

/**
 * Shared by the browser callback, the webhook, test payments and the desk's
 * "Money received", so any of them can confirm. Marking the payment paid is one
 * conditional update: when the browser, the webhook and retries arrive at the
 * same moment, exactly one of them gets through, so the confirmation email and
 * the Stayflexi reservation happen once (they used to happen up to 15 times).
 * Money for a booking cancelled meanwhile is recorded and owed back in full;
 * the booking stays cancelled.
 */
function settle_payment(int $payment_row_id, string $payment_id, array $res, array $extra = []): array {
    $r = db_tx(function () use ($payment_row_id, $payment_id, $res, $extra) {
        $pay = q1("SELECT * FROM payments WHERE id = ?" . for_update(), [$payment_row_id]);
        if (!$pay) return ['ok' => false, 'error' => 'Unknown payment.'];
        $booking_id = (int) $pay['booking_id'];
        $b = lock_booking($booking_id);

        $changed = exec_sql("UPDATE payments SET payment_id = ?, method = ?, status = 'paid', raw_response = ?, paid_at = ?"
                          . ($extra ? ', ' . implode(', ', array_map(fn($k) => "$k = ?", array_keys($extra))) : '')
                          . " WHERE id = ? AND status IN ('created', 'awaiting_confirmation', 'failed')",
                            array_merge([$payment_id, $res['method'] ?? null, json_encode($res), now()], array_values($extra), [$payment_row_id]));
        if (!$changed) {
            // Already settled by another request, or withdrawn when the booking was cancelled.
            return $pay['status'] === 'paid'
                ? ['ok' => true, 'already' => true, 'booking_id' => $booking_id]
                : ['ok' => false, 'error' => 'This payment request was withdrawn when the booking was cancelled.', 'booking_id' => $booking_id];
        }

        // Payments minus any refunds, counted the same way everywhere (refresh_amount_paid).
        $paid = refresh_amount_paid($booking_id);
        if (in_array($b['status'], ['cancelled', 'no_show'], true)) {
            update('bookings', $booking_id, ['refund_amount' => money((float) $b['refund_amount'] + (float) $pay['amount']), 'updated_at' => now()]);
            audit('payment_after_cancel', 'booking', $booking_id, ['payment' => $payment_id, 'amount' => (float) $pay['amount']]);
            return ['ok' => false, 'booking_id' => $booking_id, 'after_cancel' => true, 'amount' => (float) $pay['amount'],
                    'error' => 'This booking was cancelled before the payment arrived. The ₹' . number_format((float) $pay['amount'], 2)
                             . ' paid is recorded and will be given back — please call us.'];
        }
        // Paid after the booking stopped holding its cottage (a card payment finished
        // after its window, a UPI payment confirmed days later) and someone else has
        // the cottage now: confirming would sell it twice. The money is recorded and
        // owed back in full, and the booking is cancelled.
        if ($b['status'] === 'pending' && !booking_rooms_still_free($b)) {
            update('bookings', $booking_id, [
                'status' => 'cancelled', 'cancelled_at' => now(), 'updated_at' => now(),
                'cancel_reason' => 'Paid after its hold ended; the cottage had been booked by someone else',
                'refund_amount' => money($paid),
            ]);
            exec_sql("UPDATE payments SET status = 'void' WHERE booking_id = ? AND provider = 'upi_qr' AND status = 'awaiting_confirmation'", [$booking_id]);
            audit('payment_no_room', 'booking', $booking_id, ['payment' => $payment_id, 'paid' => $paid]);
            return ['ok' => false, 'booking_id' => $booking_id, 'no_room' => true,
                    'error' => 'Sorry — the cottage was booked by someone else while this payment was being made. The ₹'
                             . number_format($paid, 2) . ' paid is recorded and will be given back in full — please call us.'];
        }
        $newly = $b['status'] === 'pending';
        if ($newly) update('bookings', $booking_id, ['status' => 'confirmed', 'updated_at' => now()]);
        audit('payment_settled', 'booking', $booking_id, ['payment' => $payment_id, 'paid' => $paid]);
        return ['ok' => true, 'booking_id' => $booking_id, 'paid' => $paid, 'newly_confirmed' => $newly];
    });
    if (empty($r['newly_confirmed'])) return $r;

    // Now the money is real, close the room on every OTA and tell the guest — once.
    $r['channel'] = channel_push_booking($r['booking_id']);
    require_once __DIR__ . '/mail.php';
    send_booking_confirmation($r['booking_id']);
    return $r;
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
    refresh_amount_paid($booking_id);   // payments minus refunds, as record_offline_refund() does
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

    // Checked and written under the lock: twenty presses at once (or a double click)
    // record one payment, not twenty. Only a booking still waiting for payment, for
    // what it still needs.
    $r = db_tx(function () use ($booking_id, $manage_token) {
        $b = lock_booking($booking_id);
        if (!$b || !hash_equals((string) $b['manage_token'], $manage_token)) {
            return ['ok' => false, 'error' => 'Booking not found.'];
        }
        if ($b['status'] === 'cancelled') return ['ok' => false, 'error' => 'This booking was cancelled.'];
        if ($b['status'] !== 'pending') return ['ok' => false, 'error' => 'This booking is already paid.'];
        $amount = payable_now($b);
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
        return ['ok' => true, 'row' => $row, 'ref' => $b['ref']];
    });
    if (!$r['ok']) return $r;
    return settle_payment($r['row'], 'TEST-' . $r['ref'], ['method' => 'test', 'note' => 'Test payment — no money taken']);
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

    $amount = payable_now($b);
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
    $pay = q1("SELECT p.*, b.status AS booking_status FROM payments p JOIN bookings b ON b.id = p.booking_id
                WHERE p.id = ? AND p.provider = 'upi_qr'", [$payment_id]);
    if (!$pay) return ['ok' => false, 'error' => 'UPI payment not found.'];
    if ($pay['status'] === 'paid') return ['ok' => false, 'error' => 'Already marked as received.'];
    // Cancelled meanwhile: confirming it would bring the booking back to life, hold
    // the cottage again (perhaps resold), tell Stayflexi and email the guest.
    if ($pay['status'] === 'void' || in_array($pay['booking_status'], ['cancelled', 'no_show'], true)) {
        return ['ok' => false, 'error' => 'This booking was cancelled, so its UPI request was withdrawn and it was not confirmed. '
                                       . 'If the money did reach the bank, give it back to the guest.'];
    }
    $r = settle_payment($payment_id, (string) ($pay['payment_id'] ?? ''), ['method' => 'upi'], [
        'verified_by' => $staff_name,
    ]);
    if (!empty($r['no_room'])) {
        return ['ok' => false, 'error' => 'The money is recorded, but the cottage had been booked by someone else after this QR stopped '
                                       . 'holding it, so the booking was cancelled. Give the guest their money back (open the booking to record the refund).'];
    }
    if (!$r['ok'] || !empty($r['already'])) {
        return !empty($r['already']) ? ['ok' => false, 'error' => 'Already marked as received.'] : $r;
    }
    // Keep the bank reference with the payment for the books.
    exec_sql("UPDATE payments SET raw_response = ? WHERE id = ?",
             [json_encode(['bank_reference' => $bank_ref, 'confirmed_by' => $staff_name]), $payment_id]);
    audit('upi_confirmed', 'booking', $r['booking_id'], ['payment' => $payment_id, 'bank_ref' => $bank_ref], $staff_name);
    return ['ok' => true, 'booking_id' => $r['booking_id'], 'paid' => $r['paid'], 'confirmed' => !empty($r['newly_confirmed'])];
}

/**
 * Record cash/card taken at the property, or a bank transfer. Checked under the
 * lock: two desk staff recording the same payment at once cannot both get past
 * "up to the balance due", so the guest is never shown as having overpaid.
 */
function record_offline_payment(int $booking_id, float $amount, string $method, string $staff_name, string $note = ''): array {
    $amount = round($amount, 2);
    $r = db_tx(function () use ($booking_id, $amount, $method, $staff_name, $note) {
        $b = lock_booking($booking_id);
        if (!$b) return ['ok' => false, 'error' => 'Booking not found.'];
        if (in_array($b['status'], ['cancelled', 'no_show'], true)) return ['ok' => false, 'error' => 'This booking is cancelled — no payment can be recorded.'];
        $due = round((float) $b['total'] - (float) $b['amount_paid'], 2);
        if ($due <= 0.5) return ['ok' => false, 'error' => 'This booking is already fully paid.'];
        if ($amount <= 0 || $amount > $due + 0.5) return ['ok' => false, 'error' => 'Enter an amount up to the balance due (₹' . number_format($due, 2) . ').'];
        if ($b['status'] === 'pending' && !booking_rooms_still_free($b)) {
            return ['ok' => false, 'error' => 'This unpaid booking stopped holding its cottage and someone else has booked it since. '
                                            . 'Change the booking to a free cottage first, then record the payment.'];
        }
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
        // A booking still waiting for payment is confirmed once what it needs now is
        // paid (all of it, or the 50% advance) — the same as an online payment.
        $b = lock_booking($booking_id);
        if ($b['status'] === 'pending' && booking_money($b)['due_now'] <= 0.5) {
            update('bookings', $booking_id, ['status' => 'confirmed', 'updated_at' => now()]);
            audit('payment_settled', 'booking', $booking_id, ['payment' => 'offline', 'paid' => $paid], $staff_name);
            return ['ok' => true, 'paid' => $paid, 'confirmed' => true];
        }
        return ['ok' => true, 'paid' => $paid];
    });
    if (!empty($r['confirmed'])) {
        channel_push_booking($booking_id);
        require_once __DIR__ . '/mail.php';
        send_booking_confirmation($booking_id);
    }
    return $r;
}

/**
 * Money given back by the desk: after a change made the stay cheaper, or after a
 * cancellation (up to the refund it left owing). Checked under the lock, so it
 * can never give back more than is owed.
 */
function record_offline_refund(int $booking_id, float $amount, string $method, string $staff_name, string $note = ''): array {
    $amount = round(abs($amount), 2);
    return db_tx(function () use ($booking_id, $amount, $method, $staff_name, $note) {
        $b = lock_booking($booking_id);
        if (!$b) return ['ok' => false, 'error' => 'Booking not found.'];
        $owed = $b['status'] === 'cancelled'
            ? cancellation_money($b)['outstanding']
            : round((float) $b['amount_paid'] - (float) $b['total'], 2);
        if ($owed <= 0.5) return ['ok' => false, 'error' => 'Nothing is owed back to the guest.'];
        if ($amount <= 0 || $amount > $owed + 0.5) return ['ok' => false, 'error' => 'Enter an amount up to what is owed back (₹' . number_format($owed, 2) . ').'];
        insert('payments', [
            'booking_id'  => $booking_id,
            'provider'    => 'offline',
            'purpose'     => 'refund',
            'method'      => $method,
            'amount'      => -1 * $amount,
            'status'      => 'refunded',
            'verified_by' => $staff_name,
            'raw_response'=> json_encode(['note' => $note]),
            'created_at'  => now(),
            'paid_at'     => now(),
        ]);
        $paid = refresh_amount_paid($booking_id);
        audit('offline_refund', 'booking', $booking_id, ['amount' => $amount, 'method' => $method], $staff_name);
        return ['ok' => true, 'paid' => $paid];
    });
}
