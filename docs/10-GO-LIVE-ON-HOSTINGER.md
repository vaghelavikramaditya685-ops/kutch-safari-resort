# Going live on Hostinger (kutchsafaribhuj.in)

_Written 5 Oct 2026. **Not done yet**: today everything runs only on the owner's PC. Follow this when the site is uploaded. Nothing in the code needs to change for local use; the steps below are only for the live upload._

## Contents
1. [Addresses: on this PC vs live](#1-addresses-on-this-pc-vs-live)
2. [Hosting plan](#2-hosting-plan)
3. [What changes for the live upload](#3-what-changes-for-the-live-upload)
4. [Build the upload folder](#4-build-the-upload-folder)
5. [Set it up on Hostinger](#5-set-it-up-on-hostinger)
6. [Point the domain at Hostinger (DNS)](#6-point-the-domain-at-hostinger-dns)
7. [After it's live](#7-after-its-live)
8. [Updating the live site later](#8-updating-the-live-site-later)
9. [Never do this on the live site](#9-never-do-this-on-the-live-site)

---

## 1. Addresses: on this PC vs live

Decided by the owner on 5 Oct 2026: one Hostinger plan, one domain, three addresses. `book` and `admin` are subdomains of `kutchsafaribhuj.in` (no extra cost), and both point at the **same** booking-engine folder, so they share the same code and the same database.

| What | On this PC (now) | Live (after go-live) |
|---|---|---|
| Website | `http://localhost:4321` (`.claude/launch.json`; plain `pnpm dev` uses 3000) | `https://kutchsafaribhuj.in` |
| Booking (every Book Now) | `http://localhost:4321/book/` | `https://book.kutchsafaribhuj.in` |
| Check status | `http://localhost:4321/book/manage.php` | `https://book.kutchsafaribhuj.in/manage.php` |
| Admin panel | `http://localhost:4321/book/admin/` (or `/admin`) | `https://admin.kutchsafaribhuj.in` (also `kutchsafaribhuj.in/admin`) |
| Booking engine itself | `http://127.0.0.1:8080` (`php -S`, Vite proxies `/book/` to it) | folder `public_html/book/` on Hostinger |
| Database | SQLite, `backend/booking-engine/data/booking.sqlite` | MySQL on Hostinger (new, empty, filled by setup) |

**How it works on this PC:** `frontend/src/lib/booking.ts` links to `/book/`, and `vite.config.ts` forwards `/book/` to the PHP engine on `127.0.0.1:8080`. No `.htaccess` file is used locally (`php -S` and Vite ignore them).

---

## 2. Hosting plan

* Hostinger web hosting, the **₹249/month tier with daily backups** (the booking database holds real reservations and payments). Premium (₹149) also works but backs up only weekly; Single is too small.
* Needed and included: PHP 8, MySQL, SSH, cron, free SSL, subdomains.
* Not needed: Node.js, Vercel. Vercel cannot run PHP or MySQL, so it cannot host the booking engine or the admin panel.

---

## 3. What changes for the live upload

Five things. None of them is needed on this PC.

### 3.1 Booking and admin links → two build settings (no code edit)

Build the website with:

```
VITE_BOOKING_ENABLED=true
VITE_BOOKING_URL=https://book.kutchsafaribhuj.in/
```

`VITE_BOOKING_ENABLED=true` turns online booking back on: since 5 Oct 2026 the site is live on Vercel without it, so every Book Now is "Enquire Now" (→ `/enquire`), "Check status" is hidden and `/admin` is not found. `VITE_BOOKING_URL` (ends with `/`) is where the buttons go; `frontend/src/lib/booking.ts` builds every link from it:

| Link | Local build | Live build |
|---|---|---|
| Book Now (`bookingUrl()`) | `/book/?property=…` | `https://book.kutchsafaribhuj.in/?property=…` |
| Check status (`statusUrl()`) | `/book/manage.php` | `https://book.kutchsafaribhuj.in/manage.php` |
| Admin (`adminUrl()`, the website's `/admin`) | `/book/admin/` | `https://book.kutchsafaribhuj.in/admin/` → forwarded to `admin.` by 3.3 |

Without `VITE_BOOKING_ENABLED=true` the build has Enquire Now instead of Book Now. Without `VITE_BOOKING_URL` it links to `/book/`: right for this PC, wrong for the live site.

### 3.2 Website `.htaccess` (new file)

Hostinger runs Apache-compatible LiteSpeed, which needs this file so addresses like `/stay` load the site instead of a 404. Create it as **`frontend/public/.htaccess`** before building (the build copies it into the upload folder), or put it straight into `public_html/` on the host:

```apache
# ===========================================================================
#  Apache (Hostinger / cPanel) settings for the website in public_html/.
#  The booking engine in public_html/book/ has its own .htaccess.
# ===========================================================================

Options -Indexes

<IfModule mod_rewrite.c>
  RewriteEngine On

  # Always without "www", always HTTPS.
  RewriteCond %{HTTP_HOST} ^www\.(.+)$ [NC]
  RewriteRule ^ https://%1%{REQUEST_URI} [L,R=301]
  RewriteCond %{HTTPS} off
  RewriteCond %{HTTP:X-Forwarded-Proto} !https
  RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

  # Short address for staff: /admin → the admin panel's own subdomain.
  RewriteRule ^admin/?$ https://admin.kutchsafaribhuj.in/ [L,R=302]

  # The site is a single-page app: any address that isn't a real file or the
  # booking engine loads index.html, and the app shows the right page.
  RewriteCond %{REQUEST_URI} !^/book(/|$)
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule ^ index.html [L]
</IfModule>

<IfModule mod_headers.c>
  Header always set X-Content-Type-Options "nosniff"
  Header always set Referrer-Policy "strict-origin-when-cross-origin"
  # index.html must never be cached, so a new upload shows straight away.
  <FilesMatch "^index\.html$">
    Header set Cache-Control "no-cache"
  </FilesMatch>
  # Built JS/CSS have a content hash in their names, so they can be cached for a year.
  <FilesMatch "^index-[\w-]+\.(js|css)$">
    Header set Cache-Control "public, max-age=31536000, immutable"
  </FilesMatch>
</IfModule>
```

### 3.3 Booking-engine `.htaccess`: the subdomain rules

In `backend/booking-engine/.htaccess`, inside `<IfModule mod_rewrite.c>`, **after** the HTTPS rule (`RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]`) and before `</IfModule>`, add:

```apache
  # Live on Hostinger this folder is public_html/book, and two subdomains point at
  # it: book.kutchsafaribhuj.in (guests) and admin.kutchsafaribhuj.in (staff).
  # Same code, same database. These rules keep each part at its own address.

  # admin.kutchsafaribhuj.in opens straight on the panel.
  RewriteCond %{HTTP_HOST} ^admin\.kutchsafaribhuj\.in$ [NC]
  RewriteCond %{REQUEST_URI} ^/?$
  RewriteRule ^ /admin/ [L,R=302]

  # The panel on the guest address forwards to the admin address…
  RewriteCond %{HTTP_HOST} ^book\.kutchsafaribhuj\.in$ [NC]
  RewriteCond %{REQUEST_URI} ^/admin(/.*)?$
  RewriteRule ^ https://admin.kutchsafaribhuj.in/admin%1 [L,R=301]

  # …and the folder's address on the main site, kutchsafaribhuj.in/book/…,
  # forwards to the subdomains (admin pages first).
  RewriteCond %{HTTP_HOST} ^(www\.)?kutchsafaribhuj\.in$ [NC]
  RewriteCond %{REQUEST_URI} ^/book/admin(/.*)?$
  RewriteRule ^ https://admin.kutchsafaribhuj.in/admin%1 [L,R=301]
  RewriteCond %{HTTP_HOST} ^(www\.)?kutchsafaribhuj\.in$ [NC]
  RewriteCond %{REQUEST_URI} ^/book(/.*)?$
  RewriteRule ^ https://book.kutchsafaribhuj.in%1 [L,R=301]
```

These rules only act on the `kutchsafaribhuj.in` addresses, so they are harmless on this PC, but they are not needed here.

Why the admin isn't simply copied to its own folder: its pages load the engine's shared files with `../` (`../assets/`, `../document.php`, `../favicon.ico`) and use the same database, so it must stay inside the engine folder. The subdomain points at that folder instead.

### 3.4 The live engine settings: `config.local.php` (on the server only)

Create a **new** `config.local.php` for the server. **Do not upload the one on this PC** (it uses SQLite and local test settings). Fill in every `<<...>>`, then upload it into `public_html/book/`, next to `config.php`. Never put it on GitHub or email it.

```php
<?php
/* LIVE settings for book.kutchsafaribhuj.in + admin.kutchsafaribhuj.in (Hostinger).
 * Anything not listed here comes from config.php. */
return [

  // hPanel → Databases → Management. Hostinger prefixes the names, e.g. u123456789_booking.
  'db' => [
    'driver' => 'mysql',
    'host'   => 'localhost',
    'name'   => '<<database name>>',
    'user'   => '<<database user>>',
    'pass'   => '<<database password>>',
  ],

  // Where the engine lives. No slash at the end.
  'base_url' => 'https://book.kutchsafaribhuj.in',

  // Razorpay → Settings → API Keys. Start with the TEST keys (rzp_test_…);
  // switch to the live keys only after a test booking has gone end to end.
  'razorpay' => [
    'enabled'        => true,
    'key_id'         => '<<rzp_test_...>>',
    'key_secret'     => '<<key secret>>',
    'webhook_secret' => '<<webhook secret>>',
  ],

  // Direct UPI QR (paid straight to the bank; staff mark it received in admin).
  // Leave vpa empty to hide this option.
  'upi' => [
    'vpa' => '',
  ],

  // OFF on the live site: no free "I've paid (test)" button, guest details required,
  // and error details hidden from visitors.
  'test_payments' => ['enabled' => false],
  'rules'         => ['guest_details_optional' => false],
  'debug'         => false,

  // Confirmation emails. PHP mail() works on Hostinger; for mail that doesn't land
  // in spam, create reservations@kutchsafaribhuj.in in hPanel → Emails and fill in SMTP.
  'mail' => [
    'from_email' => 'reservations@kutchsafaribhuj.in',
    'smtp' => [
      'enabled' => false,
      'host'    => 'smtp.hostinger.com',
      'port'    => 587,
      'user'    => 'reservations@kutchsafaribhuj.in',
      'pass'    => '<<mailbox password>>',
      'secure'  => 'tls',
    ],
  ],
];
```

`base_url` is what confirmation emails, receipts and Razorpay use to link back to the engine.

### 3.5 The "Back to website" link (in the live database)

After setup (5.5), set the resort's website address. In the seed it is `http://localhost:3000` for local testing:

```sql
UPDATE properties SET website_url = 'https://kutchsafaribhuj.in' WHERE code = 'kutch-safari-resort';
```

Nothing else needs changing: `allowed_origins` in `config.php` already lists `https://kutchsafaribhuj.in` and `https://www.kutchsafaribhuj.in`.

---

## 4. Build the upload folder

Run in PowerShell from the project folder. It builds outside the project, so nothing new ends up in git, and it doesn't touch `dist/`.

```powershell
$out = "$env:USERPROFILE\Downloads\kutch-safari-upload\public_html"
$env:VITE_BOOKING_ENABLED = "true"
$env:VITE_BOOKING_URL = "https://book.kutchsafaribhuj.in/"
pnpm exec vite build --outDir $out --emptyOutDir
Remove-Item Env:VITE_BOOKING_ENABLED, Env:VITE_BOOKING_URL
robocopy backend\booking-engine "$out\book" /E /XF config.local.php *.sqlite *.sqlite-shm *.sqlite-wal *.log
Remove-Item "$out\vercel.json", "$out\.gitkeep" -ErrorAction SilentlyContinue
Set-Location $out; tar -a -c -f ..\public_html.zip *; Set-Location -
```

`robocopy` reports exit code 1 when it copied files; that is success. The last line makes `Downloads\kutch-safari-upload\public_html.zip`. (Tested 5 Oct 2026 in a scratch folder: the build carried the live link, `book/data/` held only `.htaccess`, no `config.local.php`, and the zip kept every `.htaccess` with proper `book/…` paths.)

What this gives (about 270 MB, mostly photos and videos):

```
public_html/
├── index.html, assets/, favicon.ico, robots.txt, sitemap.xml, .htaccess (3.2)
└── book/                       the booking engine + admin panel
    ├── .htaccess               (with the 3.3 rules)
    ├── admin/, api/, assets/, lib/, bin/ (+ .htaccess), data/ (only .htaccess)
    ├── index.php, manage.php, config.php, schema.sql, seed.sql, …
    └── (no config.local.php, no booking.sqlite: never upload those from this PC)
```

**Check before uploading:** there are **four** `.htaccess` files (top level, `book/`, `book/bin/`, `book/data/`). The top-level one is there only if 3.2 was done before building. They protect the database, settings and scripts. Turn on "show hidden files" in Explorer to see them.

**Check the links went live:** open `public_html\assets\index-….js` in a text editor and search for `book.kutchsafaribhuj.in`. If it isn't there, the build missed the settings; build again. Then open `index.html` in the browser preview of the live site after upload: the menu button must say **Book Now**, not Enquire Now.

**Make the zip with `tar` as above, not with `Compress-Archive` or "Send to → Compressed folder".** Windows PowerShell's `Compress-Archive` writes folder paths with backslashes (`book\.htaccess`), which a Linux server can unpack as oddly named files instead of folders (seen in the 5 Oct 2026 test). Uploading the folder's contents over FTP (FileZilla) avoids the zip and its size limits altogether.

---

## 5. Set it up on Hostinger

In hPanel:

1. **Websites → Add website** → `kutchsafaribhuj.in` → empty website (skip the builder).
2. **Files → File Manager → `public_html`**: delete Hostinger's `default.php`, upload the zip and **Extract** it there (or upload the folder's contents over FTP: **Files → FTP Accounts** gives the FileZilla login).
3. **Domains → Subdomains**: create `book` with the custom folder `public_html/book`, then `admin` with the **same** custom folder `public_html/book`.
4. **Databases → Management**: create a MySQL database and user. Fill those into the live `config.local.php` (3.4) and upload it into `public_html/book/`.
5. **Advanced → SSH Access**: turn it on and copy the login command hPanel shows into PowerShell. Then:
   ```bash
   cd domains/kutchsafaribhuj.in/public_html/book
   php bin/setup.php                                                   # ONCE, on the empty database only
   php bin/setup.php --admin "you@example.com" "Your Name" "a-long-password"
   php bin/check-system.php
   ```
   The first command creates the tables and loads the starting rooms, prices, special prices and extras from `seed.sql` (the same ones as on this PC: as of 5 Oct 2026 no prices had been changed in the local admin). **Never run it again** (section 9). The admin login must be new: the local test login must not go live.
6. **Databases → phpMyAdmin**: run the SQL in 3.5.
7. DNS: section 6.
8. **Security → SSL**: install the free certificate for `kutchsafaribhuj.in`, `book.kutchsafaribhuj.in` and `admin.kutchsafaribhuj.in` (once DNS points at Hostinger).

---

## 6. Point the domain at Hostinger (DNS)

DNS is the domain's address book. Changing it is what replaces the old website. On 5 Oct 2026 the domain's nameservers were **GoDaddy's** (`ns23` / `ns24.domaincontrol.com`), and `kutchsafaribhuj.in` pointed at the old site (`34.8.20.4`, a hotel-website service on Google Cloud). There were no email (MX) or TXT records, so nothing else breaks. Pick one:

* **A. Hostinger's nameservers (simplest).** Where the domain is registered, replace the two `domaincontrol.com` nameservers with the two hPanel shows. Hostinger then answers for `kutchsafaribhuj.in`, `www`, `book` and `admin` by itself.
* **B. Keep DNS at GoDaddy.** In GoDaddy DNS: change the `@` A record from `34.8.20.4` to the Hostinger server IP (hPanel → Websites → Dashboard); keep `www` as a CNAME to `@`; add A records `book` and `admin` with the same IP.

It usually takes effect within an hour (sometimes up to 24–48 hours). During that time some visitors still see the old site. Cancel the old site's subscription only after the new one has worked for a few days.

---

## 7. After it's live

1. **Check:**
   * `https://kutchsafaribhuj.in` and a page like `/stay`, opened directly.
   * Book Now opens `https://book.kutchsafaribhuj.in`.
   * A full test booking with a Razorpay **test** card.
   * `https://admin.kutchsafaribhuj.in` signs in, and the test booking shows there.
   * `kutchsafaribhuj.in/admin` and `kutchsafaribhuj.in/book/` forward to the subdomains.
   * `https://book.kutchsafaribhuj.in/config.php` and `/data/` are refused (the `.htaccess` protection).

   The `.htaccess` rules were never run on this PC (no Apache here), so these checks are their first real test.
2. **Razorpay webhook:** Dashboard → Settings → Webhooks → `https://book.kutchsafaribhuj.in/api/webhook-razorpay.php`, events payment.captured, payment.failed, refund.processed. Its secret goes into `webhook_secret`.
3. **Live payments:** after the test booking works end to end, put the live Razorpay keys (`rzp_live_…`) into `config.local.php`.
4. **Emails:** create `reservations@kutchsafaribhuj.in` (hPanel → Emails) and fill in the SMTP part of `config.local.php`, so confirmations don't land in spam. With option B DNS, Hostinger shows the MX/SPF records to add at GoDaddy.
5. **Cron (only once Stayflexi is connected):** `bin/sync-inventory.php` every 10 minutes and `bin/retry-failed-sync.php` hourly (hPanel → Advanced → Cron Jobs).
6. **Search engines:** if the domain is in Google Search Console, submit `https://kutchsafaribhuj.in/sitemap.xml`. The old site had Google Analytics; the new one doesn't yet, so add its tracking ID if visitor statistics should continue.
7. Still to be confirmed by the owner before real guests book: rates, GST and cancellation terms (docs/08, open questions).

---

## 8. Updating the live site later

* **Website change:** build again as in section 4 (with both settings), then upload everything in `public_html` **except the `book` folder**.
* **Booking-engine change:** upload the changed engine files into `public_html/book/`. Never upload `config.local.php` or anything in `data/` from this PC. New database columns need adding by hand (docs/07 §4: there are no migration scripts).
* **On this PC nothing changes:** `pnpm dev` + the engine on `127.0.0.1:8080`, links on `/book/`.

---

## 9. Never do this on the live site

* **Never run `php bin/setup.php` (without `--admin`) again.** It reloads `seed.sql`, which first empties rooms, prices, special prices and extras (docs/07 §4). `--admin` on its own is safe.
* Never upload this PC's `config.local.php` or `data/booking.sqlite` (demo bookings and local settings).
* Never leave `test_payments` on, `guest_details_optional` on or `debug` on.
* Never commit or share the live `config.local.php` (database password, Razorpay keys).
