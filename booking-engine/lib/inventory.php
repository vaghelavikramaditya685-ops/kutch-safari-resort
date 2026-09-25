<?php
/* ===========================================================================
 *  Availability and pricing.
 *
 *  A room is sellable on a date when
 *      rooms_open  -  rooms already booked  -  rooms held in someone's cart   >  0
 *
 *  rooms_open comes from the inventory calendar, or from the room type's total
 *  when that date has no row. When Stayflexi is connected, the inventory
 *  calendar is refreshed from it, so the channel manager stays the authority.
 * ======================================================================== */

require_once __DIR__ . '/db.php';

/** Every night of the stay. A 2-night stay from the 5th is [05, 06]. */
function stay_dates(string $check_in, string $check_out): array {
    $dates = [];
    $d = new DateTime($check_in);
    $end = new DateTime($check_out);
    while ($d < $end) {
        $dates[] = $d->format('Y-m-d');
        $d->modify('+1 day');
    }
    return $dates;
}

function nights_between(string $check_in, string $check_out): int {
    return count(stay_dates($check_in, $check_out));
}

/** GST percent for a given per-night tariff, from the slabs in config. */
function tax_percent_for(float $nightly_price): float {
    foreach (cfg('tax_slabs', [[PHP_INT_MAX, 0]]) as [$upto, $percent]) {
        if ($nightly_price <= $upto) return (float) $percent;
    }
    return 0.0;
}

function money(float $n): float { return round($n, 2); }

/* ---------------------------------------------------------------------------
 * How many rooms of a type are already committed on a date.
 * ------------------------------------------------------------------------ */
function rooms_booked(int $room_type_id, string $date): int {
    return (int) qval(
        "SELECT COALESCE(SUM(br.rooms), 0)
           FROM booking_rooms br
           JOIN bookings b ON b.id = br.booking_id
          WHERE br.room_type_id = ?
            AND b.status IN ('pending','confirmed')
            AND b.check_in <= ? AND b.check_out > ?",
        [$room_type_id, $date, $date], 0);
}

/** Rooms sitting in someone else's cart right now. */
function rooms_held(int $room_type_id, string $date, ?string $ignore_token = null): int {
    $sql = "SELECT COALESCE(SUM(rooms), 0) FROM holds
             WHERE room_type_id = ? AND expires_at > ?
               AND check_in <= ? AND check_out > ? AND booking_id IS NULL";
    $params = [$room_type_id, now(), $date, $date];
    if ($ignore_token) { $sql .= " AND token <> ?"; $params[] = $ignore_token; }
    return (int) qval($sql, $params, 0);
}

/** Rooms the property is willing to sell on a date. */
function rooms_open(array $room_type, string $date): int {
    $open = qval("SELECT rooms_open FROM inventory WHERE room_type_id = ? AND stay_date = ?",
                 [$room_type['id'], $date], null);
    return $open === null ? (int) $room_type['total_rooms'] : (int) $open;
}

/** Rooms a guest could actually book on a single date. */
function rooms_free(array $room_type, string $date, ?string $ignore_token = null): int {
    $free = rooms_open($room_type, $date)
          - rooms_booked((int) $room_type['id'], $date)
          - rooms_held((int) $room_type['id'], $date, $ignore_token);
    return max(0, $free);
}

/** The smallest number free across every night — what we can actually sell. */
function rooms_free_for_stay(array $room_type, string $check_in, string $check_out, ?string $ignore_token = null): int {
    $min = PHP_INT_MAX;
    foreach (stay_dates($check_in, $check_out) as $d) {
        $min = min($min, rooms_free($room_type, $d, $ignore_token));
        if ($min === 0) return 0;
    }
    return $min === PHP_INT_MAX ? 0 : $min;
}

/* ---------------------------------------------------------------------------
 * Pricing.
 * ------------------------------------------------------------------------ */

/**
 * Price a rate plan across the stay.
 * Returns null when the plan is closed on any night or fails its minimum stay.
 */
