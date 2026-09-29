# Lessons Learned and Gotchas

_Written 26 Sep 2026. Problems hit while building and fixing this project, why they happened, and what was done. Read this before changing the engine. Most of these are not obvious from the code._

## Pricing and money
| Problem | Cause | Fix |
|---|---|---|
| 4 adults in 2 rooms were charged as 4 per room | Adults were divided by the number of booking **lines**, not rooms | Per-room occupancy; one pricing function `price_rooms()` (doc 21) |
| Room list and checkout disagreed | Two separate price calculations | Everything goes through `price_rooms()` |
| Summary on the booking page didn't add up | Screen re-added figures itself | It shows the quote's own figures, GST-inclusive |
| Changing a booking re-priced **all** of it at today's rates and dropped expired codes | `quote_cart()` re-ran on the whole cart | First, price locks (`pricing_locks()`, `keep_coupon`). Then, on 26 Sep 2026, the **difference method**: old total + added − taken off (doc 22) |
| Adding one ₹7,450 room raised a ₹22,000 booking to ₹30,050 | Re-pricing moved kept rooms (GST split, single discount) | Kept rooms keep their stored amount |
| "Collect ₹33,300" on a change worth +₹2,100 | The preview showed total − paid, mixing in the old unpaid balance | Separate "What changes" and "Money" blocks, with collect now / before arrival from the payment choice |
| A night came out at ₹7,450.01 | Tax and pre-tax each rounded separately | Round the amount first, then the GST inside it |
| Refunds were forgotten when a later online payment settled | `settle_payment()` and `upi_mark_received()` summed payments only | Both use `refresh_amount_paid()` (payments − refunds) |
| Deluxe triple ₹8,000 fits neither GST slab | Inclusive prices between ₹7,875 and ₹8,850 | Uses 18%. **Accountant to confirm** (doc 08 §5) |

## Availability
| Problem | Cause | Fix |
|---|---|---|
| Abandoned checkouts blocked rooms forever | Every `pending` booking counted | Unpaid bookings stop counting 45 min after creation (doc 23) |
| Two rooms of one type could both take the last free cottage | Availability was checked per line | Checked cumulatively per type, and re-checked under a lock |
| Moving a stay by one night was refused | The booking collided with itself | `availability_ignore_booking()` while changing |

## Dates and time
* **Check-out equal to check-in after midnight:** `toISOString()` gives UTC. India is +5:30, so between 00:00 and 05:30 the date was a day behind. The browser code uses local dates (`isoLocal()`, `todayISO()`).
* **Cancellation dates were labelled with the day a period ends**, and past periods still showed. `cancellation_schedule()` returns `from` / `to` / `past`.
* PHP runs in `Asia/Kolkata` (`config.php → timezone`). Keep the server's `php.ini` timezone the same.

## Local setup on Windows
* **Rooms didn't load (spinner forever):** the Vite proxy used `localhost`, which resolved to IPv6 `::1`, but PHP listened on IPv4 only. The proxy target is `http://127.0.0.1:8080`.
* **Razorpay "HTTP 0":** PHP on Windows has no CA bundle. `curl_trust_system_certs()` uses the Windows certificate store.
* PHP 8.3 is installed with winget. `php.ini` needs `pdo_sqlite`, `sqlite3`, `curl`, `mbstring`, `openssl`, `fileinfo`, and `date.timezone = Asia/Kolkata`.
* Long scratch paths and shell quoting broke inline scripts. Edits were written as small Python/PHP files instead.

## Admin panel
* **`/book/admin` (no slash) broke the page:** relative links resolved one level too high. `_auth.php` now redirects to `/book/admin/`, and the address fills itself in to `…/admin/login.php`.
* **"Why did it sign me in automatically?":** the session lasted 8 hours across tabs. Now the password is asked for in every new tab or window, after the browser closes, and after 10 minutes idle.
* **Browser pop-ups** (`confirm()`) looked out of place and the owner didn't want them. They are replaced by an on-page box (`data-confirm`, doc 24 §8).
* **A cash form showed on a fully paid booking:** it is now shown only while money is owed, and the server refuses overpayment.
* Owner feedback that shaped the screens: room-by-room tabs → a Select button with room ticks; a card layout → "the old table style was good"; a calendar by cottage → by guest/date, full width; "Change rooms on sale" → removed.

## Front end
* **wouter route props** gave a TypeScript error. The redirect routes use children render functions.
* **Sticky elements overlapped on scroll:** the whole side panel is sticky, not parts of it, and the room "Continue" bar is not sticky.
* **Header buttons overflowed** on mid-size screens. The engine header wraps below 960 px, and admin nav items use `flex: auto`.
* **A button with the `hidden` attribute still showed:** `.btn { display: inline-flex }` beats the browser's `[hidden]` rule. Add an explicit `[hidden] { display: none }` for such buttons (see the arrival wheel's Clear).
* Headings ignore a parent's text colour (`index.css` base rule). Put the colour on the heading (doc 04 §3).

## Found on 29 Sep 2026 (self-healing, button pipeline, chaos monkey)
| Problem | Cause | Fix |
|---|---|---|
| Favicon 404 on every engine page, even after adding an icon | this browser ignores data-URI SVG icons and asks for `/favicon.ico` anyway | a real `/favicon.ico` at the website root (where the engine's `/book/` pages get it in production) + `assets/icon.svg` |
| Old `engine.js` kept by browsers after an update | no version on script/style URLs | `?v=<filemtime>` on engine.js/engine.css/admin.css |
| Section jumps "didn't work" in a test browser | smooth scrolling never animates in a pane that isn't painting | jumps use `behavior: "instant"` (better for visitors too) |
| Booking page 389 px wide on a 375 px phone | a `white-space: nowrap` header button | header buttons wrap below 960 px |
| 1,000,000 cars → ₹2.1 billion quote; 0 or −5 cars → 1 car | "forgiving" code: `max(1, (int) $qty)`, no upper limit | refuse 0/negative/words; cap at `rules.max_extra_quantity` (50) |
| 200,000-character notes saved | SQLite has no length limits; MySQL would crash | `LIMITS` + `too_long()` everywhere, `maxlength` on fields |
| `check-system.php` put a booking in the real DB and tested a switched-off property | written before the rules | runs on a temporary copy; uses the property on sale |
| A guest-detail error sent the guest back to step 1, losing their typing | error path always went to the rooms step | `field: "guest"` keeps them on the details step with their input |
| Admin errors looked like successes | one green message box for everything | red for refusals |
| Test sessions "failed" mid-run | the 10-minute idle sign-out (working as designed) | sign in again in long scripts |

## Process rules the owner set
* **Don't push to GitHub** unless asked. The one push was on 25 Sep 2026; everything since is uncommitted.
* **No synthetic data** in the real database. Test on throwaway copies (doc 28 §3, doc 30).
* Keep the colours. Only the finish changed (matte and smooth).
* No bot notifications for now.
* Secrets only in `config.local.php`. Copies given to others leave the Razorpay keys out.
