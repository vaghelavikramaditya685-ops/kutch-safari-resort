# Instructions for AI Agents and Developers
> Purpose: how to change this project safely | Mode: A | Confidence: High | Related: [folder_structure.md](folder_structure.md), [testing_and_deployment.md](testing_and_deployment.md), [../docs/29-LESSONS-LEARNED-AND-GOTCHAS.md](../docs/29-LESSONS-LEARNED-AND-GOTCHAS.md)

## Hard rules (owner's)
1. **Do not push to GitHub** unless the owner asks in that conversation. Committing locally is fine when asked. [DOC]
2. **No invented data in the real database** (`backend/booking-engine/data/booking.sqlite`). Test on a throwaway copy; delete it after. Demo data only when the owner asks, marked "Demo …" / @example.com. [DOC]
3. **Never run full `bin/setup.php` on a database with real data** — it empties rooms, prices, special prices and extras. `--admin` alone is safe. [CODE seed.sql]
4. **Secrets** only in `config.local.php`; never commit, print or copy them. [CODE .gitignore]
5. **Keep the colours**; the owner approves visual changes. [DOC]
6. Don't type the owner's real passwords anywhere; the local test login is for local testing only. [DOC]

## Conventions
* PHP: plain functions in `lib/`, `snake_case`, PDO helpers `q/q1/qval/insert/update`, `money()` for rounding, `cfg('a.b')` for settings, `audit()` for money/inventory actions. [CODE]
* Validate every input on the server with the helpers in `lib/db.php` (`LIMITS`, `too_long`, `valid_phone`, `valid_date`, `valid_arrival_time`); messages are plain sentences telling the user what to do. [CODE]
* Admin pages: `require_login()`, `check_csrf()`, `admin_head()` / `admin_foot()`; escape output with `h()`; destructive actions use `data-confirm` (never `confirm()`). [CODE]
* Website: engine links only via `lib/booking.ts` and plain `<a>`; new routes also get a title in `App.tsx` and a sitemap entry. [CODE]
* Comments explain *why*, match the surrounding density. [CODE style]

## Unsafe areas (read the doc first)
| Area | Why | Read |
|---|---|---|
| `price_rooms`, `tax_split` | every price flows through them | docs/21 |
| `modification_delta`, `booking_money` | owner-defined money rules | docs/22 |
| `rooms_booked` | overselling | docs/23 |
| `booking_rooms.rate_plan_name` format | occupancy is parsed from it everywhere | docs/28 §2 |
| `admin/login.php`, `_auth.php` | SHA-256 sign-in contract with `setup.php` | auth_and_security.md |
| `lib/channel.php` | Stayflexi, unverified endpoints | docs/27 |

## How to verify a change
1. `php -l` on changed PHP; `pnpm check`; `pnpm build`.
2. `php backend/booking-engine/bin/test-changes.php` (17/17) and `bin/check-system.php`.
3. For flows: run a copy of the engine on another port with a copy of the DB (see `chaos/EVIDENCE/`), never the real one.
4. Open the pages in a browser; check the console and that nothing scrolls sideways at 375 px.

## Which file for which task
Pricing → docs/21 + backend_spec · Changes/money → docs/22 · Availability → docs/23 · Admin → docs/24 + auth_and_security · Guest page → docs/25 + frontend_spec · Payments → docs/26 · Stayflexi → docs/27 · Database → data_models + docs/28 · Deploy → testing_and_deployment + docs/30.

## Glossary
* **Pending / confirmed / lapsed** — unpaid, paid, unpaid past the 45-minute window.
* **Due now / before arrival** — what must be paid now (to 100% or 50%) / the rest on the 50% plan.
* **Special price** — a nightly price override for dates (`rates`).
* **Sample mode** — guest details optional (`rules.guest_details_optional`).
* **manage_token** — a booking's private code in status/receipt links.
* **Demo booking** — owner-requested fake booking, named "Demo …".
