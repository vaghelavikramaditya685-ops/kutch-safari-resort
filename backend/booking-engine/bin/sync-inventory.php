<?php
/* ===========================================================================
 *  Pull availability from Stayflexi into this engine.
 *
 *  Add it to cPanel → Cron Jobs, every 10 minutes:
 *      /usr/local/bin/php /home/USER/booking-engine/bin/sync-inventory.php
 *
 *  Until Stayflexi API credentials are in config.php this prints a notice and
 *  exits without touching anything.
 * ======================================================================== */
require_once __DIR__ . '/../lib/channel.php';

if (!channel_enabled()) {
    echo "Stayflexi is not configured — nothing to sync.\n";
    echo "Add your API key and hotel ids to config.php, then set stayflexi.enabled = true.\n";
    exit(0);
}

$days = (int) ($argv[1] ?? 120);
$from = date('Y-m-d');
$to   = date('Y-m-d', strtotime("+$days day"));

foreach (q("SELECT * FROM properties WHERE active = 1 AND sf_hotel_id IS NOT NULL") as $p) {
    $r = channel_pull_inventory($p, $from, $to);
    printf("%-24s %s\n", $p['code'], $r['ok'] ? "{$r['rows']} nights updated" : "FAILED: {$r['reason']}");
}

// Tidy up carts people abandoned.
purge_expired_holds();
echo "Expired holds cleared.\n";
