# Pricing Logic Deep Dive (booking engine)

_Written 26 Sep 2026. This is how a price is actually worked out in code, and why it is done that way. For the tariff itself see `08-PACKAGES-AND-PRICING.md`; for changing an existing booking see `22-BOOKING-CHANGES-AND-MONEY.md`._

## 1. One pricing function for everything
Every room price the guest ever sees comes from **`price_rooms()`** in `backend/booking-engine/lib/inventory.php`. The room list (`search_availability()`), the checkout summary (`quote_cart()` in `lib/booking.php`), the booking that is saved (`create_booking()`), and a desk change (`quote_modification()`) all go through it.

> Why: earlier the room list and checkout priced rooms separately and disagreed (one divided adults by the number of lines, so 4 adults in 2 rooms were charged as 4 per room). One function means one answer.

```
price_rooms(property, room_type, plan, check_in, check_out, occupancy[])
  └─ price_rate_plan(plan, check_in, check_out)   → the double price for each night
       ├─ special price for that night?  (rates table)  → use it
       ├─ locked price for that night?   (pricing_locks, only while changing a booking) → use it
       └─ otherwise                       → rate_plans.base_price
  └─ for each room, for each night:
       night = double
             − (base_price − single_price)           if the room is Single
             + extra_adult_price × (guests − 2)      if the room is Triple (extra bed)
       [before tax, GST] = tax_split(night, inclusive?)
```

## 2. Occupancy: Single / Double / Triple per room
* The guest picks the occupancy **for each room** (`occupancy` = e.g. `[2, 1, 3]`).
* `rate_plans.base_price` is the **double** price. `rate_plans.single_price` is the single price. The single keeps the same **discount** (base − single) on every night, including special-price nights. Example: Kutchi base ₹7,450, single ₹6,500, so the discount is ₹950. On a special night of ₹8,000 the single is ₹7,050.
* Triple = double + `room_types.extra_adult_price` (₹1,500). That is the extra bed. The old "Extra bed" add-on was removed so it cannot be charged twice.
* `normalise_occupancy()` makes sure there is one number per room and clamps each to 1…`max_adults`.

## 3. GST: inclusive for the resort, on top for the camp
`config.php → prices_include_tax = ['kutch-safari-resort']`.

* **Inclusive (resort):** the tariff is what the guest pays. `tax_split()` takes GST **out**. The slab is decided on the value **before** tax: ₹7,450 incl. 5% = ₹7,095.24 + ₹354.76, which is under ₹7,500, so 5% is right.
* **Exclusive (White Rann Camp, currently switched off):** the tariff is the taxable value and GST is added on top.
* Slabs (`config.php → tax_slabs`): up to ₹7,500 → 5%, above → 18%. They apply **per room per night**, on the room's price including any extra bed.
* **Edge case (needs the accountant):** inclusive prices between ₹7,875 and ₹8,850 fit neither slab cleanly. The Deluxe triple at ₹8,000 is split at 18% (₹6,779.66 + ₹1,220.34). The guest pays ₹8,000 either way; only the invoice split changes.

## 4. Special prices (per night, never the normal price)
Admin → **Special prices** (`admin/rates.php`) writes rows into `rates (rate_plan_id, stay_date, price, min_stay, closed)`. Each row replaces the **double** price for one night of one plan.

* A stay is priced **night by night**. A stay of 4 normal nights + 5 special nights is 4 × normal + 5 × special. This was tested: ₹39,800 for that case.
* Guard rails in `rates.php`: never under ₹500 (`PRICE_FLOOR`); refused below 25% or above 400% of normal (`HARD_LOW`/`HARD_HIGH`); a confirm tick is needed below 60% or above 150% (`CONFIRM_LOW`/`CONFIRM_HIGH`); no past dates; a "check" step before saving.
* `closed = 1` on a night stops that plan being sold that night. `min_stay` refuses shorter stays that touch that night.
* Removing a special price deletes those rows, so the nights go back to `base_price`.

## 5. Extras (add-ons)
`addon_amount()` in `lib/inventory.php`:

| `price_type` | Amount |
|---|---|
| `per_booking` | price × quantity |
| `per_person` | price × quantity (quantity = people). `addons.min_quantity` is enforced (gala dinner: 10) |
| `per_night` | price × quantity × nights |
| `per_room_night` | price × quantity × nights |

* The resort's extras are GST-inclusive too. The tax is taken out at `addon_tax_rate` (5%).
* Extras whose names follow **"Group — Choice"** (e.g. "Airport transfer, one way — Innova") are shown as one card with a counter per choice. This is purely a naming convention, read by `assets/engine.js`. Rename carefully.
* `addon_is_per_room()` extras are assigned to numbered rooms, and the room numbers are written into the line name (" — Room 2"). None of the current extras use this.

## 6. Discount codes
`coupons` table. `quote_cart()` checks `active`, `min_nights`, `valid_from`/`valid_to` (on check-in) and `max_uses`. `percent` codes apply to the **rooms** subtotal only (not extras); `fixed` codes take off a flat amount, never more than the rooms. When the desk changes a booking the code is kept even if it has since expired (`keep_coupon`). See doc 22.

## 7. Rounding
* `money()` rounds to 2 decimals.
* Each room-night is split into [before tax, GST] and then summed. Totals therefore always add up on the receipt: rooms + extras − discount + GST = total.
* In a change, a partial stay is rounded as **amount first, GST inside it second**, so ₹7,450 a night stays exactly ₹7,450 (it once came out as ₹7,450.01).

## 8. What is paid now
`quote_cart()` returns `amount_due_now`: the full total, or `payment_modes.advance.percent` (50%) of it. "Pay at the property" (`hotel`) is switched off, so a booking is never left with nothing paid. After booking, `booking_money()` (doc 22) works out due now / due before arrival / refund from the payments.

## 9. Where to change what
| To change | Edit |
|---|---|
| Normal room price | `rate_plans.base_price` / `single_price` (database; `seed.sql` for a fresh install) |
| Extra bed | `room_types.extra_adult_price` |
| Price on certain dates | Admin → Special prices |
| GST slabs, inclusive or not | `config.php` → `tax_slabs`, `prices_include_tax` |
| Deposit percentage | `config.php` → `payment_modes.advance.percent` |
| Extras | `addons` table (name, price, `price_type`, `min_quantity`, `active`) |
