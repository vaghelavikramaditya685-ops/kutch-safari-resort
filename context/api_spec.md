# API Spec
> Purpose: every endpoint the booking engine exposes | Mode: A | Confidence: High | Related: [backend_spec.md](backend_spec.md), [frontend_spec.md](frontend_spec.md)

Base: `/book/api/` (dev: proxied to `127.0.0.1:8080/api/`). JSON in/out; CORS allow-list in `config.php allowed_origins`; per-IP rate limits (HTTP 429 "Too many attempts") [CODE `api/_init.php`]. A body that is not valid JSON → 400 "We could not read that request…" [CODE].

| Method & path | Auth | Request | Response | Errors | Rate limit |
|---|---|---|---|---|---|
| GET `availability.php` | none | `property, check_in, check_out, rooms, occupancy` (e.g. `2,3`) | `{ok, results:[{id,name,rate_plans:[{id,total,units…}],rooms_left,fits…}], addons:[…], next_available}` | bad/past/too-far dates, > 21 nights, unknown property | 120/min |
| POST `quote.php` | none | cart: `{property, check_in, check_out, occupancy:[], payment_mode, rooms:[{room_type_id,rate_plan_id,rooms}], addons:[{addon_id,quantity,rooms?}], coupon?}` | `{ok,total,tax_amount,discount,amount_due_now,balance_later,rooms,addons,cancellation,payment_modes}` | room not offered, extra not offered / quantity 1–50, gala < 10, > 5 rooms, not enough free | 90/min |
| POST `book.php` | none | cart + `guest:{name,phone,email,city,arrival_time,special_requests}` | `{ok,booking_id,ref,manage_token,payment_mode,amount_due_now,total,status:"pending",next:"payment",methods}` | as quote, plus guest checks (`field:"guest"`) | 12 / 5 min |
| POST `payment-create.php` | **booking's manage_token** | `{booking_id, manage_token, method:"razorpay"\|"upi_qr"}` | Razorpay order or UPI `{ok,vpa,amount,upi_uri…}` | not found / already paid / cancelled | 30 / 5 min |
| POST `payment-verify.php` | Razorpay signature | `{razorpay_order_id, razorpay_payment_id, razorpay_signature}` | `{ok, booking…}` | signature mismatch | 30 / 5 min |
| POST `payment-test.php` | manage_token; only if `test_payments.enabled` | `{booking_id, manage_token}` | `{ok}` → booking confirmed | off / wrong token | 30 / 5 min |
| POST `webhook-razorpay.php` | HMAC `X-Razorpay-Signature` | Razorpay event (`payment.captured`, `payment.failed`) | 200 | 400 bad signature | — |
| GET `booking-lookup.php` | ref + contact, or ref + token | `ref, contact` or `ref, token` | booking with rooms, extras, total, paid, `due_now`, `due_later`, `refund_due`, `modified_at`, cancellation | 404 not found | 15 / 5 min |
| POST `booking-cancel.php` | — | — | always **403** + call/WhatsApp message | — | — |
| POST `enquiry.php` | none | `{name*, phone*, email, property, check_in, check_out, guests, interest, message, website(honeypot)}` | `{ok,id,message}` | phone/email/dates/guests/length checks | 8 / 10 min |

All from [CODE `backend/booking-engine/api/*.php`].

## Non-JSON endpoints
* `receipt.php?ref=&token=` → PDF (guest token or signed-in staff; 404 otherwise) [CODE].
* `terms.php?property=` → PDF [CODE].
* `document.php?doc=receipt|terms&…` → page that shows the PDF with PDF.js [CODE].
* Admin pages are HTML forms with CSRF tokens (see [auth_and_security.md](auth_and_security.md)) [CODE `admin/`].

## Express (optional)
`POST /api/contact` → appends to `/data/enquiries.json` [CODE `backend/server/index.ts`]; the website does not call it yet [CODE `Home.tsx` mock].
