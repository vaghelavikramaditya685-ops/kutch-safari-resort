<?php
/* ===========================================================================
 *  Quoting, creating, retrieving and cancelling bookings.
 * ======================================================================== */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/inventory.php';
require_once __DIR__ . '/channel.php';

/** A short reference a guest can read over the phone. */
function make_ref(string $property_code): string {
    $prefix = $property_code === 'white-rann-camp' ? 'WRC' : 'KSR';
    // No 0/O/1/I — they get misheard and mistyped.
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $tail = '';
        for ($i = 0; $i < 6; $i++) $tail .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        $ref = $prefix . '-' . $tail;
    } while (qval("SELECT id FROM bookings WHERE ref = ?", [$ref]));
    return $ref;
}

/* ---------------------------------------------------------------------------
 * Price a cart without committing anything.
 *
 * $cart = [
 *   'property'  => 'kutch-safari-resort',
 *   'check_in'  => '2026-12-20',
 *   'check_out' => '2026-12-22',
 *   'adults'    => 2, 'children' => 0,
 *   'rooms'     => [ ['room_type_id'=>1,'rate_plan_id'=>2,'rooms'=>1], ... ],
 *   'addons'    => [ ['addon_id'=>3,'quantity'=>2], ... ],
 *   'coupon'    => 'RANN10',
 *   'payment_mode' => 'full' | 'advance' | 'hotel',
 * ]
 * ------------------------------------------------------------------------ */
