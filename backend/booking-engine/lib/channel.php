<?php
/* ===========================================================================
 *  STAYFLEXI — channel manager bridge.
 *
 *  WHY THIS FILE MATTERS
 *  ---------------------------------------------------------------------------
 *  Stayflexi already sells your rooms on MakeMyTrip, Booking.com and the rest.
 *  If this website sells a room without telling Stayflexi, the same room can be
 *  sold twice — and during Rann Utsav that means turning a family away at the
 *  gate at midnight.
 *
 *  So there are exactly two safe ways to run:
 *
 *    A. stayflexi.enabled = true
 *       Availability is pulled from Stayflexi and every booking is pushed back
 *       to it. Stayflexi stays the single source of truth. This needs API
 *       credentials — ask Stayflexi support for API access for a custom
 *       booking engine and they will issue a key and your hotel id.
 *
 *    B. stayflexi.enabled = false
 *       The engine uses its own inventory table. This is fine for testing and
 *       fine if you close this site's rooms off in Stayflexi so the two never
 *       overlap — for example, hold back four cottages for direct bookings and
 *       give the rest to the OTAs. It is NOT safe to sell the same rooms in
 *       both places and reconcile by hand.
 *
 *  Nothing below sends anything anywhere until you fill in the credentials.
 * ======================================================================== */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/inventory.php';

function channel_enabled(): bool {
    return (bool) cfg('stayflexi.enabled', false) && cfg('stayflexi.api_key');
}

/* ---------------------------------------------------------------------------
 * A small HTTP helper. Returns [ok, status, body].
 * ------------------------------------------------------------------------ */
function sf_request(string $method, string $path, ?array $payload = null): array {
    $url = rtrim(cfg('stayflexi.base_url'), '/') . '/' . ltrim($path, '/');
    $ch = curl_init($url);

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . cfg('stayflexi.api_key'),
    ];
    if (cfg('stayflexi.api_secret')) $headers[] = 'X-API-Secret: ' . cfg('stayflexi.api_secret');

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => (int) cfg('stayflexi.timeout', 12),
        CURLOPT_CONNECTTIMEOUT => 6,
    ]);
    curl_trust_system_certs($ch);
    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    $body   = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err    = curl_error($ch);
    unset($ch);

    if ($body === false) {
        audit('stayflexi_error', 'http', $path, ['error' => $err]);
        return [false, 0, ['error' => $err]];
    }
    $decoded = json_decode($body, true);
    return [$status >= 200 && $status < 300, $status, $decoded ?? ['raw' => $body]];
}

/* ---------------------------------------------------------------------------
 * Pull availability for a date window into our inventory table.
 *
 * Run this from cron every few minutes:
 *    php /home/USER/booking-engine/bin/sync-inventory.php
 *
 * The exact response shape is confirmed with Stayflexi when they issue your
 * key; map it in the loop below. Everything else in the engine reads the
 * inventory table, so this is the only place that needs changing.
 * ------------------------------------------------------------------------ */
function channel_pull_inventory(array $property, string $from, string $to): array {
    if (!channel_enabled()) return ['ok' => false, 'reason' => 'stayflexi disabled'];

    [$ok, $status, $body] = sf_request('GET', sprintf(
        '/core/api/v1/inventory?hotelId=%s&startDate=%s&endDate=%s',
        urlencode($property['sf_hotel_id']), $from, $to));

    if (!$ok) return ['ok' => false, 'reason' => "HTTP $status", 'body' => $body];

    // Map Stayflexi room type ids to ours.
    $map = [];
    foreach (q("SELECT id, sf_room_type_id FROM room_types WHERE property_id = ?", [$property['id']]) as $rt) {
        if ($rt['sf_room_type_id']) $map[$rt['sf_room_type_id']] = (int) $rt['id'];
    }

    $written = 0;
    foreach (($body['roomTypes'] ?? $body['data'] ?? []) as $entry) {
        $sf_id = (string) ($entry['roomTypeId'] ?? $entry['id'] ?? '');
        if (!isset($map[$sf_id])) continue;
        $room_type_id = $map[$sf_id];

        foreach (($entry['availability'] ?? $entry['dates'] ?? []) as $day) {
            $date  = $day['date'] ?? null;
            $open  = $day['available'] ?? $day['availableRooms'] ?? null;
            if ($date === null || $open === null) continue;

            exec_sql(
                "REPLACE INTO inventory (room_type_id, stay_date, rooms_open, note, synced_at)
                 VALUES (?, ?, ?, 'stayflexi', ?)",
                [$room_type_id, $date, (int) $open, now()]);
            $written++;
        }
    }
    audit('stayflexi_pull', 'property', $property['id'], ['dates' => "$from..$to", 'rows' => $written]);
    return ['ok' => true, 'rows' => $written];
}

