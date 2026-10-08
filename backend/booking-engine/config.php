<?php
/* ===========================================================================
 *  EVERY SETTING AND CREDENTIAL LIVES IN THIS ONE FILE.
 *  ---------------------------------------------------------------------------
 *  Copy this to config.local.php on the server and put the real keys there —
 *  config.local.php is ignored by git and overrides anything below, so your
 *  secrets never end up in the repository.
 * ======================================================================== */

/* --- Database ------------------------------------------------------------
 * On cPanel these come from MySQL Databases. The user is usually prefixed
 * with your cPanel account name, e.g. kutchsaf_booking.
 */
$CONFIG = [

  'db' => [
    'driver'   => 'sqlite',           // 'sqlite' for local testing; the live server's config.local.php must set 'mysql'
    'host'     => 'localhost',
    'name'     => 'kutch_booking',
    'user'     => 'root',
    'pass'     => '',
    'sqlite_path' => __DIR__ . '/data/booking.sqlite',   // only used when driver = sqlite
  ],

  /* --- Where the engine lives -------------------------------------------
   * The public URL of this folder, no trailing slash. Used to build links in
   * confirmation emails and Razorpay callbacks.
   */
  'base_url' => 'http://localhost:8080',

  /* --- Razorpay ----------------------------------------------------------
   * Dashboard → Settings → API Keys. Start with the TEST keys (rzp_test_…),
   * switch to live only once a test booking has gone end to end.
   * The webhook secret is set in Dashboard → Settings → Webhooks, pointing at
   *   <base_url>/api/webhook-razorpay.php
   * with the events payment.captured, payment.failed and refund.processed.
   */
  'razorpay' => [
    'enabled'        => false,        // flip to true once keys are in
    'key_id'         => '',           // rzp_test_xxxxxxxx
    'key_secret'     => '',
    'webhook_secret' => '',
    'currency'       => 'INR',
  ],

  /* --- Direct UPI QR -----------------------------------------------------
   * This bypasses Razorpay and pays your bank account directly, so there are
   * no gateway fees — but nothing tells the website the money arrived. The
   * booking is held as 'awaiting payment' until a staff member marks it
   * received in the admin panel. Use it for large or offline-arranged
   * bookings; leave Razorpay as the default for instant confirmation.
   */
  'upi' => [
    'enabled'    => true,
    'vpa'        => '',               // e.g. kutchsafari@okhdfcbank  — the UPI id of the bank account
    'payee_name' => 'Kutch Safari Resort',
    // Minutes a UPI QR stays valid before the held rooms are released.
    'hold_minutes' => 45,
    // Once the guest has scanned the QR, the cottage stays held while the desk
    // checks the bank — for at most this many hours. After that it goes back on
    // sale; the payment can still be confirmed if the cottage is still free.
    'confirm_hours' => 48,
  ],

  /* --- Test payments (TESTING ONLY) --------------------------------------
   * Adds an "I've paid (test)" button to the payment step. It confirms the
   * booking exactly as a real payment would — marked paid, rooms taken out of
   * inventory, confirmation sent — but NO MONEY IS TAKEN. For testing the flow
   * before Razorpay is connected. SET enabled TO false BEFORE GOING LIVE, or
   * anyone can confirm a booking without paying.
   */
  'test_payments' => [
    'enabled' => true,
  ],

  /* --- Terms and conditions ----------------------------------------------
   * The terms PDF (terms.php) and every receipt are written from the settings
   * above — check-in/out times, the cancellation ladder, payment options,
   * GST. Add the property's own house rules here, one sentence each, e.g.
   *   'Pets are not allowed.'
   */
  'terms' => [
    'house_rules' => [],
  ],

  /* --- Stayflexi ---------------------------------------------------------
   * Your channel manager, and the thing that stops a room being sold twice
   * across MakeMyTrip, Booking.com and this site.
   *
   * Ask Stayflexi support for API access for a custom booking engine; they
   * will give you an API key and your hotel id. Until 'enabled' is true the
   * engine uses its own inventory table, which means YOU must keep this site's
   * availability in step with Stayflexi by hand. Read the README before going
   * live on your own inventory.
   */
  'stayflexi' => [
    'enabled'   => false,
    'base_url'  => 'https://api.stayflexi.com',   // confirm with Stayflexi support
    'api_key'   => '',
    'api_secret'=> '',
    // Fail closed: if Stayflexi cannot be reached, refuse the booking rather
    // than risk selling a room the channel manager has already sold.
    'fail_closed' => true,
    'timeout'   => 12,
  ],

  /* --- Tax ---------------------------------------------------------------
   * Indian GST on hotel rooms is charged in slabs on the per-night tariff.
   * These are the defaults; CONFIRM THE CURRENT RATES WITH YOUR ACCOUNTANT
   * before going live — the slabs and percentages have changed more than once.
   * Each row: [up to this nightly price, percent].  Use PHP_INT_MAX for the top slab.
   */
  'tax_slabs' => [
    [7500, 5.0],
    [PHP_INT_MAX, 18.0],
  ],
  'addon_tax_rate' => 5.0,

  /* Properties whose published tariff already INCLUDES GST (by property code).
   * For these the engine takes GST out of the price instead of adding it on
   * top — the guest pays exactly the tariff. The resort's 2026–27 tariff is
   * GST-inclusive; the camp's leaflet says "+ GST", so it is not listed. */
  'prices_include_tax' => ['kutch-safari-resort'],

  /* --- What the guest may choose at checkout -----------------------------
   * Turn any of these off and it disappears from the payment step.
   */
  'payment_modes' => [
    'full'    => ['enabled' => true,  'label' => 'Pay in full now',
                  'note' => 'Free cancellation up to 30 days before arrival.'],
    'advance' => ['enabled' => true,  'label' => 'Pay 50% now',
                  'percent' => 50,
                  // Only offered while the stay is more than this many days away: after
                  // that the rest would already be due, so the guest pays in full.
                  'balance_days_before' => 30,
                  'note' => 'Balance due 30 days before arrival. Free cancellation up to 30 days before arrival.'],
    // Off: every booking is paid 50% or in full when it is made; nothing is left fully unpaid.
    'hotel'   => ['enabled' => false, 'label' => 'Pay at the property',
                  'note' => 'We hold the room for 48 hours. Reservations will call to confirm.',
                  // Pay-at-hotel is riskier, so it is only offered outside the busy window.
                  'min_days_before_arrival' => 14],
  ],

  /* --- Cancellation ladder ----------------------------------------------
   * Days before arrival => percentage of the total charged if cancelled.
   * Matches the policy already published on the website.
   */
  'cancellation' => [
    ['days_before' => 30, 'charge_percent' => 0],
    ['days_before' => 21, 'charge_percent' => 75],
    ['days_before' => 0,  'charge_percent' => 100],
  ],

  /* --- Booking rules -----------------------------------------------------*/
  'rules' => [
    'max_nights'        => 21,
    'max_rooms_online'  => 5,      // larger groups are sent to the enquiry form
    'max_days_ahead'    => 730,    // bookings up to two years ahead
    'max_extra_quantity'=> 50,     // most of one extra that can be booked online (cars, dinners…)
    'min_advance_hours' => 2,      // no same-minute bookings
    'hold_minutes'      => 20,     // inventory hold while paying by card
    'child_free_under'  => 6,
    // SAMPLE MODE: the "Who is coming?" details are optional so the flow can be
    // tested without typing them each time. Only what is typed is saved —
    // nothing is filled in. SET THIS TO false BEFORE GOING LIVE.
    'guest_details_optional' => true,
  ],

  /* --- Email -------------------------------------------------------------
   * cPanel hosts nearly always allow PHP mail(). If yours does not, or mail
   * lands in spam, set smtp.enabled and fill in the details from
   * cPanel → Email Accounts → Connect Devices.
   */
  'mail' => [
    // false sends nothing at all (logged as mail_skipped) — for a development PC.
    'enabled'    => true,
    'from_email' => 'reservations@kutchsafariresort.com',
    'from_name'  => 'Kutch Safari Resort',
    'bcc_office' => 'kutchsafaribhuj@yahoo.com',
    'smtp' => [
      'enabled' => false,
      'host'    => 'mail.kutchsafariresort.com',
      'port'    => 587,
      'user'    => '',
      'pass'    => '',
      'secure'  => 'tls',
    ],
  ],

  /* --- WhatsApp / phone shown throughout --------------------------------*/
  'contact' => [
    'whatsapp' => '919925238599',
    'phone'    => '+91 99252 38599',
  ],

  /* --- Admin panel -------------------------------------------------------*/
  'admin' => [
    'session_name'   => 'kutch_admin',
    // The password is asked for every time: the sign-in ends when the browser
    // closes, only counts in the tab it was made in (a new tab or window asks
    // again), and ends after this many minutes without using the admin panel.
    'idle_minutes'   => 10,
    // Bookings list: finished and cancelled bookings drop off the list after this
    // many days (they are kept, not deleted — "Show older bookings" or a search finds them).
    'list_clear_days' => 15,
    'login_attempts' => 6,        // per 15 minutes, per IP
  ],

  /* --- Which websites may call this engine from the browser --------------
   * Add every domain the sites run on, including the Netlify preview.
   */
  'allowed_origins' => [
    'https://www.kutchsafariresort.com',
    'https://kutchsafariresort.com',
    'https://www.whiterann.com',
    'https://whiterann.com',
    'https://incandescent-alpaca-652856.netlify.app',
    // The React site in this repo, and its dev server (pnpm dev proxies /book/ to the engine).
    'https://kutchsafariresort.in',          // the main website since 8 Oct 2026
    'https://www.kutchsafariresort.in',
    'https://kutchsafaribhuj.in',
    'https://www.kutchsafaribhuj.in',
    'https://kutch-safari-resort.vercel.app',
    'http://localhost:3000',
    'http://127.0.0.1:3000',
    'http://localhost:4321',
    'http://127.0.0.1:4321',
    'http://localhost:8080',
    'http://127.0.0.1:8080',
  ],

  /* --- Proxies in front of this engine -----------------------------------
   * Only if the engine sits behind a proxy or CDN (a Vercel rewrite, Cloudflare):
   * list the proxy's own addresses here, so each guest's real address is read
   * from X-Forwarded-For for the rate limits. Leave empty when guests reach the
   * engine directly (cPanel), or every guest could claim any address.
   */
  'trusted_proxies' => [],

  /* --- Developer ---------------------------------------------------------*/
  'debug'    => true,    // set to false on the live site: hides error details
  'timezone' => 'Asia/Kolkata',
];

/* Local overrides — never committed. */
if (is_file(__DIR__ . '/config.local.php')) {
    $local = require __DIR__ . '/config.local.php';
    if (is_array($local)) {
        // Merge two levels deep so you can override just one key.
        foreach ($local as $section => $values) {
            if (is_array($values) && isset($CONFIG[$section]) && is_array($CONFIG[$section])) {
                $CONFIG[$section] = array_replace_recursive($CONFIG[$section], $values);
            } else {
                $CONFIG[$section] = $values;
            }
        }
    }
}

date_default_timezone_set($CONFIG['timezone']);

/** Read a setting with a dotted path: cfg('razorpay.key_id') */
function cfg(string $path, $default = null) {
    global $CONFIG;
    $node = $CONFIG;
    foreach (explode('.', $path) as $key) {
        if (!is_array($node) || !array_key_exists($key, $node)) return $default;
        $node = $node[$key];
    }
    return $node;
}

return $CONFIG;
