<?php
/* ===========================================================================
 *  Record the ids Stayflexi uses for your property, rooms and rate plans.
 *
 *      php bin/map-stayflexi.php property kutch-safari-resort  HOTEL_ID
 *      php bin/map-stayflexi.php room     kutchi-ac-cottage    ROOM_TYPE_ID
 *      php bin/map-stayflexi.php plan     1                    RATE_PLAN_ID
 *      php bin/map-stayflexi.php list
 *
 *  The codes are the short names in your own database (shown by `list`);
 *  the ids are whatever Stayflexi calls the same things.
 * ======================================================================== */

require_once __DIR__ . '/../lib/db.php';

$what = $argv[1] ?? 'list';

if ($what === 'list') {
    echo "\nYour codes (left) and their Stayflexi ids (right)\n";
    echo str_repeat('-', 66) . "\n";
    foreach (q("SELECT * FROM properties ORDER BY id") as $p) {
        printf("property  %-24s %s\n", $p['code'], $p['sf_hotel_id'] ?: '—');
        foreach (q("SELECT * FROM room_types WHERE property_id = ? ORDER BY sort_order", [$p['id']]) as $rt) {
            printf("  room    %-24s %s\n", $rt['code'], $rt['sf_room_type_id'] ?: '—');
            foreach (q("SELECT * FROM rate_plans WHERE room_type_id = ?", [$rt['id']]) as $rp) {
                printf("    plan  %-22s %s   (id %d)\n", $rp['code'], $rp['sf_rate_plan_id'] ?: '—', $rp['id']);
            }
        }
    }
    echo "\n";
    exit(0);
}

$code = $argv[2] ?? null;
$id   = $argv[3] ?? null;
if (!$code || $id === null) {
    echo "Usage: php bin/map-stayflexi.php property|room|plan  CODE  STAYFLEXI_ID\n";
    exit(1);
}

switch ($what) {
    case 'property':
        $row = q1("SELECT id FROM properties WHERE code = ?", [$code]);
        if (!$row) { echo "No property with code '$code'.\n"; exit(1); }
        update('properties', (int) $row['id'], ['sf_hotel_id' => $id]);
        break;
    case 'room':
        $row = q1("SELECT id FROM room_types WHERE code = ?", [$code]);
        if (!$row) { echo "No room type with code '$code'.\n"; exit(1); }
        update('room_types', (int) $row['id'], ['sf_room_type_id' => $id]);
        break;
    case 'plan':
        $row = q1("SELECT id FROM rate_plans WHERE id = ?", [(int) $code]);
        if (!$row) { echo "No rate plan with id '$code'.\n"; exit(1); }
        update('rate_plans', (int) $row['id'], ['sf_rate_plan_id' => $id]);
        break;
    default:
        echo "First argument must be property, room, plan or list.\n"; exit(1);
}

audit('stayflexi_mapped', $what, $code, ['sf_id' => $id], 'cli');
echo "Mapped $what '$code' to Stayflexi id '$id'.\n";
