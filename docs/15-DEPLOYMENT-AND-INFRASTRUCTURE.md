# Deployment and Infrastructure

## 1. Overview
| Piece | Runtime | Where it can be hosted |
|---|---|---|
| Marketing site (`dist/public`) | Static files | Vercel (current), Netlify, cPanel, anything |
| Express server (`dist/index.js`) | Node 18+ | Any Node host. Optional; only needed for `/api/contact` |
| **Booking engine (`booking-engine/`)** | **PHP 8 + MySQL** | **PHP host with MySQL (cPanel). Not Vercel.** |

Production domain: `kutchsafaribhuj.in`. Vercel project: `kutch-safari-resort` (`.vercel/project.json`).

---

## 2. Marketing site

### 2.1 Build
```bash
pnpm build     # vite build → dist/public ; esbuild server/index.ts → dist/index.js
```
Set `VITE_BOOKING_URL` at build time if the engine is **not** served from `/book/` on the same domain, e.g. `VITE_BOOKING_URL=https://book.kutchsafaribhuj.in/`. The value must end with `/`.

### 2.2 Vercel
Deployed with the CLI from `dist/public` (Windows: use `cmd.exe /c` to get round PowerShell's script policy):
```powershell
cmd.exe /c "vercel --prod --yes"
```
`client/public/vercel.json` rewrites every path to `/index.html`. `.vercelignore` excludes `dist/`, `node_modules/`, `.vercel/` and `booking-engine/`.

On Vercel, `/book/` is answered by the SPA, so `BookingRedirect` shows "call/WhatsApp us". **To make booking work while the site stays on Vercel**, pick one:
* Build with `VITE_BOOKING_URL` pointing at the engine on the PHP host, **or**
* Add a Vercel rewrite that proxies `/book/(.*)` to the PHP host, placed before the SPA catch-all:
  ```json
  { "rewrites": [
      { "source": "/book/:path*", "destination": "https://PHP-HOST/book/:path*" },
      { "source": "/(.*)", "destination": "/index.html" } ] }
  ```

### 2.3 Everything on cPanel (simplest)
Upload `dist/public/*` to `public_html/` and `booking-engine/*` to `public_html/book/`. The default `/book/` link then works with no environment variable. SPA routing needs an `.htaccess` fallback to `index.html` in `public_html`, with `book/` excluded.

---

## 3. Booking engine
Full steps are in `booking-engine/README.md`. In short:
1. Upload `booking-engine/` → `public_html/book` (include the hidden `.htaccess` files).
2. cPanel → MySQL: create the database and user, then import `schema.sql` and `seed.sql` (or run `php bin/setup.php`).
3. Create `config.local.php` next to `config.php` with DB, `base_url`, Razorpay, UPI, `debug => false` and `'rules' => ['guest_details_optional' => false]` (sample mode off) and `'test_payments' => ['enabled' => false]` (no free "I've paid (test)" button). **Replace the local SQLite `config.local.php` from the repo. Don't upload it.**
4. `php bin/setup.php --admin USERNAME "Name" "password"` (a long password; the local test login `manvir` / `1234` must not go live).
   **Never run full `php bin/setup.php` again on the live database.** It reloads `seed.sql`, which empties the rooms, prices, special prices and extras first (doc 28 §4).
5. Razorpay: test keys first, then a webhook to `<base_url>/api/webhook-razorpay.php` (payment.captured, payment.failed, refund.processed).
6. Cron (once Stayflexi is connected): `bin/sync-inventory.php` every 10 min, `bin/retry-failed-sync.php` hourly.
7. Make sure the site's domain is in `allowed_origins` (`config.php`). The kutchsafaribhuj.in domains and the Vercel preview are already listed.
8. Health check: `php bin/check-system.php`, `bin/check-razorpay.php`, `bin/check-stayflexi.php`.

**Seed data to adjust for this domain:** `properties.website_url` is `http://localhost:3000` (KSR, set for local testing) and `https://www.whiterann.com` (WRC). **`config.php` defaults to SQLite**; the live `config.local.php` must set `'driver' => 'mysql'`. This is the engine's "Back to website" link. Change it to `https://kutchsafaribhuj.in` if that's the live site. Mail settings in `config.php` also assume `@kutchsafariresort.com`.

---

## 4. Local development
```bash
pnpm install
pnpm setup:book   # php booking-engine/bin/setup.php → booking-engine/data/booking.sqlite
pnpm dev:book     # php -S 127.0.0.1:8080 -t booking-engine
pnpm dev          # vite on :3000, proxies /book/ → :8080
```
* PHP 8.3 is installed on this PC with winget. `php.ini` enables `pdo_sqlite`, `sqlite3`, `curl`, `mbstring`, `openssl` and `fileinfo`, with `date.timezone = Asia/Kolkata`.
* The engine must listen on **127.0.0.1:8080** and the Vite proxy must point at `http://127.0.0.1:8080`. With `localhost`, Node picked IPv6 and the rooms never loaded.
* On Windows, PHP uses the Windows certificate store for HTTPS (`curl_trust_system_certs()`); without it Razorpay calls fail with HTTP 0.
* `booking-engine/config.local.php` (git-ignored) switches to SQLite and sets `base_url` to `http://localhost:3000/book`.
* Admin locally: `http://localhost:3000/book/admin` (the address fills itself in to `…/admin/login.php`).
* Tests: `php booking-engine/bin/test-changes.php` (works on a temporary copy of the database, doc 30).

## 5. Express server (`server/index.ts`)
Serves `dist/public`, `POST /api/contact` (appends to `/data/enquiries.json`), and a `*` fallback to `index.html`. `pnpm start` runs it with `NODE_ENV=production`. It does not proxy `/book/`. If you host the site with this server, add a proxy or serve the engine from a PHP host.

## 6. Secrets and git
* Ignored: `.env*`, `/data/`, `booking-engine/config.local.php`, `booking-engine/data/*` (except `.htaccess`), `*.sqlite`, `*.log`.
* Never commit Razorpay keys, the webhook secret, the UPI VPA or DB passwords. They go only in the server's `config.local.php`.
* On this PC, `config.local.php` holds the Razorpay test and live key pairs (test pair in use). Regenerate the live secret before launch; it was shared in chat.
* **Don't push to GitHub** unless the owner asks. The one push was on 25 Sep 2026; everything since is uncommitted.
