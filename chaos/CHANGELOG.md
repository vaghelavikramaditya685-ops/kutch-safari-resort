# Changelog

| Finding | File | Change |
|---|---|---|
| all | backend/booking-engine/lib/db.php | LIMITS, too_long(), valid_phone(), valid_arrival_time(), valid_date() |
| 1-3 | lib/booking.php (quote_cart) | extras: unknown refused; quantity must be 1..rules.max_extra_quantity; rooms/addons must be lists |
| 4-6 | lib/booking.php (create_booking) | guest name/city/note length, phone format, arrival time format; field=guest on refusal; missing name/phone safe |
| 7 | lib/booking.php (validate_dates, validate_dates_staff) | real calendar dates; up to rules.max_days_ahead |
| 7 | config.php | rules.max_days_ahead = 730, rules.max_extra_quantity = 50 |
| 8 | api/enquiry.php | phone, name, email, dates, guests, interest, message checks |
| 9,10,13 | admin/booking.php | method list; note/reason limits; red error messages; trimmed values |
| 10-13 | admin/enquiries.php | status list; enquiry must exist; note limit; red errors; maxlength |
| 14 | admin/calendar.php | unknown property / bad start date fall back |
| 15 | admin/rates.php | "Choose the first and the last night." |
| 16 | api/_init.php | unreadable JSON -> clear 400 |
| 19 | admin/export.php | csv_safe() on guest-typed cells |
| UX | assets/engine.js | maxlength on guest fields; phone/email checked before sending; refused details keep the guest on the step with their input |
