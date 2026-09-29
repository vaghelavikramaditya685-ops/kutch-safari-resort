# Booking engine — Kutch Safari Resort & White Rann Camp

Direct bookings, taken on your own website, with Razorpay and a UPI QR code,
sitting alongside the Enquire Now route you already have.

Plain **PHP 8 + MySQL**. No build step, no npm, no framework — because the end
destination is a cPanel account, and PHP runs there with nothing to configure.

---

## What it does

- **Search → choose a room → add extras → pay**, the four-step flow used by the
  big Indian engines.
- **Several meal plans per room** (CP / MAP / AP for the resort, MAPAI for the
  camp), each with its own price, exactly as your tariffs are written.
- **A rate calendar** — peak dates are already loaded: Christmas/New Year
  19 Dec 2026 – 4 Jan 2027, Uttarayan 13–15 Jan, full moon 21–23 Jan.
- **GST in slabs.** 5% up to ₹7,500 a night and 18% above it, worked out per
  night, so a ₹7,499 tent and an ₹8,450 tent are taxed differently and correctly.
- **The guest chooses how to pay** — in full, 50% now, or at the property.
- **Sold-out dates suggest the next free ones** instead of dead-ending.
- **Add-ons** — transfers, gala dinner, camel cart, extra beds.
- **A guest can look up and cancel** their own booking with the reference and
  their phone number; refunds follow your published ladder.
- **An admin panel** for bookings, availability, rates, enquiries and CSV export.
- **Stayflexi bridge** so the same room is never sold twice.

---

## Files

```
booking-engine/
├── config.php            ← every setting and key lives here
├── schema.sql            ← the database
├── seed.sql              ← your rooms, rates, add-ons and packages
├── index.php             ← the booking engine a guest sees
├── manage.php            ← "my booking": look up and cancel
├── lib/
│   ├── db.php            small PDO helpers
│   ├── inventory.php     availability + pricing  ← the heart of it
│   ├── booking.php       quote, create, cancel
│   ├── payment.php       Razorpay + UPI QR
│   ├── channel.php       Stayflexi
│   └── mail.php          confirmation emails
├── api/                  the JSON endpoints the page calls
├── admin/                staff panel
├── bin/
│   ├── setup.php         creates the tables and loads your data
│   ├── sync-inventory.php        cron: pull availability from Stayflexi
│   └── retry-failed-sync.php     cron: resend bookings that did not land
└── assets/               engine CSS, JS and room photographs
```

---

## Putting it on cPanel

**1. Upload.** Put this whole folder in `public_html/book` on
kutchsafariresort.com. Both websites link to it, so it only needs to exist once.

**2. Make the database.** cPanel → MySQL Databases → create a database and a
user, give the user All Privileges. Note the names — cPanel prefixes them with
your account, e.g. `kutchsaf_booking`.

**3. Load the tables.** cPanel → phpMyAdmin → pick the database → Import →
`schema.sql`, then `seed.sql`. Or over SSH:

```bash
mysql -u USER -p DBNAME < schema.sql && mysql -u USER -p DBNAME < seed.sql
```

**4. Put your settings in `config.local.php`** next to `config.php`. That file
overrides anything in `config.php` and is never committed, so your keys stay out
of the repository:

```php
<?php
return [
  'db' => ['driver' => 'mysql', 'host' => 'localhost',
           'name' => 'kutchsaf_booking', 'user' => 'kutchsaf_book', 'pass' => '••••'],
  'base_url' => 'https://www.kutchsafariresort.com/book',
  'razorpay' => ['enabled' => true, 'key_id' => 'rzp_live_…',
                 'key_secret' => '…', 'webhook_secret' => '…'],
  'upi'      => ['enabled' => true, 'vpa' => 'yourname@okhdfcbank'],
  'debug'    => false,
];
```

**5. Create your login.**

```bash
php bin/setup.php --admin "you@kutchsafariresort.com" "Your Name" "a-long-password"
```

**6. Two cron jobs** (cPanel → Cron Jobs), once Stayflexi is connected:

```
*/10 * * * *  /usr/local/bin/php /home/USER/public_html/book/bin/sync-inventory.php
0    * * * *  /usr/local/bin/php /home/USER/public_html/book/bin/retry-failed-sync.php
```

**7. Check `.htaccess` uploaded.** It forces HTTPS and blocks the web from
reading `config.php` and the `data` and `bin` folders. Hidden files are easy to
miss — turn on "Show Hidden Files" in the cPanel File Manager.

---

## Stayflexi — read this part

Stayflexi is what stops the same tent being sold here and on MakeMyTrip at once.
There are two safe ways to run, and one unsafe one.

