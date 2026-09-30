<?php
/* ===========================================================================
 *  Stayflexi connection tester and API prober.
 *
 *      php bin/check-stayflexi.php            check credentials + show mapping
 *      php bin/check-stayflexi.php --probe    try the likely endpoints and
 *                                             print the shape of what comes back
 *
 *  WHY THE PROBE EXISTS
 *  ---------------------------------------------------------------------------
 *  Stayflexi does not publish its API, so the endpoint paths in lib/channel.php
 *  are informed guesses. Once you have credentials, run --probe and send me the
 *  output: it prints the response structure (field names, not guest data), which
 *  is all that is needed to finish the adapter properly.
 * ======================================================================== */

require_once __DIR__ . '/../lib/channel.php';

function mask(?string $s): string {
    if (!$s) return '(not set)';
    return strlen($s) <= 8 ? str_repeat('•', strlen($s))
         : substr($s, 0, 4) . str_repeat('•', max(4, strlen($s) - 8)) . substr($s, -4);
}

/** Describe a structure without printing the values inside it. */
function shape($node, int $depth = 0, int $max = 4) {
    $pad = str_repeat('  ', $depth + 1);
    if ($depth > $max) return "$pad...\n";
    if (is_array($node)) {
        if (!$node) return $pad . "(empty array)\n";
        if (array_is_list($node)) {
            $out = $pad . "[ list of " . count($node) . " ]\n";
            return $out . shape($node[0], $depth + 1, $max);
        }
        $out = '';
        foreach ($node as $k => $v) {
            $type = is_array($v) ? (array_is_list($v) ? 'list' : 'object')
                  : (is_bool($v) ? 'bool' : (is_numeric($v) ? 'number' : 'string'));
            $out .= "$pad$k : $type\n";
            if (is_array($v)) $out .= shape($v, $depth + 1, $max);
        }
        return $out;
    }
    return $pad . gettype($node) . "\n";
}

echo "\nStayflexi configuration\n-----------------------\n";
printf("  enabled    : %s\n", cfg('stayflexi.enabled') ? 'yes' : 'NO — set stayflexi.enabled to true once you have a key');
printf("  base url   : %s\n", cfg('stayflexi.base_url') ?: '(not set)');
printf("  api key    : %s\n", mask((string) cfg('stayflexi.api_key')));
printf("  api secret : %s\n", cfg('stayflexi.api_secret') ? 'set' : '(not set — may not be required)');
printf("  fail closed: %s\n", cfg('stayflexi.fail_closed') ? 'yes — refuse a booking if Stayflexi is unreachable' : 'no');

/* --- What still needs mapping ----------------------------------------- */
echo "\nProperty and room mapping\n-------------------------\n";
$missing = 0;
foreach (q("SELECT * FROM properties ORDER BY id") as $p) {
    printf("  %-22s hotel id: %s\n", $p['code'], $p['sf_hotel_id'] ?: '— NOT MAPPED —');
    if (!$p['sf_hotel_id']) $missing++;
    foreach (q("SELECT * FROM room_types WHERE property_id = ? ORDER BY sort_order", [$p['id']]) as $rt) {
        printf("      %-28s room type id: %s\n", $rt['code'], $rt['sf_room_type_id'] ?: '— NOT MAPPED —');
        if (!$rt['sf_room_type_id']) $missing++;
        foreach (q("SELECT * FROM rate_plans WHERE room_type_id = ?", [$rt['id']]) as $rp) {
            printf("          %-24s rate plan id: %s\n", $rp['code'], $rp['sf_rate_plan_id'] ?: '— not mapped —');
        }
    }
}
if ($missing) {
    echo "\n  $missing item(s) still need a Stayflexi id. Set them with:\n";
    echo "     php bin/map-stayflexi.php property kutch-safari-resort  HOTEL_ID\n";
    echo "     php bin/map-stayflexi.php room     kutchi-ac-cottage    ROOM_TYPE_ID\n";
}

if (!channel_enabled()) {
    echo "\nStayflexi is not switched on, so nothing was contacted.\n";
    echo "Until it is, keep this website's rooms separate from the ones the OTAs sell —\n";
    echo "see the Stayflexi section of docs/05-BOOKING-ENGINE.md.\n\n";
    exit(0);
}

/* --- Live check -------------------------------------------------------- */
$probe = in_array('--probe', $argv ?? [], true);
$property = q1("SELECT * FROM properties WHERE sf_hotel_id IS NOT NULL AND sf_hotel_id <> '' LIMIT 1");
if (!$property) { echo "\nNo property has a Stayflexi hotel id yet — map one first.\n\n"; exit(1); }

$from = date('Y-m-d');
$to   = date('Y-m-d', strtotime('+14 day'));

$candidates = $probe ? [
    "/core/api/v1/inventory?hotelId={$property['sf_hotel_id']}&startDate=$from&endDate=$to",
    "/api/v1/inventory?hotelId={$property['sf_hotel_id']}&startDate=$from&endDate=$to",
    "/core/api/v1/hotels/{$property['sf_hotel_id']}/availability?startDate=$from&endDate=$to",
    "/core/api/v1/roomTypes?hotelId={$property['sf_hotel_id']}",
    "/core/api/v1/rateplans?hotelId={$property['sf_hotel_id']}",
] : [
    "/core/api/v1/inventory?hotelId={$property['sf_hotel_id']}&startDate=$from&endDate=$to",
];

echo "\nContacting Stayflexi (" . cfg('stayflexi.base_url') . ")\n";
echo str_repeat('-', 60) . "\n";

$any = false;
foreach ($candidates as $path) {
    [$ok, $status, $body] = sf_request('GET', $path);
    printf("  %-3s %s\n", $status ?: '---', $path);
    if ($ok) {
        $any = true;
        echo "      response shape:\n" . shape($body, 1);
    } elseif ($probe && $status && $status !== 404) {
        $msg = $body['message'] ?? $body['error'] ?? null;
        if ($msg) echo "      says: " . (is_string($msg) ? $msg : json_encode($msg)) . "\n";
    }
}

echo str_repeat('-', 60) . "\n";
if ($any) {
    echo "At least one endpoint answered. Send the shape above to your developer and\n";
    echo "the field mapping in lib/channel.php can be finished in minutes.\n\n";
} else {
    echo "Nothing answered successfully.\n";
    echo "  • 401/403 — the key is wrong, or not enabled for this hotel.\n";
    echo "  • 404     — the paths are wrong; ask Stayflexi for their API reference.\n";
    echo "  • 000     — cannot reach the host; check stayflexi.base_url.\n\n";
    echo "Run with --probe to try more paths.\n\n";
}
