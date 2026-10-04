<?php
/* ===========================================================================
 *  The booking engine a guest sees.
 *
 *  One page, four steps, driven by the JSON endpoints in /api.
 *  Open it as:  /book/?property=kutch-safari-resort&check_in=…&check_out=…
 *  The two websites link here from every Book Now button.
 * ======================================================================== */

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/inventory.php';
require_once __DIR__ . '/lib/debug.php';

$code = $_GET['property'] ?? 'kutch-safari-resort';
$property = q1("SELECT * FROM properties WHERE code = ? AND active = 1", [$code]);
if (!$property) { http_response_code(404); exit('Unknown property.'); }

$site_url = $property['website_url'] ?: '/';
$today    = date('Y-m-d');
$in       = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['check_in']  ?? '') ? $_GET['check_in']  : '';
$out      = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['check_out'] ?? '') ? $_GET['check_out'] : '';
// No dates in the link (e.g. a plain Book Now button): open on the first
// bookable night — tomorrow, or the season's opening night if that is later —
// so the guest sees rooms and prices straight away instead of an empty page.
if (!$in || !$out || $out <= $in) {
    $first = date('Y-m-d', strtotime('+1 day'));
    if (!empty($property['season_start']) && $property['season_start'] > $first) $first = $property['season_start'];
    $in  = $first;
    $out = date('Y-m-d', strtotime($first . ' +1 day'));
}
$adults   = max(1, (int) ($_GET['adults'] ?? 2));
$children = max(0, (int) ($_GET['children'] ?? 0));
$rooms    = max(1, min((int) cfg('rules.max_rooms_online', 5), (int) ($_GET['rooms'] ?? 1)));
// Guests per room: ?occupancy=2,1,3, or spread from ?adults= for older links.
$occupancy = normalise_occupancy($_GET['occupancy'] ?? '', $rooms, $adults);
$e        = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Book your stay — <?= $e($property['name']) ?></title>
<link rel="icon" type="image/svg+xml" href="assets/icon.svg">
<meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Marcellus&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/engine.css?v=<?= filemtime(__DIR__ . '/assets/engine.css') ?>">
<style>body{ --clay: <?= $e($property['accent']) ?>; }</style>
</head>
<body
  data-property="<?= $e($property['code']) ?>"
  data-guest-optional="<?= cfg('rules.guest_details_optional') ? '1' : '0' ?>"
  data-checkin="<?= $e($in) ?>" data-checkout="<?= $e($out) ?>"
  data-adults="<?= $adults ?>" data-children="<?= $children ?>" data-rooms="<?= $rooms ?>" data-occupancy="<?= $e(implode(',', $occupancy)) ?>">
<?php debug_bar(); /* local only; see lib/debug.php */ ?>

<header class="eng-header">
  <div class="wrap">
    <a class="eng-brand" href="<?= $e($site_url) ?>">
      <strong><?= $e($property['name']) ?></strong>
    </a>
    <nav>
      <a href="<?= $e($site_url) ?>">Back to website</a>
      <a href="manage.php">Already booked? Check status</a>
      <a href="tel:<?= $e(preg_replace('/\s/', '', $property['phone'])) ?>"><?= $e($property['phone']) ?></a>
    </nav>
  </div>
</header>

<div class="wrap">
  <div id="lastBooking"></div>
  <div class="steps" id="steps">
    <div class="step is-active" data-step="1"><b>1</b><span>Choose a room</span></div>
    <div class="step" data-step="2"><b>2</b><span>Add extras</span></div>
    <div class="step" data-step="3"><b>3</b><span>Your details</span></div>
    <div class="step" data-step="4"><b>4</b><span>Payment</span></div>
  </div>

  <!-- Dates and guests. Always visible so a guest can change their mind. -->
  <form class="searchbar" id="searchForm">
    <div class="field">
      <label for="f-in">Check in</label>
      <input type="date" id="f-in" name="check_in" min="<?= $today ?>" value="<?= $e($in) ?>" required>
    </div>
    <div class="field">
      <label for="f-out">Check out</label>
      <input type="date" id="f-out" name="check_out" min="<?= $today ?>" value="<?= $e($out) ?>" required>
    </div>
    <div class="field">
      <label for="f-rooms">Rooms</label>
      <select id="f-rooms" name="rooms">
        <?php for ($i = 1; $i <= (int) cfg('rules.max_rooms_online', 5); $i++): ?>
          <option value="<?= $i ?>" <?= $i === $rooms ? 'selected' : '' ?>><?= $i ?> room<?= $i > 1 ? 's' : '' ?></option>
        <?php endfor; ?>
      </select>
    </div>
    <div class="field field--action">
      <button class="btn btn--block" type="submit">Check availability</button>
    </div>
    <!-- One Single / Double / Triple choice per room; engine.js draws one per room. -->
    <div class="field field--occ">
      <span class="field__label">Guests in each room</span>
      <div class="occ" id="occList"></div>
    </div>
  </form>

  <div id="alert"></div>

  <div class="layout">
    <main id="stage">
      <div class="loading"><div class="spinner"></div>Checking what is free on those dates…</div>
    </main>

    <aside>
      <div class="summary" id="summary"></div>

      <!-- The offline route stays open next to the engine, exactly as asked. -->
      <div class="panel" style="margin-top:16px;border-left-color:var(--gold)">
        <h3 style="font-size:1rem">Rather talk to someone?</h3>
        <p style="font-size:.85rem;margin-bottom:12px">
          Groups, packages and anything unusual are easiest by phone. We answer all day.
        </p>
        <a class="btn btn--ghost btn--sm btn--block" style="margin-bottom:8px"
           href="tel:<?= $e(preg_replace('/\s/', '', $property['phone'])) ?>">Call <?= $e($property['phone']) ?></a>
        <a class="btn btn--plain btn--sm btn--block" target="_blank" rel="noopener"
           href="https://wa.me/<?= $e(cfg('contact.whatsapp')) ?>">Enquire on WhatsApp</a>
      </div>
    </aside>
  </div>

  <div class="trust">
    <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 13 4 4L19 7"/></svg> Secure payment</span>
    <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 13 4 4L19 7"/></svg> Best rate, booked direct</span>
    <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 13 4 4L19 7"/></svg> Free cancellation up to 30 days before arrival</span>
    <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 13 4 4L19 7"/></svg> Confirmation by email and WhatsApp</span>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
<?php if (cfg('razorpay.enabled')): ?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<?php endif; ?>
<script src="assets/engine.js?v=<?= filemtime(__DIR__ . '/assets/engine.js') ?>"></script>
</body>
</html>
