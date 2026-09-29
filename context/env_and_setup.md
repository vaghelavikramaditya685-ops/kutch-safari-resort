# Environment and Setup
> Purpose: get it running from zero | Mode: A | Confidence: High | Related: [../docs/15-DEPLOYMENT-AND-INFRASTRUCTURE.md](../docs/15-DEPLOYMENT-AND-INFRASTRUCTURE.md), [testing_and_deployment.md](testing_and_deployment.md)

## Prerequisites
* Node 18+ and pnpm (or npm) [CODE `package.json`].
* PHP 8.x with `pdo_sqlite`, `sqlite3`, `curl`, `mbstring`, `openssl`, `fileinfo`; `date.timezone = Asia/Kolkata`. On this PC: PHP 8.3 via winget [DOC `docs/15`].

## Install and run (local)
```bash
pnpm install
pnpm setup:book      # once: php backend/booking-engine/bin/setup.php → creates data/booking.sqlite
pnpm dev:book        # php -S 127.0.0.1:8080 -t backend/booking-engine
pnpm dev             # vite on :3000; /book/ → 127.0.0.1:8080
```
Open http://localhost:3000 (site), /book/ (booking), /book/manage.php (status), /book/admin (admin) [CODE].
Create/reset an admin: `php backend/booking-engine/bin/setup.php --admin USERNAME "Name" "password"` [CODE].

## Settings (`backend/booking-engine/config.php`, overridden by `config.local.php`)
| Setting | Purpose | Example (non-secret) |
|---|---|---|
| `db.driver` | `sqlite` (default) or `mysql` | live: `mysql` in config.local.php |
| `db.host/name/user/pass` | MySQL connection | `localhost`, `kutch_booking`, … |
| `db.sqlite_path` | SQLite file | `data/booking.sqlite` |
| `base_url` | engine's own URL | `http://localhost:3000/book` |
| `razorpay.enabled/key_id/key_secret/webhook_secret` | payments | `rzp_test_…` (secret only in config.local.php) |
| `upi.enabled/vpa/payee_name/hold_minutes` | UPI QR | vpa empty (not set) |
| `test_payments.enabled` | "I've paid (test)" button | `true` now; `false` for launch |
| `rules.*` | max_nights 21, max_rooms_online 5, max_days_ahead 730, max_extra_quantity 50, hold_minutes 20, guest_details_optional (true now) | |
| `payment_modes`, `cancellation`, `tax_slabs`, `prices_include_tax` | money rules | see [backend_spec.md](backend_spec.md) |
| `stayflexi.*` | channel manager | `enabled: false` |
| `mail.*` | from/bcc, SMTP | SMTP off |
| `admin.idle_minutes/list_clear_days/login_attempts` | 10 / 15 / 6 | |
| `allowed_origins`, `debug`, `timezone` | CORS, error detail, IST | |
Website: `VITE_BOOKING_URL` (build time; default `/book/`) [CODE `lib/booking.ts`].

## Common setup errors
| Symptom | Cause | Fix |
|---|---|---|
| Rooms never load (spinner) | Vite proxy hit IPv6 `localhost` | engine on `127.0.0.1:8080` (as configured) [DOC `docs/29`] |
| Razorpay "HTTP 0" on Windows | no CA bundle | `curl_trust_system_certs()` (already in code) |
| `/book/admin` broken layout | missing trailing slash | redirect in `_auth.php` (fixed) |
| Port 8080 in use | a previous PHP server | stop it, or reuse |
| `NODE_ENV=production` fails in cmd | Unix-style env in `pnpm start` | use Git Bash / Linux host |
