# Testing and Deployment
> Purpose: how to prove it works and how to ship it | Mode: A | Confidence: High | Related: [../docs/30-TESTING-GO-LIVE-AND-HANDOVER.md](../docs/30-TESTING-GO-LIVE-AND-HANDOVER.md), [../heal/REPORT.md](../heal/REPORT.md), [../chaos/REPORT.md](../chaos/REPORT.md)

## Rule
Never test on the real database (`backend/booking-engine/data/booking.sqlite`); use a temporary copy [DOC owner rule].

## Existing tests
| What | Command | Covers |
|---|---|---|
| Change pricing & money | `php backend/booking-engine/bin/test-changes.php` | 17 checks on a temp copy (add/remove, dates, occupancy, special prices, coupons, 50%/full, payments − refunds) [CODE] |
| System readiness | `php backend/booking-engine/bin/check-system.php` | search, quote, create/lookup/cancel (temp copy), overbooking, payments/Stayflexi config [CODE] |
| TypeScript | `pnpm check` | website types [CODE] |
| Build | `pnpm build` | Vite + esbuild bundle [CODE] |
| PHP lint | `php -l` on each file | syntax [CODE practice] |
Flow scripts used on 29 Sep (59 guest/admin actions over HTTP) and the chaos scripts live in `chaos/EVIDENCE/` [CODE]; they target a throwaway engine copy on port 8090.

## Coverage gaps
No automated browser tests in the repo (browser checks were done by hand/agent on 29 Sep); no CI [CODE: no CI config]; no unit tests for the website [CODE].

## Environments
* **Local:** this PC, SQLite, test keys [CODE].
* **Website live:** Vercel `kutchsafaribhuj.in` [DOC].
* **Engine live:** [PLANNED] a PHP + MySQL host at `/book/`.

## Deployment (short; full list in docs/30)
1. Host: upload `backend/booking-engine/` → `public_html/book` (with `.htaccess`).
2. MySQL: create DB, `php bin/setup.php` **once**, then `--admin` with a long password.
3. `config.local.php` on the server: `db.driver = mysql`, live Razorpay (regenerated secret) + webhook, `debug=false`, `guest_details_optional=false`, `test_payments.enabled=false`, `base_url`, `properties.website_url` → live site.
4. Website: build with the right `VITE_BOOKING_URL` or proxy `/book/` to the PHP host.
5. Cron when Stayflexi is on: `sync-inventory.php` (10 min), `retry-failed-sync.php` (hourly).
Monitoring: `audit_log` (`mail_failed`, `stayflexi_*`, `login_failed`) [CODE]. Rollback: git snapshot branches (`restructure/…`, `heal/…`) and a DB backup before changes [CODE].
