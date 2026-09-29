# Auth and Security
> Purpose: who can do what, and how it's protected | Mode: A | Confidence: High | Related: [../docs/24-ADMIN-PANEL-GUIDE.md](../docs/24-ADMIN-PANEL-GUIDE.md), [env_and_setup.md](env_and_setup.md)

## Roles
* **Guest** — no account. Access to one booking by *ref + mobile/email* or *ref + private `manage_token`* (40 hex chars, compared with `hash_equals`) [CODE `find_booking`, `find_booking_by_token`].
* **Admin (`owner`)** — `admin_users` rows; one active user `manvir` [CODE]. No other roles [CODE].

## Admin sign-in [CODE `admin/login.php`, `admin/_auth.php`]
* The page sends **SHA-256 of the username (trimmed, lower-case) and SHA-256 of the password** — never the typed text (inputs have no `name`). Built-in `crypto.subtle`, or a checked plain-JS fallback on plain http.
* Server finds the user by `hash('sha256', lower(email))`, then `password_verify(sha256, password_hash)`; stored hash = **bcrypt(sha256(password))**. `bin/setup.php --admin` writes it this way.
* Plain-text posts are refused ("Please sign in using this page").
* Not encryption: HTTPS protects the connection on the live site [INFERRED; documented to the owner].
* 6 failed attempts per 15 min per IP → wait [CODE `admin.login_attempts`].
* Session cookie only (ends with the browser), `HttpOnly`, `SameSite=Lax`, `Secure` on HTTPS; per-tab `sessionStorage` mark; 10-minute idle sign-out [CODE].

## Request protection
* CSRF token on every admin POST (403 "Your session expired…" without it) [CODE `check_csrf`].
* Parameterised SQL everywhere (PDO prepared statements) [CODE].
* Output escaped (`h()` in PHP, `esc()` in admin JS); a `<script>` guest name shows as text on every admin page [CODE, tested 29 Sep].
* CSV export neutralises formula cells (= + − @) [CODE `admin/export.php`].
* Input limits and format checks (see [backend_spec.md](backend_spec.md)).
* Rate limits per IP on every public API [CODE `rate_limit`].
* Payment start needs the booking's private code; Razorpay HMAC for verify and webhook [CODE].
* `.htaccess` blocks `config*.php`, `*.sql`, `*.sqlite`, `data/`, `bin/` (Apache only) [DOC `docs/02`]; `bin/` scripts refuse to run from the web [CODE].

## Secrets
* Only in `backend/booking-engine/config.local.php` (git-ignored): Razorpay test + live keys [CODE]. Never commit; leave out of copies.
* **Live Razorpay secret was shared in chat → regenerate before launch** [DOC `docs/26`].
* Local admin password is the test value `1234` → set a long one on the live server [DOC `docs/30`].

## Known risks / hardening still needed
* Sample mode (`guest_details_optional`) and test payments are **on** — turn off before launch [CODE].
* `debug => true` locally shows error detail — off in production [CODE].
* Stayflexi off → overselling risk if the same rooms are on OTAs [DOC `docs/27`].
* No 2FA for admin [INFERRED]; single shared owner account [CODE].
