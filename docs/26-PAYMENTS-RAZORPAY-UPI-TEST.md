# Payments: Razorpay, UPI QR, Test Payments, Refunds

_Written 26 Sep 2026. Code: `lib/payment.php`, `api/payment-*.php`, `api/webhook-razorpay.php`, admin booking page._

## 1. What the guest can pay
* **50% now** or **in full** (`config.php → payment_modes`). "Pay at the property" is off (`hotel.enabled = false`), so every booking has money behind it.
* The amount asked for at checkout is `bookings.amount_due_now`, fixed when the booking is made. A later change by the desk does **not** change it. The desk collects any difference itself (doc 22).

## 2. Razorpay (cards, UPI apps, netbanking)
1. `api/payment-create.php` → `razorpay_create_order()` → Razorpay order for `amount_due_now` (in paise). The booking ref goes in `receipt`/`notes`. **Since 29 Sep 2026 the request must include the booking's private `manage_token`**, and only unpaid (pending) bookings can start a payment — before, anyone could start one with just a booking number and leave a stranger's booking "waiting for UPI", holding its rooms.
2. The guest pays in Razorpay Checkout (script from Razorpay's CDN, loaded only when enabled).
3. `api/payment-verify.php` → `razorpay_confirm()` checks the HMAC signature → `settle_payment()`.
4. `api/webhook-razorpay.php` (`payment.captured`, `payment.failed`, `refund.processed`) is the backup if the browser closes. `settle_payment()` is **idempotent**: the browser and the webhook often both arrive, and the second is ignored.
5. `settle_payment()`: marks the payment paid, recounts `amount_paid` = payments − refunds (`refresh_amount_paid()`), sets the booking `confirmed`, pushes it to Stayflexi (doc 27), and sends the confirmation email.

**Keys** live only in `backend/booking-engine/config.local.php` (git-ignored). This PC uses the **test** keys. Test and live key pairs were checked against Razorpay on 26 Sep 2026. The live Key Secret was shared over WhatsApp and chat, so **regenerate it before launch**. Any copy handed to someone else must leave the keys out (doc 30 §4).

**Windows HTTPS fix:** PHP on Windows had no certificate bundle, so every call to Razorpay failed with "HTTP 0". `curl_trust_system_certs()` (`lib/db.php`) tells cURL to use the Windows certificate store (`CURLSSLOPT_NATIVE_CA`). It is used by Razorpay and Stayflexi calls. Linux hosts don't need it.

## 3. UPI QR (straight to the bank account)
* Needs `upi.vpa` (the account's UPI id; not set yet). The QR is drawn in the browser (qrious) with the exact amount and the booking ref.
* The payment is `awaiting_confirmation` until staff see the money in the bank and click **Money received** in the admin (`upi_mark_received()`). That is the **only** way a UPI QR payment becomes paid.
* While waiting, the booking keeps its rooms. With nothing paid and nothing waiting, it lapses after 45 minutes (doc 23).

## 4. Test payments (turn off before launch)
`test_payments.enabled = true` shows **I've paid (test)**. `api/payment-test.php` → `test_payment_settle()` needs the booking's own `manage_token`, records a `test` payment of `amount_due_now`, and goes through the same `settle_payment()` path as a real payment, so everything downstream behaves as it will for real. Receipts say "Test payment — no money taken".

## 5. Money taken or given back by the desk
* **Collect the balance** → `record_offline_payment()` (cash, card, bank transfer, UPI — only these). A **pending** booking is confirmed once what it needs now is paid (all of it, or the 50%), with Stayflexi push and confirmation email, the same as an online payment (fixed 29 Sep 2026). The amount is capped at the balance. It is refused on cancelled or fully paid bookings.
* **Give back** (after a change made the stay cheaper) → `record_offline_refund()`. It is stored as a negative `refund` row.
* **Cancel** → the ladder decides the charge. Card payments are refunded through Razorpay automatically (`razorpay_refund()`) once it is connected; otherwise the refund is recorded.
* `bookings.amount_paid` is always recounted by `refresh_amount_paid()` = paid payments − refunds. Online settlement and UPI confirmation now use it too. **Fixed 26 Sep 2026:** before, they counted payments only and ignored earlier refunds.

## 6. What "due" means
See doc 22 §3 (`booking_money()`): due now, due before arrival (50% plan), or refund due.