function quote_cart(array $cart): array {
    $property = q1("SELECT * FROM properties WHERE code = ? AND active = 1", [$cart['property'] ?? '']);
    if (!$property) return ['ok' => false, 'error' => 'Unknown property.'];

    $check_in  = $cart['check_in']  ?? '';
    $check_out = $cart['check_out'] ?? '';
    $err = validate_dates($property, $check_in, $check_out);
    if ($err) return ['ok' => false, 'error' => $err];

    $nights   = nights_between($check_in, $check_out);
    $adults   = max(1, (int) ($cart['adults'] ?? 2));
    $children = max(0, (int) ($cart['children'] ?? 0));

    $lines = [];
    $rooms_subtotal = 0.0;
    $tax_total = 0.0;
    $total_rooms = 0;

    foreach (($cart['rooms'] ?? []) as $line) {
        $rt = q1("SELECT * FROM room_types WHERE id = ? AND property_id = ? AND active = 1",
                 [(int) $line['room_type_id'], $property['id']]);
        $plan = q1("SELECT * FROM rate_plans WHERE id = ? AND room_type_id = ? AND active = 1",
                   [(int) $line['rate_plan_id'], (int) $line['room_type_id']]);
        if (!$rt || !$plan) return ['ok' => false, 'error' => 'That room is no longer offered.'];

        $rooms = max(1, (int) ($line['rooms'] ?? 1));
        $total_rooms += $rooms;

        $free = rooms_free_for_stay($rt, $check_in, $check_out, $cart['hold_token'] ?? null);
        if ($free < $rooms) {
            return ['ok' => false, 'error' => sprintf('Only %d %s left for those dates.', $free, $rt['name'])];
        }

        $priced = price_rate_plan($plan, $check_in, $check_out);
        if ($priced === null) return ['ok' => false, 'error' => $rt['name'] . ' is not available on those dates.'];

        $adults_per_room   = (int) ceil($adults / max(1, count($cart['rooms'])));
        $children_per_room = (int) ceil($children / max(1, count($cart['rooms'])));
        $extras = occupancy_extras($rt, $adults_per_room, $children_per_room, $nights);

        $line_subtotal = money($priced['subtotal'] * $rooms + $extras['amount'] * $rooms);
        $line_tax      = money($priced['tax'] * $rooms
                         + $extras['amount'] * $rooms * tax_percent_for($priced['avg_night']) / 100);

        $rooms_subtotal += $line_subtotal;
        $tax_total      += $line_tax;

        $lines[] = [
            'room_type_id'  => (int) $rt['id'],
            'rate_plan_id'  => (int) $plan['id'],
            'room_type_name'=> $rt['name'],
            'rate_plan_name'=> $plan['name'],
            'rooms'         => $rooms,
            'adults'        => $adults_per_room,
            'children'      => $children_per_room,
            'extra_adults'  => $extras['extra_adults'],
            'nightly'       => $priced['nightly'],
            'per_night'     => $priced['avg_night'],
            'subtotal'      => $line_subtotal,
            'tax_amount'    => $line_tax,
        ];
    }

    if (!$lines) return ['ok' => false, 'error' => 'Please choose a room.'];
    if ($total_rooms > (int) cfg('rules.max_rooms_online', 5)) {
        return ['ok' => false, 'error' => 'For more than ' . cfg('rules.max_rooms_online')
                 . ' rooms please send an enquiry and we will arrange it for you.'];
    }

    /* --- Add-ons ------------------------------------------------------- */
    $addon_lines = [];
    $addons_subtotal = 0.0;
    $addon_tax = 0.0;
    foreach (($cart['addons'] ?? []) as $a) {
        $addon = q1("SELECT * FROM addons WHERE id = ? AND property_id = ? AND active = 1",
                    [(int) $a['addon_id'], $property['id']]);
        if (!$addon) continue;
        $qty = max(1, (int) ($a['quantity'] ?? 1));
        $amount = addon_amount($addon, $qty, $nights, $adults + $children, $total_rooms);
        $t = money($amount * (float) $addon['tax_rate'] / 100);
        $addons_subtotal += $amount;
        $addon_tax += $t;
        $addon_lines[] = [
            'addon_id'   => (int) $addon['id'],
            'addon_name' => $addon['name'],
            'quantity'   => $qty,
            'unit_price' => (float) $addon['price'],
            'price_type' => $addon['price_type'],
            'subtotal'   => $amount,
            'tax_amount' => $t,
        ];
    }
    $tax_total += $addon_tax;

    /* --- Coupon -------------------------------------------------------- */
    $discount = 0.0;
    $coupon_code = null;
    if (!empty($cart['coupon'])) {
        $c = q1("SELECT * FROM coupons WHERE code = ? AND active = 1", [strtoupper(trim($cart['coupon']))]);
        $valid = $c
            && $nights >= (int) $c['min_nights']
            && (empty($c['valid_from']) || $check_in >= $c['valid_from'])
            && (empty($c['valid_to'])   || $check_in <= $c['valid_to'])
            && ($c['max_uses'] === null || (int) $c['times_used'] < (int) $c['max_uses']);
        if ($valid) {
            $discount = $c['discount_type'] === 'percent'
                ? money($rooms_subtotal * (float) $c['amount'] / 100)
                : money((float) $c['amount']);
            $discount = min($discount, $rooms_subtotal);
            $coupon_code = $c['code'];
        }
    }

    $grand = money($rooms_subtotal + $addons_subtotal - $discount + $tax_total);

    /* --- What is payable today ---------------------------------------- */
    $mode = $cart['payment_mode'] ?? 'full';
    $modes = available_payment_modes($check_in);
    if (!isset($modes[$mode])) $mode = array_key_first($modes);

    $due_now = $grand;
    if ($mode === 'advance') {
        $due_now = money($grand * (float) cfg('payment_modes.advance.percent', 50) / 100);
    } elseif ($mode === 'hotel') {
        $due_now = 0.0;
    }

    return [
        'ok' => true,
        'property'        => ['code' => $property['code'], 'name' => $property['name']],
        'check_in'        => $check_in,
        'check_out'       => $check_out,
        'nights'          => $nights,
        'adults'          => $adults,
        'children'        => $children,
        'rooms'           => $lines,
        'addons'          => $addon_lines,
        'rooms_subtotal'  => money($rooms_subtotal),
        'addons_subtotal' => money($addons_subtotal),
        'discount'        => money($discount),
        'coupon_code'     => $coupon_code,
        'tax_amount'      => money($tax_total),
        'total'           => $grand,
        'payment_mode'    => $mode,
        'amount_due_now'  => money($due_now),
        'balance_later'   => money($grand - $due_now),
        'payment_modes'   => $modes,
        'cancellation'    => cancellation_schedule($check_in, $grand),
    ];
}

