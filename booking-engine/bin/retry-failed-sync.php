<?php
/* Any booking that did not reach Stayflexi is retried here. Run hourly:
 *     /usr/local/bin/php /home/USER/booking-engine/bin/retry-failed-sync.php
 * A room that Stayflexi does not know about is a room the OTAs can still sell.
 */
require_once __DIR__ . '/../lib/channel.php';

if (!channel_enabled()) { echo "Stayflexi is not configured.\n"; exit(0); }

$rows = q("SELECT id, ref FROM bookings
            WHERE status IN ('pending','confirmed') AND sf_synced_at IS NULL
              AND check_out >= ? ORDER BY id", [date('Y-m-d')]);
if (!$rows) { echo "Nothing waiting to sync.\n"; exit(0); }

foreach ($rows as $b) {
    $r = channel_push_booking((int) $b['id']);
    printf("%-14s %s\n", $b['ref'], $r['ok'] ? 'sent' : 'STILL FAILING: ' . ($r['reason'] ?? ''));
}
