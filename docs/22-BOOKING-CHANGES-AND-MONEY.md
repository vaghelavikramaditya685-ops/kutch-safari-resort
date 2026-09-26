# Booking Changes and Money Logic

_Written 26 Sep 2026. How the desk changes a booking (Admin → booking → **Change this booking**), how the new price is worked out, and how "collect / give back" follows the guest's payment choice. Code: `lib/booking.php` → `quote_modification()`, `modification_delta()`, `booking_money()`, `modify_booking()`; screen: `admin/edit.php`._

## 1. The rule the owner asked for
> "What is added, total it and add it to their price. What is removed, total it and take it off the full amount."

So a change is **never re-priced from scratch**:

```
new total = old total + added − taken off ± changed
```

Everything the guest keeps stays at **exactly the amount it was booked at**, even if the tariff or a special price has moved since. Only new things are priced, at today's prices.

### Why it had to be rebuilt
The first version re-ran the whole cart through `quote_cart()` (with the old nightly prices "locked"). The total still drifted, because GST, the single/extra-bed adjustment and the discount were worked out again from today's settings. On a real test booking, adding one ₹7,450 Kutchi room moved the total from ₹22,000 to ₹30,050 instead of ₹29,450. The preview also mixed the change with money already owed. On a 50%-paid booking it said "collect ₹33,300" when the change was only +₹2,100.

## 2. How the difference is worked out (`modification_delta()`)
1. `quote_cart()` still runs first on the new cart. That checks availability (with this booking's own rooms left out, `availability_ignore_booking()`), dates, occupancy and extras, and prices anything **new**. Nights the guest already had are priced at their booked nightly price (`pricing_locks()`), and the code stays on (`keep_coupon`).
2. The old booking and the new cart are each broken into **one entry per room**: cottage type, rate plan, occupancy (read from the line name "Room 2 Double", see doc 28), its `[before tax, GST]` amount and its nightly prices.
3. Rooms are matched old ↔ new:
   * **Same cottage, plan and occupancy:** nights kept keep their stored share. Extra nights are **added** at the new price, and nights dropped are **taken off** at their stored share.
   * **Same cottage and plan, different occupancy** (e.g. Double → Triple): nights kept are **changed** by (new − old). Extra and dropped nights are as above.
   * **Left over new:** **added** in full. **Left over old:** **taken off** in full.
4. A room's share of a night is proportional to that night's price (special prices make nights differ). The whole stay kept means the exact stored amount, with no rounding.
5. **Extras**, by extra: the units kept keep their stored amount, more units are **added** at the new price, and fewer units are **taken off** at their stored per-unit amount. Extras priced by the night (`per_night`, `per_room_night`) are re-priced when the number of nights changes and shown as **changed**.
6. **Discount code:** a percent code moves by its percentage on the rooms added or taken off (kept rooms keep their discount). A fixed code stays the same.
7. The new booking lines are rewritten to those amounts, and the totals are summed from them. Any paise left from how an *older* booking was rounded go into the GST figure so the parts still add up.

The result, `$q['changes']`:
```php
['added'   => [['what' => 'Deluxe AC Cottage (Single), 16 Oct – 20 Oct (4 nights)', 'amount' => 22000], …],
 'removed' => [['what' => 'Airport transfer, one way — Innova × 1', 'amount' => -2100], …],
 'changed' => [['what' => 'Kutchi AC Cottage: Double → Single, 16 Oct – 19 Oct (3 nights)', 'amount' => -2850], …],
 'added_total', 'removed_total', 'changed_total', 'change']
```
It is also saved in the audit log with the change (`booking_modified`), so the booking page can say "Old total ₹X + added ₹A − taken off ₹R = new total ₹N" after saving.

## 3. Money: follows how the guest chose to pay (`booking_money()`)
| Field | Paid in full | 50% advance |
|---|---|---|
| `needed_now` | 100% of total | 50% of total (`payment_modes.advance.percent`) |
| `due_now` | total − paid | max(0, 50% − paid) |
| `later` | 0 | total − max(paid, 50%), **due before arrival** |
| `refund` | paid − total, if over | paid − total, if over |

* The 50% plan's balance is labelled **"before arrival"** because that is what guests are told (`payment_modes.advance.note`: "Balance due 30 days before arrival"). If the owner collects it at check-in instead, change that note and the labels together.
* Used by: the change preview (`admin/edit.php`), the booking page ("Due now", "Due before arrival", the Collect form), the Availability side panel, the guest's check-status page (`api/booking-lookup.php` → `due_now`, `due_later`) and the receipt PDF.
* A cancelled booking owes nothing and is owed nothing here. Its refund is `bookings.refund_amount`.

## 4. After saving
* `modify_booking()` re-checks availability under a lock, deletes and re-inserts the booking's rooms and extras, updates the totals, audits old → new with the breakdown, and (once Stayflexi is connected) cancels and re-pushes the reservation there.
* It does **not** move money. The desk records it on the booking page: **Collect the balance** (`record_offline_payment()`) or **Give back** (`record_offline_refund()`). `bookings.amount_paid` is always payments − refunds (`refresh_amount_paid()`).
* The guest sees the new details, "Updated by the resort", and the new due / refund on the check-status page and receipt.

## 5. Worked examples (all pass in the test script, see doc 30)
Booking: Kutchi Double + Deluxe Double, 3 nights, 1 Innova = ₹43,950.

| Change | Result |
|---|---|
| + 2 candlelight dinners | + ₹6,000 → ₹49,950 |
| − Innova | − ₹2,100 → ₹41,850 |
| − Deluxe room | − ₹19,500 → ₹24,450 |
| + 1 night | + ₹7,450 + ₹6,500 → ₹57,900 |
| Kutchi Double → Triple | changed + ₹4,500 → ₹48,450 |
| Same length, one day later | + (new night) − (old night) → ₹43,950 |
| Booked at a special ₹5,000 × 3, special later removed, + 1 night | ₹15,000 + ₹7,450 = ₹22,450 (booked nights stay ₹5,000) |
| Several at once (+room, occupancy, extras, +night) | adds up exactly, and the saved booking equals the preview |
| 50% paid (₹9,750 of ₹19,500), + Innova | total ₹21,600; collect now ₹1,050; ₹10,800 before arrival |
| 50% paid, cut to 1 night (₹6,500) | give back ₹3,250 |
| Paid in full, + Innova | collect now ₹2,100 |

## 6. Things to know
* The desk can change a stay that has already started, and isn't bound by the online 5-room limit (`staff_edit`).
* Room numbers in the line names ("Room 1", "Room 2") are positions in the booking, **not** physical cottage numbers. They may renumber after a change.
* Changing nothing and checking the price shows "Nothing that changes the price" and the same total.