/** Which payment choices to show, given how close the arrival is. */
function available_payment_modes(string $check_in): array {
    $out = [];
    $days_out = (int) floor((strtotime($check_in) - time()) / 86400);
    foreach (cfg('payment_modes', []) as $key => $m) {
        if (empty($m['enabled'])) continue;
        if (isset($m['min_days_before_arrival']) && $days_out < (int) $m['min_days_before_arrival']) continue;
        $out[$key] = [
            'label'   => $m['label'],
            'note'    => $m['note'] ?? '',
            'percent' => $m['percent'] ?? 100,
        ];
    }
    if (!$out) $out['full'] = ['label' => 'Pay in full now', 'note' => '', 'percent' => 100];
    return $out;
}

/** What the guest would be charged if they cancelled on a given day. */
function cancellation_schedule(string $check_in, float $total): array {
    $rows = [];
    foreach (cfg('cancellation', []) as $step) {
        $when = date('Y-m-d', strtotime($check_in . ' -' . (int) $step['days_before'] . ' day'));
        $rows[] = [
            'until'          => $when,
            'days_before'    => (int) $step['days_before'],
            'charge_percent' => (float) $step['charge_percent'],
            'charge_amount'  => money($total * (float) $step['charge_percent'] / 100),
        ];
    }
    return $rows;
}

function validate_dates(array $property, string $check_in, string $check_out): ?string {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $check_in) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $check_out)) {
        return 'Please choose your dates.';
    }
    if ($check_out <= $check_in) return 'Check-out must be after check-in.';
    if ($check_in < date('Y-m-d')) return 'Those dates are in the past.';

    $nights = nights_between($check_in, $check_out);
    if ($nights > (int) cfg('rules.max_nights', 21)) {
        return 'For stays longer than ' . cfg('rules.max_nights') . ' nights, please send us an enquiry.';
    }
    if (!property_open_between($property, $check_in, $check_out)) {
        return sprintf('%s is open from %s to %s.', $property['name'],
            date('j M Y', strtotime($property['season_start'])),
            date('j M Y', strtotime($property['season_end'])));
    }
    return null;
}

/* ---------------------------------------------------------------------------
 * Commit the booking. Everything inside one transaction, with the availability
 * re-checked at the last moment under a row lock.
 * ------------------------------------------------------------------------ */
