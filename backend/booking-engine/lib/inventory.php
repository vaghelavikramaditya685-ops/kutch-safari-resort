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

/** Whether this property's published tariff already includes GST (config: prices_include_tax). */
function prices_include_tax(array $property): bool {
    return in_array($property['code'], (array) cfg('prices_include_tax', []), true);
}

/**
 * Split one night's tariff into [before tax, tax].
 *
 * Exclusive: the tariff is the taxable value, GST goes on top.
 * Inclusive: the tariff is what the guest pays; the slab is decided on the
 * value before tax (₹7,450 incl. 5% is ₹7,095 + ₹355, so it stays in the 5% slab).
 */
function tax_split(float $tariff, bool $inclusive): array {
    if (!$inclusive) {
        $tax = $tariff * tax_percent_for($tariff) / 100;
        return [$tariff, $tax];
    }
    foreach (cfg('tax_slabs', [[PHP_INT_MAX, 0]]) as [$upto, $percent]) {
        $net = $tariff / (1 + $percent / 100);
        if ($net <= $upto) return [$net, $tariff - $net];
    }
    return [$tariff, 0.0];
}

/* ---------------------------------------------------------------------------
 * Occupancy — guests choose Single, Double or Triple for each room.
 * ------------------------------------------------------------------------ */

function occupancy_label(int $guests): string {
    return [1 => 'Single', 2 => 'Double', 3 => 'Triple'][$guests] ?? "$guests guests";
}

/**
 * One entry per room: how many adults sleep in it. Accepts "2,1,3" or [2,1,3];
 * without one, spreads $adults over the rooms (the old adults + rooms search).
 */
function normalise_occupancy($occupancy, int $rooms, int $adults = 2): array {
    if (is_string($occupancy)) $occupancy = $occupancy === '' ? [] : explode(',', $occupancy);
    $list = array_map(fn($n) => max(1, min(3, (int) $n)), is_array($occupancy) ? $occupancy : []);
    if (count($list) !== $rooms) {
        $list = [];
        $left = max($rooms, $adults);
        for ($i = $rooms; $i > 0; $i--) {
            $n = max(1, min(3, (int) ceil($left / $i)));
            $list[] = $n;
            $left -= $n;
        }
    }
    return $list;
}

/**
 * Price a set of rooms of one type on one plan, each for its own occupancy.
 *
 *   Double = the plan's nightly rate (base_price, or the date's rate).
 *   Single = that rate less (base_price - single_price), so date and peak
 *            prices keep the same single discount. No single_price = Double.
 *   Triple = Double plus the room type's extra-bed charge (extra_adult_price)
 *            for each guest beyond base_occupancy.
 *
 * This is the only place room prices are worked out — the room list and the
 * checkout both use it, so what a guest sees is what the server charges.
 */
function price_rooms(array $property, array $room_type, array $plan, string $check_in, string $check_out, array $occupancy): ?array {
    $priced = price_rate_plan($plan, $check_in, $check_out);
    if ($priced === null) return null;

    $inclusive  = prices_include_tax($property);
    $single_off = ($plan['single_price'] ?? null) !== null && $plan['single_price'] !== ''
                ? max(0.0, (float) $plan['base_price'] - (float) $plan['single_price']) : 0.0;
    $base_occ   = (int) $room_type['base_occupancy'];
    $extra_bed  = (float) $room_type['extra_adult_price'];

    $units = [];
    $net = $tax = 0.0;
    foreach (array_values($occupancy) as $i => $guests) {
        $u_net = $u_tax = 0.0;
        foreach ($priced['nightly'] as $double) {
            $night = ($guests <= 1 ? max(0.0, $double - $single_off) : $double)
                   + max(0, $guests - $base_occ) * $extra_bed;
            [$n, $t] = tax_split($night, $inclusive);
            $u_net += $n;
            $u_tax += $t;
        }
        $units[] = ['room' => $i + 1, 'guests' => $guests, 'label' => occupancy_label($guests),
                    'subtotal' => money($u_net), 'tax' => money($u_tax), 'total' => money($u_net + $u_tax)];
        $net += $u_net;
        $tax += $u_tax;
    }

    return [
        'nightly'      => $priced['nightly'],
        'per_night'    => $priced['avg_night'],
        'subtotal'     => money($net),
        'tax'          => money($tax),
        'total'        => money($net + $tax),
        'units'        => $units,
        'adults'       => array_sum($occupancy),
        'extra_adults' => array_sum(array_map(fn($g) => max(0, $g - $base_occ), $occupancy)),
    ];
}

/* ---------------------------------------------------------------------------
 * How many rooms of a type are already committed on a date.
 * ------------------------------------------------------------------------ */
/**
 * While the desk changes a booking, that booking's own rooms must not count
 * against it — moving a stay by a night should not be refused because of itself.
 */
function availability_ignore_booking(?int $booking_id = null, bool $set = false): ?int {
    static $ignore = null;
    if ($set) $ignore = $booking_id;
    return $ignore;
}

