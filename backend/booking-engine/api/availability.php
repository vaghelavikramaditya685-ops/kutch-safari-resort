<?php
/* Rooms and prices for a date range.
   GET/POST: property, check_in, check_out, rooms, occupancy ("2,1,3" — guests per room)
   Older links may send adults instead of occupancy; they are spread over the rooms. */
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../lib/inventory.php';
require_once __DIR__ . '/../lib/booking.php';

rate_limit('avail', 120, 60);

$property = q1("SELECT * FROM properties WHERE code = ? AND active = 1", [in_str('property')]);
if (!$property) json_fail('Unknown property.');

$check_in  = in_str('check_in');
$check_out = in_str('check_out');
$adults    = max(1, in_int('adults', 2));
$children  = max(0, in_int('children', 0));
$rooms     = max(1, min((int) cfg('rules.max_rooms_online', 5), in_int('rooms', 1)));
$occupancy = normalise_occupancy(in_str('occupancy'), $rooms, $adults);
$adults    = array_sum($occupancy);

if ($err = validate_dates($property, $check_in, $check_out)) {
    json_out(['ok' => false, 'error' => $err, 'season' => [
        'start' => $property['season_start'], 'end' => $property['season_end']]]);
}

$results = search_availability($property, $check_in, $check_out, $adults, $children, $rooms, $occupancy);

json_out([
    'ok'        => true,
    'property'  => [
        'code' => $property['code'], 'name' => $property['name'],
        'accent' => $property['accent'], 'phone' => $property['phone'],
        'check_in_time' => $property['check_in_time'], 'check_out_time' => $property['check_out_time'],
    ],
    'check_in'  => $check_in,
    'check_out' => $check_out,
    'nights'    => nights_between($check_in, $check_out),
    'adults'    => $adults,
    'children'  => $children,
    'rooms_wanted' => $rooms,
    'occupancy' => array_map(fn($g, $i) => ['room' => $i + 1, 'guests' => $g, 'label' => occupancy_label($g)],
                             $occupancy, array_keys($occupancy)),
    'prices_include_tax' => prices_include_tax($property),
    'results'   => $results,
    // When nothing is free, offer dates that are rather than a dead end.
    'next_available' => $results ? [] :
        next_available_dates($property, $check_in, nights_between($check_in, $check_out), $rooms),
    'addons'    => array_map(fn($a) => [
        'id' => (int) $a['id'], 'code' => $a['code'], 'name' => $a['name'],
        'description' => $a['description'], 'price' => (float) $a['price'],
        'price_type' => $a['price_type'], 'image' => $a['image'],
        'min_quantity' => max(1, (int) ($a['min_quantity'] ?? 1)),
        'per_room' => addon_is_per_room($a),
    ], list_addons((int) $property['id'])),
]);