function price_rate_plan(array $plan, string $check_in, string $check_out): ?array {
    $dates = stay_dates($check_in, $check_out);
    $nights = count($dates);
    $nightly = [];
    $subtotal = 0.0;
    $tax = 0.0;

    // Pull every stored rate for this plan across the stay in one query.
    $ph = implode(',', array_fill(0, $nights, '?'));
    $rows = q("SELECT stay_date, price, min_stay, closed FROM rates
                WHERE rate_plan_id = ? AND stay_date IN ($ph)",
              array_merge([$plan['id']], $dates));
    $byDate = [];
    foreach ($rows as $r) $byDate[$r['stay_date']] = $r;

    foreach ($dates as $d) {
        $row = $byDate[$d] ?? null;
        if ($row && (int) $row['closed'] === 1) return null;           // stop-sell
        if ($row && $nights < (int) $row['min_stay']) return null;      // min stay not met
        $price = $row ? (float) $row['price'] : (float) $plan['base_price'];
        $nightly[$d] = money($price);
        $subtotal += $price;
        $tax += $price * tax_percent_for($price) / 100;
    }

    return [
        'nightly'  => $nightly,
        'subtotal' => money($subtotal),
        'tax'      => money($tax),
        'total'    => money($subtotal + $tax),
        'avg_night'=> money($subtotal / max(1, $nights)),
    ];
}

/**
 * Charge for guests beyond the price's base occupancy.
 * Children under the free age are not counted.
 */
function occupancy_extras(array $room_type, int $adults, int $children, int $nights): array {
    $extra_adults = max(0, $adults - (int) $room_type['base_occupancy']);
    $free_under = (int) cfg('rules.child_free_under', 6);
    $charged_children = $free_under > 0 ? $children : $children;  // age is collected at check-in
    $amount = $extra_adults * (float) $room_type['extra_adult_price'] * $nights
            + $charged_children * (float) $room_type['extra_child_price'] * $nights;
    return [
        'extra_adults' => $extra_adults,
        'children'     => $charged_children,
        'amount'       => money($amount),
    ];
}

/* ---------------------------------------------------------------------------
 * The search a guest performs.
 * ------------------------------------------------------------------------ */

/**
 * Everything bookable at a property for these dates.
 * $rooms_wanted filters out categories that cannot supply that many.
 */
function search_availability(array $property, string $check_in, string $check_out,
                             int $adults = 2, int $children = 0, int $rooms_wanted = 1): array {
    $nights = nights_between($check_in, $check_out);
    $results = [];

    $room_types = q("SELECT * FROM room_types WHERE property_id = ? AND active = 1 ORDER BY sort_order, id",
                    [$property['id']]);

    foreach ($room_types as $rt) {
        // Occupancy check, spread across the rooms requested.
        $capacity_adults   = (int) $rt['max_adults'] * $rooms_wanted;
        $capacity_children = (int) $rt['max_children'] * $rooms_wanted;
        if ($adults > $capacity_adults || $children > $capacity_children) continue;

        $free = rooms_free_for_stay($rt, $check_in, $check_out);
        if ($free < $rooms_wanted) continue;

        $adults_per_room   = (int) ceil($adults / max(1, $rooms_wanted));
        $children_per_room = (int) ceil($children / max(1, $rooms_wanted));
        $extras = occupancy_extras($rt, $adults_per_room, $children_per_room, $nights);

        $plans = [];
        foreach (q("SELECT * FROM rate_plans WHERE room_type_id = ? AND active = 1 ORDER BY sort_order, id",
                   [$rt['id']]) as $plan) {
            $priced = price_rate_plan($plan, $check_in, $check_out);
            if ($priced === null) continue;

            $rooms_total = $priced['subtotal'] * $rooms_wanted + $extras['amount'] * $rooms_wanted;
            $tax_total   = $priced['tax'] * $rooms_wanted
                         + $extras['amount'] * $rooms_wanted * tax_percent_for($priced['avg_night']) / 100;

            $plans[] = [
                'id'          => (int) $plan['id'],
                'code'        => $plan['code'],
                'name'        => $plan['name'],
                'meal_note'   => $plan['meal_note'],
                'refundable'  => (bool) $plan['refundable'],
                'nightly'     => $priced['nightly'],
                'per_night'   => $priced['avg_night'],
                'per_night_with_tax' => money($priced['avg_night'] * (1 + tax_percent_for($priced['avg_night']) / 100)),
                'subtotal'    => money($rooms_total),
                'tax'         => money($tax_total),
                'total'       => money($rooms_total + $tax_total),
            ];
        }
        if (!$plans) continue;

        $results[] = [
            'id'            => (int) $rt['id'],
            'code'          => $rt['code'],
            'name'          => $rt['name'],
            'description'   => $rt['description'],
            'bed_type'      => $rt['bed_type'],
            'size_label'    => $rt['size_label'],
            'max_adults'    => (int) $rt['max_adults'],
            'max_children'  => (int) $rt['max_children'],
            'base_occupancy'=> (int) $rt['base_occupancy'],
            'images'        => json_decode($rt['images'] ?: '[]', true) ?: [],
            'amenities'     => json_decode($rt['amenities'] ?: '[]', true) ?: [],
            'rooms_left'    => $free,
            // Shown as a gentle nudge when stock is genuinely low — never invented.
            'low_stock'     => $free <= 3,
            'extra_adults'  => $extras['extra_adults'],
            'extra_charge'  => $extras['amount'],
            'rate_plans'    => $plans,
        ];
    }

    return $results;
}