/**
 * What one room of a booking line was actually charged each night, before GST:
 * the line's amount spread over its nights in proportion to the stored nightly
 * prices. The stored `nightly` list is the base (double) price, so a single or a
 * triple sent it as-is told Stayflexi ₹6,500 for a night charged ₹8,000. The
 * nights add up exactly to the line's amount per room.
 */
function charged_nightly(array $line): array {
    $base = json_decode((string) ($line['nightly'] ?: '{}'), true) ?: [];
    if (!$base) return [];
    $per_room = (float) $line['subtotal'] / max(1, (int) $line['rooms']);
    $sum = array_sum($base);
    $out = []; $left = money($per_room); $dates = array_keys($base);
    foreach ($dates as $i => $d) {
        $out[$d] = $i === count($dates) - 1 ? money($left)
                 : money($sum > 0 ? $per_room * (float) $base[$d] / $sum : $per_room / count($dates));
        $left -= $out[$d];
    }
    return $out;
}

/* ---------------------------------------------------------------------------
 * Push a confirmed booking to Stayflexi so the OTAs are closed down.
 * Called right after payment succeeds.
 * ------------------------------------------------------------------------ */
function channel_push_booking(int $booking_id): array {
    if (!channel_enabled()) return ['ok' => false, 'reason' => 'stayflexi disabled'];

    $b = q1("SELECT b.*, p.sf_hotel_id FROM bookings b
               JOIN properties p ON p.id = b.property_id WHERE b.id = ?", [$booking_id]);
    if (!$b) return ['ok' => false, 'reason' => 'booking not found'];

    $rooms = [];
    foreach (q("SELECT br.*, rt.sf_room_type_id, rp.sf_rate_plan_id
                  FROM booking_rooms br
                  JOIN room_types rt ON rt.id = br.room_type_id
                  JOIN rate_plans rp ON rp.id = br.rate_plan_id
                 WHERE br.booking_id = ?", [$booking_id]) as $r) {
        $rooms[] = [
            'roomTypeId' => $r['sf_room_type_id'],
            'ratePlanId' => $r['sf_rate_plan_id'],
            'numRooms'   => (int) $r['rooms'],
            'adults'     => (int) $r['adults'],
            'children'   => (int) $r['children'],
            'nightly'    => charged_nightly($r),
            'amount'     => (float) $r['subtotal'],
        ];
    }

    $payload = [
        'hotelId'        => $b['sf_hotel_id'],
        'externalRef'    => $b['ref'],
        'source'         => 'Website Booking Engine',
        'status'         => 'CONFIRMED',
        'checkIn'        => $b['check_in'],
        'checkOut'       => $b['check_out'],
        'guest' => [
            'name'  => $b['guest_name'],
            'email' => $b['guest_email'],
            'phone' => $b['guest_phone'],
            'city'  => $b['guest_city'],
        ],
        'adults'         => (int) $b['adults'],
        'children'       => (int) $b['children'],
        'rooms'          => $rooms,
        'totalAmount'    => (float) $b['total'],
        'paidAmount'     => (float) $b['amount_paid'],
        'specialRequests'=> $b['special_requests'],
    ];

    [$ok, $status, $body] = sf_request('POST', '/core/api/v1/reservations', $payload);

    if ($ok) {
        update('bookings', $booking_id, [
            'sf_booking_id' => (string) ($body['reservationId'] ?? $body['bookingId'] ?? ''),
            'sf_synced_at'  => now(),
            'sf_sync_error' => null,
        ]);
        audit('stayflexi_push_ok', 'booking', $booking_id);
        return ['ok' => true, 'id' => $body['reservationId'] ?? null];
    }

    // Never lose the booking because the channel manager was down — record the
    // failure loudly so staff can enter it by hand, and show it in the admin.
    update('bookings', $booking_id, [
        'sf_sync_error' => "HTTP $status " . json_encode($body),
        'sf_synced_at'  => null,
    ]);
    audit('stayflexi_push_failed', 'booking', $booking_id, ['status' => $status, 'body' => $body]);
    return ['ok' => false, 'reason' => "HTTP $status", 'body' => $body];
}

/** Tell Stayflexi a booking was cancelled, so the room goes back on sale. */
function channel_cancel_booking(int $booking_id): array {
    if (!channel_enabled()) return ['ok' => false, 'reason' => 'stayflexi disabled'];
    $b = q1("SELECT b.*, p.sf_hotel_id FROM bookings b
               JOIN properties p ON p.id = b.property_id WHERE b.id = ?", [$booking_id]);
    if (!$b || !$b['sf_booking_id']) return ['ok' => false, 'reason' => 'not synced'];

    [$ok, $status, $body] = sf_request('POST',
        '/core/api/v1/reservations/' . urlencode($b['sf_booking_id']) . '/cancel',
        ['hotelId' => $b['sf_hotel_id'], 'reason' => $b['cancel_reason'] ?: 'Guest cancelled online']);

    audit($ok ? 'stayflexi_cancel_ok' : 'stayflexi_cancel_failed', 'booking', $booking_id, $body);
    // Remember a failed cancel on the booking: bin/retry-failed-sync.php sends it
    // again every hour, so the room comes back on sale on the OTAs by itself.
    update('bookings', $booking_id, [
        'sf_sync_error' => $ok ? null : 'Cancel not sent: HTTP ' . $status . ' ' . json_encode($body),
        'updated_at'    => now(),
    ]);
    return ['ok' => $ok, 'body' => $body];
}

/**
 * The fewest rooms free on any night in a Stayflexi availability reply, or null
 * when the reply does not say (an HTML error page, an empty list, a field
 * missing). Null means "could not confirm", never "sold out": a junk reply used to
 * count as 0 rooms and tell the guest the dates had just been taken.
 */
function sf_min_available($body): ?int {
    $days = is_array($body) ? ($body['roomTypes'][0]['availability'] ?? $body['data'] ?? null) : null;
    if (!is_array($days) || !$days) return null;
    $min = PHP_INT_MAX;
    foreach ($days as $day) {
        $n = is_array($day) ? ($day['available'] ?? $day['availableRooms'] ?? null) : null;
        if (!is_numeric($n)) return null;
        $min = min($min, (int) $n);
    }
    return $min;
}

/* ---------------------------------------------------------------------------
 * Last line of defence, called inside the booking transaction.
 *
 * With Stayflexi on and fail_closed set, a booking is refused when the channel
 * manager cannot confirm the room is still free. Losing one booking is far
 * cheaper than selling a room twice on a full-moon night.
 * ------------------------------------------------------------------------ */
function channel_verify_still_available(array $property, int $room_type_id, string $check_in, string $check_out, int $rooms): array {
    if (!channel_enabled()) return ['ok' => true, 'checked' => false];

    $rt = q1("SELECT sf_room_type_id FROM room_types WHERE id = ?", [$room_type_id]);
    if (!$rt || !$rt['sf_room_type_id']) {
        return cfg('stayflexi.fail_closed')
            ? ['ok' => false, 'reason' => 'This room is not mapped to the channel manager.']
            : ['ok' => true, 'checked' => false];
    }

    [$ok, $status, $body] = sf_request('GET', sprintf(
        '/core/api/v1/inventory?hotelId=%s&startDate=%s&endDate=%s&roomTypeId=%s',
        urlencode($property['sf_hotel_id']), $check_in, $check_out, urlencode($rt['sf_room_type_id'])));

    if (!$ok) {
        if (cfg('stayflexi.fail_closed')) {
            return ['ok' => false, 'reason' => 'We could not confirm availability just now. Please call us to book.'];
        }
        return ['ok' => true, 'checked' => false, 'warning' => "channel unreachable (HTTP $status)"];
    }

    $min = sf_min_available($body);
    if ($min === null) {
        audit('stayflexi_unreadable', 'room_type', $room_type_id, ['status' => $status, 'body' => array_slice((array) $body, 0, 5)]);
        if (cfg('stayflexi.fail_closed')) {
            return ['ok' => false, 'reason' => 'We could not confirm availability just now. Please call us to book.'];
        }
        return ['ok' => true, 'checked' => false, 'warning' => 'channel reply not understood'];
    }

    return $min >= $rooms
        ? ['ok' => true, 'checked' => true, 'available' => $min]
        : ['ok' => false, 'checked' => true, 'reason' => 'Those dates have just been taken. Please try different dates.'];
}