function create_booking(array $cart, array $guest): array {
    $quote = quote_cart($cart);
    if (!$quote['ok']) return $quote;

    foreach (['name', 'phone'] as $f) {
        if (empty(trim($guest[$f] ?? ''))) return ['ok' => false, 'error' => 'Please give your ' . $f . '.'];
    }
    if (!empty($guest['email']) && !filter_var($guest['email'], FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'That email address does not look right.'];
    }

    $property = q1("SELECT * FROM properties WHERE code = ?", [$cart['property']]);

    db_begin();
    try {
        // Re-check under lock — someone may have booked while this guest typed.
        foreach ($quote['rooms'] as $line) {
            $rt = q1("SELECT * FROM room_types WHERE id = ?" . for_update(), [$line['room_type_id']]);
            $free = rooms_free_for_stay($rt, $quote['check_in'], $quote['check_out'], $cart['hold_token'] ?? null);
            if ($free < $line['rooms']) {
                db_rollback();
                return ['ok' => false, 'error' => 'Sorry — ' . $rt['name'] . ' has just been taken for those dates.'];
            }

            // And ask the channel manager, which knows about the OTAs too.
            $check = channel_verify_still_available($property, (int) $line['room_type_id'],
                        $quote['check_in'], $quote['check_out'], (int) $line['rooms']);
            if (!$check['ok']) {
                db_rollback();
                return ['ok' => false, 'error' => $check['reason']];
            }
        }

        $ref = make_ref($property['code']);
        $booking_id = insert('bookings', [
            'ref'             => $ref,
            'property_id'     => (int) $property['id'],
            'package_id'      => $cart['package_id'] ?? null,
            'status'          => $quote['payment_mode'] === 'hotel' ? 'confirmed' : 'pending',
            'check_in'        => $quote['check_in'],
            'check_out'       => $quote['check_out'],
            'nights'          => $quote['nights'],
            'adults'          => $quote['adults'],
            'children'        => $quote['children'],
            'guest_name'      => trim($guest['name']),
            'guest_email'     => trim($guest['email'] ?? '') ?: null,
            'guest_phone'     => trim($guest['phone']),
            'guest_city'      => trim($guest['city'] ?? '') ?: null,
            'guest_country'   => trim($guest['country'] ?? 'India'),
            'special_requests'=> trim($guest['special_requests'] ?? '') ?: null,
            'arrival_time'    => trim($guest['arrival_time'] ?? '') ?: null,
            'rooms_subtotal'  => $quote['rooms_subtotal'],
            'addons_subtotal' => $quote['addons_subtotal'],
            'discount'        => $quote['discount'],
            'coupon_code'     => $quote['coupon_code'],
            'tax_amount'      => $quote['tax_amount'],
            'total'           => $quote['total'],
            'amount_paid'     => 0,
            'payment_mode'    => $quote['payment_mode'],
            'amount_due_now'  => $quote['amount_due_now'],
            'source'          => 'website',
            'manage_token'    => bin2hex(random_bytes(20)),
            'created_at'      => now(),
        ]);

        foreach ($quote['rooms'] as $line) {
            insert('booking_rooms', [
                'booking_id'    => $booking_id,
                'room_type_id'  => $line['room_type_id'],
                'rate_plan_id'  => $line['rate_plan_id'],
                'room_type_name'=> $line['room_type_name'],
                'rate_plan_name'=> $line['rate_plan_name'],
                'rooms'         => $line['rooms'],
                'adults'        => $line['adults'],
                'children'      => $line['children'],
                'extra_adults'  => $line['extra_adults'],
                'nightly'       => json_encode($line['nightly']),
                'subtotal'      => $line['subtotal'],
                'tax_amount'    => $line['tax_amount'],
            ]);
        }
        foreach ($quote['addons'] as $a) {
            insert('booking_addons', [
                'booking_id' => $booking_id,
                'addon_id'   => $a['addon_id'],
                'addon_name' => $a['addon_name'],
                'quantity'   => $a['quantity'],
                'unit_price' => $a['unit_price'],
                'subtotal'   => $a['subtotal'],
                'tax_amount' => $a['tax_amount'],
            ]);
        }
        if ($quote['coupon_code']) {
            exec_sql("UPDATE coupons SET times_used = times_used + 1 WHERE code = ?", [$quote['coupon_code']]);
        }
        if (!empty($cart['hold_token'])) {
            exec_sql("UPDATE holds SET booking_id = ? WHERE token = ?", [$booking_id, $cart['hold_token']]);
        }

        db_commit();
    } catch (Throwable $e) {
        db_rollback();
        audit('booking_failed', null, null, ['error' => $e->getMessage()]);
        return ['ok' => false, 'error' => 'We could not save that booking. Please try again or call us.'];
    }

    audit('booking_created', 'booking', $booking_id, ['ref' => $ref, 'total' => $quote['total']]);

    // Pay-at-hotel is confirmed immediately, so tell Stayflexi now. Card and
    // UPI bookings are pushed once the money lands.
    if ($quote['payment_mode'] === 'hotel') {
        channel_push_booking($booking_id);
    }

    return ['ok' => true, 'booking_id' => $booking_id, 'ref' => $ref, 'quote' => $quote,
            'manage_token' => qval("SELECT manage_token FROM bookings WHERE id = ?", [$booking_id])];
}

