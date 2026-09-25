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
    'driver'   => 'mysql',            // 'mysql' in production, 'sqlite' for local testing
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

  /* --- What the guest may choose at checkout -----------------------------
   * Turn any of these off and it disappears from the payment step.
   */
  'payment_modes' => [
    'full'    => ['enabled' => true,  'label' => 'Pay in full now',
                  'note' => 'Free cancellation up to 30 days before arrival.'],
    'advance' => ['enabled' => true,  'label' => 'Pay 50% now',
                  'percent' => 50,
                  'note' => 'Balance due 30 days before arrival. Free cancellation up to 30 days before arrival.'],
    'hotel'   => ['enabled' => true,  'label' => 'Pay at the property',
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
    'min_advance_hours' => 2,      // no same-minute bookings
    'hold_minutes'      => 20,     // inventory hold while paying by card
    'child_free_under'  => 6,
  ],

  /* --- Email -------------------------------------------------------------
   * cPanel hosts nearly always allow PHP mail(). If yours does not, or mail
   * lands in spam, set smtp.enabled and fill in the details from
   * cPanel → Email Accounts → Connect Devices.
   */
  'mail' => [
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
    'session_hours'  => 8,
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