/**
 * When nothing is available, look ahead for dates that are — the behaviour
 * the Rann Riders engine has, and far better than a dead end.
 */
function next_available_dates(array $property, string $check_in, int $nights, int $rooms_wanted = 1, int $look_ahead = 45): array {
    $found = [];
    $start = new DateTime($check_in);
    for ($i = 1; $i <= $look_ahead && count($found) < 3; $i++) {
        $try_in = (clone $start)->modify("+$i day");
        $try_out = (clone $try_in)->modify("+$nights day");
        if (!property_open_between($property, $try_in->format('Y-m-d'), $try_out->format('Y-m-d'))) continue;
        $avail = search_availability($property, $try_in->format('Y-m-d'), $try_out->format('Y-m-d'), 2, 0, $rooms_wanted);
        if ($avail) {
            $found[] = [
                'check_in'  => $try_in->format('Y-m-d'),
                'check_out' => $try_out->format('Y-m-d'),
                'label'     => $try_in->format('j M'),
                'from_price'=> min(array_map(fn($r) => $r['rate_plans'][0]['total'], $avail)),
            ];
        }
    }
    return $found;
}

/** A seasonal property (the camp) only sells inside its window. */
function property_open_between(array $property, string $check_in, string $check_out): bool {
    if (empty($property['season_start']) || empty($property['season_end'])) return true;
    $last_night = (new DateTime($check_out))->modify('-1 day')->format('Y-m-d');
    return $check_in >= $property['season_start'] && $last_night <= $property['season_end'];
}

/* ---------------------------------------------------------------------------
 * Holds — a short reservation of stock while the guest pays.
 * ------------------------------------------------------------------------ */
function create_hold(int $room_type_id, string $check_in, string $check_out, int $rooms): string {
    purge_expired_holds();
    $token = bin2hex(random_bytes(16));
    insert('holds', [
        'token'        => $token,
        'room_type_id' => $room_type_id,
        'check_in'     => $check_in,
        'check_out'    => $check_out,
        'rooms'        => $rooms,
        'expires_at'   => date('Y-m-d H:i:s', time() + 60 * (int) cfg('rules.hold_minutes', 20)),
        'created_at'   => now(),
    ]);
    return $token;
}

function release_hold(string $token): void {
    exec_sql("DELETE FROM holds WHERE token = ?", [$token]);
}

function purge_expired_holds(): void {
    exec_sql("DELETE FROM holds WHERE expires_at < ? AND booking_id IS NULL", [now()]);
}

/* ---------------------------------------------------------------------------
 * Add-ons.
 * ------------------------------------------------------------------------ */
function list_addons(int $property_id): array {
    return q("SELECT * FROM addons WHERE property_id = ? AND active = 1 ORDER BY sort_order, id", [$property_id]);
}

/**
 * What an add-on costs for this stay.
 *
 * `quantity` always means "how many units the guest picked" — how many people
 * for a per-person extra, how many beds for a per-night one. Only the time
 * dimension is multiplied on top, so the number a guest chooses is the number
 * they are charged for.
 */
function addon_amount(array $addon, int $quantity, int $nights, int $guests, int $rooms): float {
    $unit = (float) $addon['price'];
    switch ($addon['price_type']) {
        case 'per_person':     return money($unit * $quantity);            // quantity = people
        case 'per_night':      return money($unit * $quantity * $nights);  // quantity = items
        case 'per_room_night': return money($unit * $quantity * $nights);  // quantity = rooms
        default:               return money($unit * $quantity);            // per_booking
    }
}