/* ---------------------------------------------------------------------------
 * Reading a booking back.
 * ------------------------------------------------------------------------ */
function get_booking(int $id): ?array {
    $b = q1("SELECT b.*, p.name AS property_name, p.code AS property_code, p.phone AS property_phone,
                    p.email AS property_email, p.address AS property_address, p.gst_number,
                    p.check_in_time, p.check_out_time, p.accent
               FROM bookings b JOIN properties p ON p.id = b.property_id
              WHERE b.id = ?", [$id]);
    if (!$b) return null;
    $b['rooms']    = q("SELECT * FROM booking_rooms WHERE booking_id = ?", [$id]);
    $b['addons']   = q("SELECT * FROM booking_addons WHERE booking_id = ?", [$id]);
    $b['payments'] = q("SELECT * FROM payments WHERE booking_id = ? ORDER BY id", [$id]);
    return $b;
}

/** Look a booking up the way a guest does: reference + phone or email. */
function find_booking(string $ref, string $contact): ?array {
    $b = q1("SELECT id FROM bookings WHERE ref = ?", [strtoupper(trim($ref))]);
    if (!$b) return null;
    $full = get_booking((int) $b['id']);
    $contact = strtolower(trim($contact));
    $phone_digits = preg_replace('/\D/', '', $contact);
    $stored_phone = preg_replace('/\D/', '', $full['guest_phone']);
    $match = ($contact && strtolower((string) $full['guest_email']) === $contact)
          || ($phone_digits && strlen($phone_digits) >= 6 && str_ends_with($stored_phone, substr($phone_digits, -10)));
    return $match ? $full : null;
}

/* ---------------------------------------------------------------------------
 * Cancelling.
 * ------------------------------------------------------------------------ */
function cancel_booking(int $id, string $reason = '', string $actor = 'guest'): array {
    $b = get_booking($id);
    if (!$b) return ['ok' => false, 'error' => 'Booking not found.'];
    if ($b['status'] === 'cancelled') return ['ok' => false, 'error' => 'That booking is already cancelled.'];
    if ($b['check_in'] < date('Y-m-d')) return ['ok' => false, 'error' => 'Past stays cannot be cancelled online. Please call us.'];

    // Work out the charge from the ladder in config.
    $days_out = (int) floor((strtotime($b['check_in']) - time()) / 86400);
    $charge_percent = 100.0;
    foreach (cfg('cancellation', []) as $step) {
        if ($days_out >= (int) $step['days_before']) { $charge_percent = (float) $step['charge_percent']; break; }
    }
    $charge = money((float) $b['total'] * $charge_percent / 100);
    $refund = money(max(0, (float) $b['amount_paid'] - $charge));

    update('bookings', $id, [
        'status'        => 'cancelled',
        'cancelled_at'  => now(),
        'cancel_reason' => $reason ?: 'Cancelled by ' . $actor,
        'refund_amount' => $refund,
        'updated_at'    => now(),
    ]);
    exec_sql("DELETE FROM holds WHERE booking_id = ?", [$id]);

    channel_cancel_booking($id);
    audit('booking_cancelled', 'booking', $id,
          ['charge_percent' => $charge_percent, 'refund' => $refund], $actor);

    return ['ok' => true, 'charge_percent' => $charge_percent, 'charge' => $charge,
            'refund_due' => $refund,
            'note' => $refund > 0
                ? 'A refund of ₹' . number_format($refund, 2) . ' will reach your account in 5–7 working days.'
                : 'No refund is due under the cancellation policy.'];
}