function rooms_booked(int $room_type_id, string $date): int {
    // A confirmed booking always holds its rooms. A pending (unpaid) one holds
    // them only while the guest can still pay — the card window (rules.hold_minutes)
    // or the UPI QR window, whichever is longer — or once some money has been
    // paid, or while a UPI payment waits for the desk to confirm it. After that
    // an unpaid booking stops blocking rooms, so abandoned checkouts free up.
    $window = max((int) cfg('rules.hold_minutes', 20), (int) cfg('upi.hold_minutes', 45));
    $since  = date('Y-m-d H:i:s', time() - $window * 60);
    return (int) qval(
        "SELECT COALESCE(SUM(br.rooms), 0)
           FROM booking_rooms br
           JOIN bookings b ON b.id = br.booking_id
          WHERE br.room_type_id = ?
            AND b.check_in <= ? AND b.check_out > ?
            AND (b.status = 'confirmed'
                 OR (b.status = 'pending'
                     AND (b.amount_paid > 0 OR b.created_at > ?
                          OR EXISTS (SELECT 1 FROM payments p
                                      WHERE p.booking_id = b.id AND p.status = 'awaiting_confirmation'))))"
            . (availability_ignore_booking() ? " AND b.id <> " . (int) availability_ignore_booking() : ''),
        [$room_type_id, $date, $date, $since], 0);
}

/** An unpaid booking whose payment window has closed — it no longer holds rooms. */
function booking_lapsed(array $b): bool {
    if ($b['status'] !== 'pending' || (float) $b['amount_paid'] > 0) return false;
    $window = max((int) cfg('rules.hold_minutes', 20), (int) cfg('upi.hold_minutes', 45));
    if (strtotime($b['created_at']) > time() - $window * 60) return false;
    return !qval("SELECT 1 FROM payments WHERE booking_id = ? AND status = 'awaiting_confirmation'", [$b['id']]);
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
/**
 * Prices already agreed on a booking that the desk is changing. Set only while
 * pricing that change (quote_modification): a night the guest already had keeps
 * the price it was booked at — even if the tariff or a special price has moved
 * since — and so does an extra already on the booking. Only new nights, new
 * cottages and new extras are priced at today's rates.
 *   ['nightly' => [rate_plan_id => [date => price]], 'addons' => [addon_id => unit price]]
 */
function pricing_locks(?array $locks = null, bool $set = false): array {
    static $current = [];
    if ($set) $current = $locks ?? [];
    return $current;
}

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

    $locked = pricing_locks()['nightly'][(int) $plan['id']] ?? [];
    foreach ($dates as $d) {
        if (isset($locked[$d])) {
            // Already booked at this price: keep it, and a later stop-sell or
            // minimum stay does not take away a night the guest already has.
            $price = (float) $locked[$d];
            $nightly[$d] = money($price);
            $subtotal += $price;
            $tax += $price * tax_percent_for($price) / 100;
            continue;
        }
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
                             int $adults = 2, int $children = 0, int $rooms_wanted = 1, ?array $occupancy = null): array {
    $nights = nights_between($check_in, $check_out);
    $occupancy = normalise_occupancy($occupancy, $rooms_wanted, $adults);
    $results = [];

    $room_types = q("SELECT * FROM room_types WHERE property_id = ? AND active = 1 ORDER BY sort_order, id",
                    [$property['id']]);

    foreach ($room_types as $rt) {
        // Guests may mix cottage types across rooms, so a type is listed if it can
        // take at least one of the rooms: at least one free, and big enough for
        // that room's guests. `fits` says which rooms it can take.
        $fits = array_map(fn($g) => $g <= (int) $rt['max_adults'], $occupancy);
        if (!in_array(true, $fits, true)) continue;

        $free = rooms_free_for_stay($rt, $check_in, $check_out);
        if ($free < 1) continue;

        $plans = [];
        $extra_adults = 0;
        foreach (q("SELECT * FROM rate_plans WHERE room_type_id = ? AND active = 1 ORDER BY sort_order, id",
                   [$rt['id']]) as $plan) {
            $priced = price_rooms($property, $rt, $plan, $check_in, $check_out, $occupancy);
            if ($priced === null) continue;
            $extra_adults = $priced['extra_adults'];

            $plans[] = [
                'id'          => (int) $plan['id'],
                'code'        => $plan['code'],
                'name'        => $plan['name'],
                'meal_note'   => $plan['meal_note'],
                'refundable'  => (bool) $plan['refundable'],
                'nightly'     => $priced['nightly'],
                'per_night'   => $priced['per_night'],
                // Average for all the rooms chosen, per night, as the guest will pay it.
                'per_night_with_tax' => money($priced['total'] / max(1, $nights)),
                'units'       => $priced['units'],
                'subtotal'    => $priced['subtotal'],
                'tax'         => $priced['tax'],
                'total'       => $priced['total'],
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
            'fits'          => $fits,
            // True when this one type can take every room in the search.
            'fits_all'      => $free >= $rooms_wanted && !in_array(false, $fits, true),
            // Shown as a gentle nudge when stock is genuinely low — never invented.
            'low_stock'     => $free <= 3,
            'extra_adults'  => $extra_adults,
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
/**
 * Extras that belong to a particular room and can be had once per room —
 * the extra bed at the resort (`extra-bed`) and at the camp (`extra-bed-tent`).
 * The guest picks which rooms get one; quantity is how many rooms were picked.
 */
function addon_is_per_room(array $addon): bool {
    return strpos((string) $addon['code'], 'extra-bed') === 0;
}

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
