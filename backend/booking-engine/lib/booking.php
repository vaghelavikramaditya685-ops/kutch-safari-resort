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
    // Anything that is not a list is treated as nothing chosen (never a PHP warning),
    // and a room line that is not itself a list is dropped.
    $cart['rooms']  = is_array($cart['rooms'] ?? null) ? array_values(array_filter($cart['rooms'], 'is_array')) : [];
    $cart['addons'] = is_array($cart['addons'] ?? null) ? $cart['addons'] : [];
    // Fields that are text: a list there (only a hand-made request sends one) is treated
    // as blank, so it gets a plain refusal instead of a crash.
    foreach (['property', 'check_in', 'check_out', 'payment_mode', 'coupon'] as $f) {
        if (isset($cart[$f]) && !is_scalar($cart[$f])) $cart[$f] = '';
    }
    $property = q1("SELECT * FROM properties WHERE code = ? AND active = 1", [$cart['property'] ?? '']);
    if (!$property) return ['ok' => false, 'error' => 'Unknown property.'];

    $check_in  = $cart['check_in']  ?? '';
    $check_out = $cart['check_out'] ?? '';
    // The desk changing a booking (modify_booking) may work on a stay that has
    // already started and is not bound by the online limits.
    $staff = !empty($cart['staff_edit']);
    $err = $staff ? validate_dates_staff($property, $check_in, $check_out) : validate_dates($property, $check_in, $check_out);
    if ($err) return ['ok' => false, 'error' => $err];

    $nights   = nights_between($check_in, $check_out);
    $adults   = max(1, (int) ($cart['adults'] ?? 2));
    $children = max(0, (int) ($cart['children'] ?? 0));

    $lines = [];
    $rooms_subtotal = 0.0;
    $tax_total = 0.0;
    $total_rooms = 0;
    $rooms_booked = max(1, array_sum(array_map(
        fn($l) => max(1, (int) ($l['rooms'] ?? 1)), (array) ($cart['rooms'] ?? []))));
    // Guests per room, in room order (Room 1, Room 2, …) across every line.
    $occupancy = normalise_occupancy($cart['occupancy'] ?? null, $rooms_booked, $adults);
    $adults = array_sum($occupancy);
    $type_rooms = [];

    foreach (($cart['rooms'] ?? []) as $line) {
        $rt = q1("SELECT * FROM room_types WHERE id = ? AND property_id = ? AND active = 1",
                 [(int) $line['room_type_id'], $property['id']]);
        $plan = q1("SELECT * FROM rate_plans WHERE id = ? AND room_type_id = ? AND active = 1",
                   [(int) $line['rate_plan_id'], (int) $line['room_type_id']]);
        if (!$rt || !$plan) return ['ok' => false, 'error' => 'That room is no longer offered.'];

        $rooms = max(1, (int) ($line['rooms'] ?? 1));
        $first_room = $total_rooms + 1;
        $line_occ = array_slice($occupancy, $total_rooms, $rooms);
        $total_rooms += $rooms;

        // Count every room of this type across all lines — Room 1 and Room 3 can both be Deluxe.
        $type_rooms[$rt['id']] = ($type_rooms[$rt['id']] ?? 0) + $rooms;
        $free = rooms_free_for_stay($rt, $check_in, $check_out, $cart['hold_token'] ?? null);
        if ($free < $type_rooms[$rt['id']]) {
            return ['ok' => false, 'error' => sprintf('Only %d %s left for those dates.', $free, $rt['name'])];
        }
        if (max($line_occ) > (int) $rt['max_adults']) {
            return ['ok' => false, 'error' => sprintf('%s sleeps at most %d.', $rt['name'], $rt['max_adults'])];
        }

        $priced = price_rooms($property, $rt, $plan, $check_in, $check_out, $line_occ);
        if ($priced === null) return ['ok' => false, 'error' => $rt['name'] . ' is not available on those dates.'];

        $units = array_map(fn($u) => array_merge($u, ['room' => $first_room + $u['room'] - 1]), $priced['units']);
        $rooms_subtotal += $priced['subtotal'];
        $tax_total      += $priced['tax'];

        $lines[] = [
            'room_type_id'  => (int) $rt['id'],
            'rate_plan_id'  => (int) $plan['id'],
            'room_type_name'=> $rt['name'],
            // Occupancy goes into the stored name so the desk, the admin panel
            // and the confirmation email all show who sleeps where.
            'rate_plan_name'=> $plan['name'] . ' — ' . implode(', ',
                                 array_map(fn($u) => 'Room ' . $u['room'] . ' ' . $u['label'], $units)),
            'plan_name'     => $plan['name'],
            'rooms'         => $rooms,
            'units'         => $units,
            'adults'        => $priced['adults'],
            'children'      => 0,
            'extra_adults'  => $priced['extra_adults'],
            'nightly'       => $priced['nightly'],
            'per_night'     => $priced['per_night'],
            'subtotal'      => $priced['subtotal'],
            'tax_amount'    => $priced['tax'],
        ];
    }

    if (!$lines) return ['ok' => false, 'error' => 'Please choose a room.'];
    if (!$staff && $total_rooms > (int) cfg('rules.max_rooms_online', 5)) {
        return ['ok' => false, 'error' => 'For more than ' . cfg('rules.max_rooms_online')
                 . ' rooms please send an enquiry and we will arrange it for you.'];
    }

    /* --- Add-ons ------------------------------------------------------- */
    $addon_lines = [];
    $addons_subtotal = 0.0;
    $addon_tax = 0.0;
    foreach (($cart['addons'] ?? []) as $a) {
        $addon = q1("SELECT * FROM addons WHERE id = ? AND property_id = ? AND active = 1",
                    [(int) ($a['addon_id'] ?? 0), $property['id']]);
        if (!$addon) return ['ok' => false, 'error' => 'One of the extras you chose is no longer offered. Please choose again.'];
        // A quantity must be a whole number from 1 up; 0, negatives and words are refused,
        // never quietly turned into 1.
        $raw_qty = $a['quantity'] ?? 1;
        if (!is_numeric($raw_qty) || (float) $raw_qty != (int) $raw_qty || (int) $raw_qty < 1) {
            return ['ok' => false, 'error' => 'Choose how many of "' . $addon['name'] . '" you would like (1 or more).'];
        }
        if ((int) $raw_qty > (int) cfg('rules.max_extra_quantity', 50)) {
            return ['ok' => false, 'error' => 'For more than ' . cfg('rules.max_extra_quantity', 50) . ' of "' . $addon['name'] . '" please call us and we will arrange it.'];
        }
        // An extra already on a booking being changed keeps the price it was booked at.
        $locked_unit = pricing_locks()['addons'][(int) $addon['id']] ?? null;
        if ($locked_unit !== null) $addon['price'] = $locked_unit;
        $qty = max(1, (int) ($a['quantity'] ?? 1));
        $min = max(1, (int) ($addon['min_quantity'] ?? 1));
        if ($addon['price_type'] === 'per_person' && $qty < $min) {
            return ['ok' => false, 'error' => sprintf('%s is for groups of %d or more.', $addon['name'], $min)];
        }
        $name = $addon['name'];
        $assigned = null;
        if (addon_is_per_room($addon)) {
            // One per room, assigned to a numbered room (1..total_rooms). The
            // assignment is written into the line name so the desk sees it on
            // the booking, the admin panel and the confirmation email.
            $assigned = array_values(array_unique(array_filter(
                array_map('intval', is_array($a['rooms'] ?? null) ? $a['rooms'] : []),
                fn($n) => $n >= 1 && $n <= $total_rooms)));
            sort($assigned);
            // Older pages send only a quantity: never more than one per room.
            if (!$assigned) $assigned = range(1, min($qty, $total_rooms));
            if (!$assigned) continue;
            $qty  = count($assigned);
            $name .= ' — ' . implode(', ', array_map(fn($n) => "Room $n", $assigned));
        }
        $amount = addon_amount($addon, $qty, $nights, $adults + $children, $total_rooms);
        if (prices_include_tax($property)) {
            // The price quoted to the guest already includes GST: take it out, don't add it.
            $t = money($amount - $amount / (1 + (float) $addon['tax_rate'] / 100));
            $amount = money($amount - $t);
        } else {
            $t = money($amount * (float) $addon['tax_rate'] / 100);
        }
        $addons_subtotal += $amount;
        $addon_tax += $t;
        $addon_lines[] = [
            'addon_id'   => (int) $addon['id'],
            'addon_name' => $name,
            'rooms'      => $assigned,
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
        $c = q1("SELECT * FROM coupons WHERE code = ?" . (empty($cart['keep_coupon']) ? " AND active = 1" : ''),
                [strtoupper(trim($cart['coupon']))]);
        // A code already on a booking stays on it when the desk changes the booking,
        // even if it has since expired or been used up.
        $valid = $c && !empty($cart['keep_coupon']) ? true : $c
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
        'prices_include_tax' => prices_include_tax($property),
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

/**
 * What the guest would be charged if they cancelled on a given day, earliest
 * period first. Each row says when that charge starts (`from`, null for "any
 * time before") and ends (`to`); `past` marks a period that is already over.
 * Screens show "Up to <to>" for the first row and "From <from>" for the rest.
 */
function cancellation_schedule(string $check_in, float $total): array {
    $steps = cfg('cancellation', []);
    usort($steps, fn($a, $b) => (int) $b['days_before'] <=> (int) $a['days_before']);
    $today = date('Y-m-d');
    $rows = [];
    $prev_to = null;
    foreach ($steps as $i => $step) {
        $last = $i === count($steps) - 1;
        // Cancelling on the day that is `days_before` days out still counts as that many days' notice.
        $to   = $last ? $check_in : date('Y-m-d', strtotime($check_in . ' -' . (int) $step['days_before'] . ' day'));
        $from = $prev_to === null ? null : date('Y-m-d', strtotime($prev_to . ' +1 day'));
        $rows[] = [
            'from'           => $from,
            'to'             => $to,
            'until'          => $to,          // kept for older screens
            'past'           => $to < $today,
            'days_before'    => (int) $step['days_before'],
            'charge_percent' => (float) $step['charge_percent'],
            'charge_amount'  => money($total * (float) $step['charge_percent'] / 100),
        ];
        $prev_to = $to;
    }
    return $rows;
}

/** "Up to Sun 20 Sep" / "From Mon 21 Sep" — the current period starts today if it began earlier. */
function cancellation_label(array $row, string $fmt = 'D, j M Y'): string {
    if ($row['from'] === null) return 'Up to ' . date($fmt, strtotime($row['to']));
    return 'From ' . date($fmt, strtotime(max($row['from'], date('Y-m-d'))));
}

function validate_dates(array $property, string $check_in, string $check_out): ?string {
    if (!valid_date($check_in) || !valid_date($check_out)) {
        return 'Please choose your dates.';
    }
    if ($check_out <= $check_in) return 'Check-out must be after check-in.';
    if ($check_in < date('Y-m-d')) return 'Those dates are in the past.';
    if ($check_in > date('Y-m-d', strtotime('+' . (int) cfg('rules.max_days_ahead', 730) . ' days'))) {
        return 'We take bookings up to two years ahead. For later dates, please call us.';
    }

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

/** Dates the desk may set when changing a booking: in order and in season, past check-in allowed. */
function validate_dates_staff(array $property, string $check_in, string $check_out): ?string {
    if (!valid_date($check_in) || !valid_date($check_out)) {
        return 'Choose both dates.';
    }
    if ($check_out <= $check_in) return 'Check-out must be after check-in.';
    if ($check_out < date('Y-m-d')) return 'That stay is already over.';
    if ($check_in > date('Y-m-d', strtotime('+' . (int) cfg('rules.max_days_ahead', 730) . ' days'))) {
        return 'That is more than two years ahead — check the year.';
    }
    if (!property_open_between($property, $check_in, $check_out)) {
        return sprintf('%s is open from %s to %s.', $property['name'],
            date('j M Y', strtotime($property['season_start'])), date('j M Y', strtotime($property['season_end'])));
    }
    return null;
}

/**
 * Price a change to an existing booking without saving it: the same pricing a
 * guest gets, at today's rates, with this booking's own rooms left out of the
 * availability check. $changes: check_in, check_out, occupancy, rooms[], addons[].
 */
function quote_modification(int $booking_id, array $changes): array {
    $b = get_booking($booking_id);
    if (!$b) return ['ok' => false, 'error' => 'Booking not found.'];
    if ($b['status'] === 'cancelled') return ['ok' => false, 'error' => 'This booking is cancelled and cannot be changed.'];
    if (empty($changes['rooms'])) return ['ok' => false, 'error' => 'Keep at least one room.'];

    // What was agreed stays agreed: nights the guest already has keep their booked
    // price per rate plan, extras keep their booked unit price, the code stays on.
    $locks = ['nightly' => [], 'addons' => []];
    foreach ($b['rooms'] as $line) {
        foreach ((array) json_decode((string) $line['nightly'], true) as $date => $price) {
            $locks['nightly'][(int) $line['rate_plan_id']][$date] = (float) $price;
        }
    }
    foreach ($b['addons'] as $a) $locks['addons'][(int) $a['addon_id']] = (float) $a['unit_price'];

    availability_ignore_booking($booking_id, true);
    pricing_locks($locks, true);
    try {
        $q = quote_cart($changes + [
            'property'     => $b['property_code'],
            'payment_mode' => $b['payment_mode'],
            'coupon'       => $b['coupon_code'],
            'keep_coupon'  => true,
            'staff_edit'   => true,
        ]);
    } finally {
        availability_ignore_booking(null, true);
        pricing_locks(null, true);
    }
    if (!$q['ok']) return $q;

    $q = modification_delta($b, $q);
    $q['old_total']  = (float) $b['total'];
    $q['paid']       = (float) $b['amount_paid'];
    $q['difference'] = money($q['total'] - $q['paid']);   // > 0 still owed, < 0 overpaid
    $q['money']      = booking_money(['total' => $q['total'], 'amount_paid' => $q['paid'],
                                      'payment_mode' => $b['payment_mode'], 'status' => $b['status']]);
    $q['old_money']  = booking_money($b);
    return $q;
}

/**
 * A change to a booking is priced as a difference, never from scratch:
 *     new total = old total + what is added − what is taken off (± what changes)
 * Everything the guest keeps keeps exactly the amount it was booked at. Only
 * new nights, new rooms, a different occupancy and extra extras are priced, at
 * the prices quote_cart() worked out for them ($q). Rewrites $q's lines and
 * totals to those amounts and adds $q['changes'].
 */
function modification_delta(array $b, array $q): array {
    $span = function (string $ci, string $co): array {
        $out = [];
        for ($d = $ci; $d < $co; $d = date('Y-m-d', strtotime($d . ' +1 day'))) $out[] = $d;
        return $out;
    };
    $old_nights = $span($b['check_in'], $b['check_out']);
    $new_nights = $span($q['check_in'], $q['check_out']);
    $keep = array_values(array_intersect($old_nights, $new_nights));
    $more = array_values(array_diff($new_nights, $old_nights));
    $less = array_values(array_diff($old_nights, $new_nights));
    $incl = !empty($q['prices_include_tax']);

    // One entry per room: cottage, plan, occupancy, its [subtotal, GST] for the stay.
    $units = function (array $lines): array {
        $out = [];
        foreach ($lines as $i => $l) {
            $n = max(1, (int) $l['rooms']);
            preg_match_all('/Room \d+ (Single|Double|Triple)/', (string) $l['rate_plan_name'], $m);
            $nightly = is_array($l['nightly']) ? $l['nightly'] : (array) json_decode((string) $l['nightly'], true);
            for ($k = 0; $k < $n; $k++) {
                $out[] = ['line' => $i, 'type' => (int) $l['room_type_id'], 'plan' => (int) $l['rate_plan_id'],
                          'cottage' => $l['room_type_name'], 'occ' => $m[1][$k] ?? '', 'nightly' => $nightly,
                          'sub' => money((float) $l['subtotal'] / $n), 'tax' => money((float) $l['tax_amount'] / $n)];
            }
        }
        return $out;
    };
    // The part of a room's amount that belongs to some of its nights (special prices
    // make nights differ, so each night carries its own share).
    $share = function (array $u, array $dates) use ($incl): array {
        $all = array_keys($u['nightly']);
        $in = array_values(array_intersect($all, $dates));
        if (!$in) return [0.0, 0.0];
        if (count($in) === count($all)) return [$u['sub'], $u['tax']];
        $adj = (($incl ? $u['sub'] + $u['tax'] : $u['sub']) - array_sum($u['nightly'])) / count($all);
        $w = $tw = 0.0;
        foreach ($all as $d) {
            $x = max(0.0, (float) $u['nightly'][$d] + $adj);
            $tw += $x;
            if (in_array($d, $in, true)) $w += $x;
        }
        $f = $tw > 0 ? $w / $tw : count($in) / count($all);
        // Round the amount first and the GST inside it second, so ₹7,450 a night stays ₹7,450.
        $g = money(($u['sub'] + $u['tax']) * $f);
        $t = money($u['tax'] * $f);
        return [money($g - $t), $t];
    };
    $gross = fn(array $p): float => money($p[0] + $p[1]);

    $old = $units($b['rooms']);
    $new = $units($q['rooms']);
    $final = [];
    $added = $removed = $changed = [];

    // 1. The same room kept (same cottage and plan), first with the same occupancy,
    //    then with a different one. Nights it keeps keep their booked amount.
    foreach ([true, false] as $same_occ) {
        foreach ($new as $ni => $nu) {
            if (isset($final[$ni])) continue;
            foreach ($old as $oi => $ou) {
                if ($ou['type'] !== $nu['type'] || $ou['plan'] !== $nu['plan']) continue;
                if ($same_occ && $ou['occ'] !== $nu['occ']) continue;
                $kept_old = $share($ou, $keep);
                $kept_new = $same_occ ? $kept_old : $share($nu, $keep);
                $extra    = $share($nu, $more);
                $final[$ni] = [$kept_new[0] + $extra[0], $kept_new[1] + $extra[1]];
                if (!$same_occ && $keep) {
                    $changed[] = ['what' => $nu['cottage'] . ': ' . ($ou['occ'] ?: '?') . ' → ' . ($nu['occ'] ?: '?') . ', ' . nights_label($keep),
                                  'amount' => money($gross($kept_new) - $gross($kept_old))];
                }
                if ($more) $added[]   = ['what' => $nu['cottage'] . ' (' . $nu['occ'] . ') — more nights, ' . nights_label($more), 'amount' => $gross($extra)];
                if ($less) $removed[] = ['what' => $ou['cottage'] . ' (' . $ou['occ'] . ') — nights taken off, ' . nights_label($less),
                                         'amount' => -money($gross([$ou['sub'], $ou['tax']]) - $gross($kept_old))];
                unset($old[$oi]);
                break;
            }
        }
    }
    // 2. Rooms that are new, and rooms taken off.
    foreach ($new as $ni => $nu) {
        if (isset($final[$ni])) continue;
        $final[$ni] = [$nu['sub'], $nu['tax']];
        $added[] = ['what' => $nu['cottage'] . ' (' . $nu['occ'] . '), ' . nights_label($new_nights), 'amount' => $gross($final[$ni])];
    }
    foreach ($old as $ou) {
        $removed[] = ['what' => $ou['cottage'] . ' (' . $ou['occ'] . '), ' . nights_label($old_nights), 'amount' => -$gross([$ou['sub'], $ou['tax']])];
    }
    foreach ($q['rooms'] as $i => $l) { $q['rooms'][$i]['subtotal'] = 0.0; $q['rooms'][$i]['tax_amount'] = 0.0; }
    foreach ($new as $ni => $nu) {
        $q['rooms'][$nu['line']]['subtotal']   = money($q['rooms'][$nu['line']]['subtotal'] + $final[$ni][0]);
        $q['rooms'][$nu['line']]['tax_amount'] = money($q['rooms'][$nu['line']]['tax_amount'] + $final[$ni][1]);
    }

    // 3. Extras: the ones kept keep their booked amount; more or fewer are the difference.
    $old_ex = [];
    foreach ($b['addons'] as $a) {
        $k = (int) $a['addon_id'];
        $old_ex[$k] = ['name' => preg_replace('/ — Room .*/', '', (string) $a['addon_name']),
                       'qty'  => ($old_ex[$k]['qty'] ?? 0) + max(1, (int) $a['quantity']),
                       'sub'  => ($old_ex[$k]['sub'] ?? 0) + (float) $a['subtotal'],
                       'tax'  => ($old_ex[$k]['tax'] ?? 0) + (float) $a['tax_amount']];
    }
    foreach ($q['addons'] as $i => $a) {
        $k = (int) $a['addon_id'];
        $name = preg_replace('/ — Room .*/', '', (string) $a['addon_name']);
        $nq = max(1, (int) $a['quantity']);
        $o = $old_ex[$k] ?? null;
        if (!$o) { $added[] = ['what' => $name . ' × ' . $nq, 'amount' => $gross([$a['subtotal'], $a['tax_amount']])]; continue; }
        unset($old_ex[$k]);
        // Priced by the night: a different number of nights changes all of it.
        if (in_array($a['price_type'], ['per_night', 'per_room_night'], true) && count($old_nights) !== count($new_nights)) {
            $d = money($gross([$a['subtotal'], $a['tax_amount']]) - $gross([$o['sub'], $o['tax']]));
            if (abs($d) >= 0.01) $changed[] = ['what' => $name . ' — for ' . count($new_nights) . ' nights instead of ' . count($old_nights), 'amount' => $d];
            continue;
        }
        $kq = min($o['qty'], $nq);
        $kept = $kq === $o['qty'] ? [money($o['sub']), money($o['tax'])]
                                  : [money($o['sub'] * $kq / $o['qty']), money($o['tax'] * $kq / $o['qty'])];
        $extra = $nq > $kq ? [money($a['subtotal'] * ($nq - $kq) / $nq), money($a['tax_amount'] * ($nq - $kq) / $nq)] : [0.0, 0.0];
        $q['addons'][$i]['subtotal']   = money($kept[0] + $extra[0]);
        $q['addons'][$i]['tax_amount'] = money($kept[1] + $extra[1]);
        if ($nq > $o['qty']) $added[]   = ['what' => $name . ' × ' . ($nq - $o['qty']) . ' more', 'amount' => $gross($extra)];
        if ($nq < $o['qty']) $removed[] = ['what' => $name . ' × ' . ($o['qty'] - $nq) . ' fewer', 'amount' => -money($gross([$o['sub'], $o['tax']]) - $gross($kept))];
    }
    foreach ($old_ex as $o) $removed[] = ['what' => $o['name'] . ' × ' . $o['qty'], 'amount' => -$gross([$o['sub'], $o['tax']])];

    // 4. Totals from those amounts.
    $rooms_sub = $rooms_tax = $ex_sub = $ex_tax = 0.0;
    foreach ($q['rooms'] as $l)  { $rooms_sub += $l['subtotal']; $rooms_tax += $l['tax_amount']; }
    foreach ($q['addons'] as $a) { $ex_sub += $a['subtotal']; $ex_tax += $a['tax_amount']; }

    // A discount code works on the room price: kept rooms keep their discount,
    // added or removed rooms move it by the code's percentage.
    $discount = (float) $b['discount'];
    if ($discount > 0) {
        $c = $b['coupon_code'] ? q1("SELECT * FROM coupons WHERE code = ?", [$b['coupon_code']]) : null;
        if ($c && $c['discount_type'] === 'percent') {
            $discount = money($discount + ($rooms_sub - (float) $b['rooms_subtotal']) * (float) $c['amount'] / 100);
        }
        $discount = max(0.0, min($discount, money($rooms_sub)));
        if (abs($discount - (float) $b['discount']) >= 0.01) {
            $changed[] = ['what' => 'Discount' . ($b['coupon_code'] ? ' (' . $b['coupon_code'] . ')' : '') . ' on the rooms added or taken off',
                          'amount' => money((float) $b['discount'] - $discount)];
        }
    }

    $sum = fn(array $xs): float => money(array_sum(array_column($xs, 'amount')));
    $total = money((float) $b['total'] + $sum($added) + $sum($removed) + $sum($changed));
    $tax = money($rooms_tax + $ex_tax);
    // Paise left over from how an older booking was rounded go into the GST figure,
    // so that the parts still add up to the total.
    $tax = money($tax + $total - money($rooms_sub + $ex_sub - $discount + $tax));

    $q['rooms_subtotal']  = money($rooms_sub);
    $q['addons_subtotal'] = money($ex_sub);
    $q['discount']        = money($discount);
    $q['coupon_code']     = $b['coupon_code'];
    $q['tax_amount']      = $tax;
    $q['total']           = $total;
    $q['cancellation']    = cancellation_schedule($q['check_in'], $total);
    $q['changes'] = ['added' => $added, 'removed' => $removed, 'changed' => $changed,
                     'added_total' => $sum($added), 'removed_total' => $sum($removed), 'changed_total' => $sum($changed),
                     'change' => money($total - (float) $b['total'])];
    return $q;
}

/** "27 Sep – 29 Sep (2 nights)" for a set of nights; separate runs are joined with "and". */
function nights_label(array $dates): string {
    sort($dates);
    $runs = [];
    foreach ($dates as $d) {
        $last = count($runs) - 1;
        if ($last >= 0 && date('Y-m-d', strtotime($runs[$last][1] . ' +1 day')) === $d) $runs[$last][1] = $d;
        else $runs[] = [$d, $d];
    }
    $n = count($dates);
    return implode(' and ', array_map(fn($r) => date('j M', strtotime($r[0])) . ' – ' . date('j M', strtotime($r[1] . ' +1 day')), $runs))
         . ' (' . $n . ' night' . ($n === 1 ? '' : 's') . ')';
}

/**
 * What the guest owes, going by how they chose to pay: the full amount up front,
 * or the advance (50%) now and the rest before arrival (config: payment_modes.advance.note).
 *   needed_now — what should be paid by now (100%, or 50% of the total)
 *   due_now    — what is short of that
 *   later      — what is left to pay before arrival (50% plan)
 *   refund     — what was paid over the total
 */
function booking_money(array $b): array {
    $total = (float) $b['total'];
    $paid  = (float) $b['amount_paid'];
    $mode  = $b['payment_mode'] ?? 'full';
    $pct   = $mode === 'advance' ? (float) cfg('payment_modes.advance.percent', 50) : ($mode === 'hotel' ? 0.0 : 100.0);
    $needed = money($total * $pct / 100);
    $open = ($b['status'] ?? '') !== 'cancelled';
    return [
        'total'      => $total,
        'paid'       => $paid,
        'mode'       => $mode,
        'percent'    => $pct,
        'needed_now' => $needed,
        'due_now'    => $open ? max(0.0, money($needed - $paid)) : 0.0,
        'later'      => $open ? max(0.0, money($total - max($paid, $needed))) : 0.0,
        'refund'     => $open ? max(0.0, money($paid - $total)) : 0.0,
        'balance'    => money($total - $paid),
    ];
}

/** Save a change made by the desk: dates, rooms, occupancy and extras. */
function modify_booking(int $booking_id, array $changes, string $actor): array {
    $b = get_booking($booking_id);
    $q = quote_modification($booking_id, $changes);
    if (!$q['ok']) return $q;

    db_begin();
    try {
        // Re-check under lock, still leaving this booking's own rooms out.
        availability_ignore_booking($booking_id, true);
        $wanted = [];
        foreach ($q['rooms'] as $line) $wanted[$line['room_type_id']] = ($wanted[$line['room_type_id']] ?? 0) + (int) $line['rooms'];
        foreach ($wanted as $rt_id => $n) {
            $rt = q1("SELECT * FROM room_types WHERE id = ?" . for_update(), [$rt_id]);
            if (rooms_free_for_stay($rt, $q['check_in'], $q['check_out']) < $n) {
                db_rollback();
                return ['ok' => false, 'error' => 'Not enough ' . $rt['name'] . ' free for those dates any more.'];
            }
        }

        exec_sql("DELETE FROM booking_rooms WHERE booking_id = ?", [$booking_id]);
        exec_sql("DELETE FROM booking_addons WHERE booking_id = ?", [$booking_id]);
        foreach ($q['rooms'] as $line) {
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
        foreach ($q['addons'] as $a) {
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
        update('bookings', $booking_id, [
            'check_in'        => $q['check_in'],
            'check_out'       => $q['check_out'],
            'nights'          => $q['nights'],
            'adults'          => $q['adults'],
            'children'        => $q['children'],
            'rooms_subtotal'  => $q['rooms_subtotal'],
            'addons_subtotal' => $q['addons_subtotal'],
            'discount'        => $q['discount'],
            'tax_amount'      => $q['tax_amount'],
            'total'           => $q['total'],
            'updated_at'      => now(),
        ]);
        db_commit();
    } catch (Throwable $e) {
        db_rollback();
        throw $e;
    } finally {
        availability_ignore_booking(null, true);
    }

    $summary = fn($x) => $x['check_in'] . ' → ' . $x['check_out'] . ' · ' . implode('; ',
        array_map(fn($r) => $r['rooms'] . ' × ' . $r['room_type_name'] . ' (' . $r['rate_plan_name'] . ')', $x['rooms']));
    audit('booking_modified', 'booking', $booking_id, [
        'from' => $summary($b), 'to' => $summary($q),
        'total_from' => (float) $b['total'], 'total_to' => $q['total'], 'paid' => $q['paid'],
        'changes' => $q['changes'],
    ], $actor);

    // Tell the channel manager: take the old reservation off and send the new one.
    if ($b['status'] === 'confirmed' && function_exists('channel_enabled') && channel_enabled() && $b['sf_booking_id']) {
        channel_cancel_booking($booking_id);
        update('bookings', $booking_id, ['sf_booking_id' => null, 'sf_synced_at' => null, 'sf_sync_error' => null]);
        channel_push_booking($booking_id);
    }

    return ['ok' => true, 'total' => $q['total'], 'old_total' => $q['old_total'],
            'paid' => $q['paid'], 'difference' => $q['difference']];
}

/** The first room number written in a line's plan name ("… — Room 3 Double"), for ordering. */
function room_number_of(array $line): int {
    return preg_match('/Room (\d+)/', (string) $line['rate_plan_name'], $m) ? (int) $m[1] : (int) $line['id'];
}

/** When the desk last changed the booking, or null. */
function booking_modified_at(int $booking_id): ?string {
    return qval("SELECT created_at FROM audit_log WHERE action = 'booking_modified' AND entity = 'booking' AND entity_id = ?
                  ORDER BY id DESC LIMIT 1", [(string) $booking_id], null);
}

/**
 * What the guest has actually paid, net of any refunds: payments received minus
 * money given back. Kept on bookings.amount_paid.
 */
function refresh_amount_paid(int $booking_id): float {
    $paid = (float) qval("SELECT COALESCE(SUM(amount), 0) FROM payments
                           WHERE booking_id = ? AND ((status = 'paid' AND purpose <> 'refund')
                                                  OR (purpose = 'refund' AND status IN ('paid','refunded')))",
                         [$booking_id], 0);
    update('bookings', $booking_id, ['amount_paid' => money($paid), 'updated_at' => now()]);
    return money($paid);
}

/* ---------------------------------------------------------------------------
 * Commit the booking. Everything inside one transaction, with the availability
 * re-checked at the last moment under a row lock.
 * ------------------------------------------------------------------------ */
function create_booking(array $cart, array $guest): array {
    $quote = quote_cart($cart);
    if (!$quote['ok']) return $quote;

    // Guest details are text. A list in any of them counts as blank, rather than
    // being saved as the word "Array".
    foreach (['name', 'phone', 'email', 'city', 'country', 'arrival_time', 'special_requests'] as $f) {
        if (isset($guest[$f]) && !is_scalar($guest[$f])) $guest[$f] = '';
    }

    // Sample mode (config rules.guest_details_optional): details may be left blank.
    // Nothing is filled in on the guest's behalf — only what was typed is saved.
    if (!cfg('rules.guest_details_optional')) {
        foreach (['name', 'phone'] as $f) {
            if (empty(trim($guest[$f] ?? ''))) return ['ok' => false, 'error' => 'Please give your ' . $f . '.'];
        }
    }
    // Details that are given must make sense ('field' => 'guest' keeps the guest on the details step).
    $bad = fn(string $msg) => ['ok' => false, 'error' => $msg, 'field' => 'guest'];
    if (!empty($guest['email']) && (!filter_var($guest['email'], FILTER_VALIDATE_EMAIL) || too_long($guest['email'], 'email'))) {
        return $bad('That email address does not look right.');
    }
    if (too_long($guest['name'] ?? '', 'name')) return $bad('Please keep the name under ' . LIMITS['name'] . ' characters.');
    if (trim((string) ($guest['phone'] ?? '')) !== '' && !valid_phone((string) $guest['phone'])) {
        return $bad('That mobile number does not look right. Use digits only, for example 98250 12345 or +91 98250 12345.');
    }
    if (!valid_arrival_time(trim((string) ($guest['arrival_time'] ?? '')))) return $bad('Choose the arrival time on the clock, or leave it blank.');
    if (too_long($guest['city'] ?? '', 'city')) return $bad('Please keep the city under ' . LIMITS['city'] . ' characters.');
    if (too_long($guest['special_requests'] ?? '', 'guest_note')) {
        return $bad('Please keep your note under ' . number_format(LIMITS['guest_note']) . ' characters.');
    }

    $property = q1("SELECT * FROM properties WHERE code = ?", [$cart['property']]);

    db_begin();
    try {
        // Re-check under lock — someone may have booked while this guest typed.
        // Rooms of one type can sit on several lines (one line per room when the
        // guest mixes cottage types), so count them together.
        $wanted = [];
        foreach ($quote['rooms'] as $line) {
            $wanted[$line['room_type_id']] = ($wanted[$line['room_type_id']] ?? 0) + (int) $line['rooms'];
        }
        foreach ($quote['rooms'] as $line) {
            $rt = q1("SELECT * FROM room_types WHERE id = ?" . for_update(), [$line['room_type_id']]);
            $free = rooms_free_for_stay($rt, $quote['check_in'], $quote['check_out'], $cart['hold_token'] ?? null);
            if ($free < $wanted[$line['room_type_id']]) {
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
            'guest_name'      => trim((string) ($guest['name'] ?? '')),
            'guest_email'     => trim($guest['email'] ?? '') ?: null,
            'guest_phone'     => trim((string) ($guest['phone'] ?? '')),
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
    // Similar rooms together: by cottage (in the site's own order), then Room 1, 2…
    $b['rooms']    = q("SELECT br.*, COALESCE(rt.sort_order, 99) AS type_sort FROM booking_rooms br
                          LEFT JOIN room_types rt ON rt.id = br.room_type_id WHERE br.booking_id = ?", [$id]);
    usort($b['rooms'], fn($x, $y) => [(int) $x['type_sort'], (int) $x['room_type_id'], room_number_of($x)]
                                 <=> [(int) $y['type_sort'], (int) $y['room_type_id'], room_number_of($y)]);
    $b['addons']   = q("SELECT * FROM booking_addons WHERE booking_id = ?", [$id]);
    $b['payments'] = q("SELECT * FROM payments WHERE booking_id = ? ORDER BY id", [$id]);
    return $b;
}

/** Look a booking up with its reference and private manage_token. */
function find_booking_by_token(string $ref, string $token): ?array {
    $b = q1("SELECT id, manage_token FROM bookings WHERE ref = ?", [strtoupper(trim($ref))]);
    if (!$b || $token === '' || !hash_equals((string) $b['manage_token'], $token)) return null;
    return get_booking((int) $b['id']);
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