**Safe — connected.** Ask Stayflexi support for **API access for a custom
booking engine**. They issue an API key and your hotel id. Put them in
`config.local.php`, set `stayflexi.enabled = true`, fill in `sf_hotel_id` on each
property and `sf_room_type_id` on each room type, and the cron jobs keep the two
in step. Availability is read from Stayflexi and every booking is pushed back to
it within seconds.

**Safe — separated.** Leave Stayflexi off here, and in Stayflexi hold some rooms
back from the OTAs — say four cottages and four tents kept for direct bookings.
Set the same numbers in Availability in the admin panel. The two systems never
touch the same room, so neither can oversell.

**Not safe.** Selling all twenty rooms here *and* all twenty on the OTAs, and
reconciling by hand. On a full-moon night in January you will double-sell.

The engine is built for the first option. Until the key arrives, use the second.
`config.php` has `fail_closed` set to true, which means that if Stayflexi is
connected but unreachable, a booking is refused rather than risked. Losing one
booking costs less than turning a family away at the gate.

If Stayflexi will not give you API access, tell me — the fallback is to embed
their own booking engine behind your design, which is what Rann Riders does with
DJUBO.

---

## Razorpay

1. Sign up at razorpay.com and finish KYC — they will want PAN, GST and the
   hotel's bank account. Allow a few days.
2. Settings → API Keys → generate **test** keys first (`rzp_test_…`).
3. Settings → Webhooks → add
   `https://www.kutchsafariresort.com/book/api/webhook-razorpay.php`
   with the events `payment.captured`, `payment.failed`, `refund.processed`.
   Copy the webhook secret into your config.
4. Make one real test booking with a test card, then switch to the live keys.

The webhook matters: if a guest closes the tab the instant after paying, the
browser never reports back, and the webhook is what confirms the booking anyway.

**Fees.** Razorpay takes roughly 2% + GST on cards and UPI. On a ₹19,942 booking
that is about ₹470.

---

## The UPI QR code

A second way to pay that sends money straight to your bank account with **no
gateway fee**. Put the bank account's UPI id in `config.local.php`:

```php
'upi' => ['enabled' => true, 'vpa' => 'kutchsafari@okhdfcbank'],
```

The guest scans, pays, and sees a reference number. **The website is not told
that the money arrived** — nothing can tell it, because the payment never touches
Razorpay. So the booking waits, and the admin panel shows a "UPI payments waiting
to be checked" box at the top of the bookings list. Someone checks the bank,
presses "Money received", and only then is the booking confirmed and the room
closed on Stayflexi.

That is the real trade-off: no fee, but a person has to look. Razorpay is the
default for exactly that reason. The QR is best for large bookings where the fee
is worth a phone call, and for guests who ask for it.

---

## Changing things

**Prices.** Admin → Rates. Choose the plans, the dates, the new nightly price.
Leave a date alone and it uses the base price from `rate_plans`.

**How many rooms are on sale.** Admin → Availability. Type a new number into any
night to close rooms off — for maintenance, or to hold some back for the OTAs.

**Add-ons, rooms, meal plans.** In the database tables `addons`, `room_types`
and `rate_plans`. Edit them in phpMyAdmin, or re-run `seed.sql` after editing it.

**Tax, cancellation terms, deposit percentage, how many rooms may be booked
online.** All at the top of `config.php`, with a comment on each.

**Look and feel.** `assets/engine.css`. The tokens at the top match the two
websites, and each property's accent colour comes from its row in `properties`.

---

## Before you go live

1. **Confirm the GST slabs with your accountant.** `config.php` has 5% up to
   ₹7,500 and 18% above. These have changed more than once; the website has said
   12% in the past. Getting it wrong is a tax problem, not a website problem.
2. **Confirm the resort's room rates.** The figures in `seed.sql` came from the
   tariff page, which was labelled for an earlier season.
3. **Test one real booking end to end** with live Razorpay keys and a real card,
   then refund it from the Razorpay dashboard.
4. **Check the confirmation email arrives** and is not treated as spam. If it is,
   turn on SMTP in `config.php` and send through the mailbox cPanel gives you.
5. **Set `debug` to false.** With it on, error details are shown to guests.
6. **Decide the Stayflexi arrangement** from the three options above, and write
   down which rooms are sold where.

---

## What is deliberately not built yet

- **Package booking online.** The Colors of Kutch packages are in the database
  with their pricing tiers and itineraries, but they are still enquiry-only at
  checkout. They need a vehicle, a driver and a guide, and those cannot be
  confirmed by a payment form. Say the word and I will add them as a bookable
  product with a deposit.
- **A guest login.** Bookings are looked up by reference plus phone number, which
  is what most Indian hotel engines do and is one less password for a guest.
- **Automatic GST invoices.** The data is all captured; the PDF is not written.
- **Multi-currency.** Prices are in rupees only. The plumbing is there if you
  start taking foreign bookings.
