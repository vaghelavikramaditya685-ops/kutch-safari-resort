# Input map

| ID | Field / param | Where | Expected | Required | Saved to |
|---|---|---|---|---|---|
| V | check_in, check_out, rooms, occupancy, property | api/availability.php | real dates within 2 years; 1-5 rooms; 1-3 per room; an active property | yes | nothing (read only) |
| Q | rooms[], occupancy[], addons[], payment_mode, coupon, dates | api/quote.php, book.php | cottage+plan of the property; whole numbers 1-50 per extra; full/advance | yes | booking_rooms, booking_addons, bookings |
| G | name, phone, email, city, arrival_time, special_requests | api/book.php | text <=120 / 7-15 digits / email / <=80 / h:mm AM-PM / <=2000 | optional in sample mode | bookings.guest_* |
| L,P | ref, contact, token, booking_id, method | booking-lookup, payment-test, payment-create | existing booking + its private code | yes | payments |
| E | name, phone, email, guests, check_in/out, interest, message, property, website | api/enquiry.php | as bookings; guests 1-500; message <=3000 | name, phone | enquiries |
| A | amount, method, note, reason, special_requests | admin/booking.php | amount up to the balance; listed method; notes <=300/2000; reason <=250 | yes | payments, bookings |
| C | dates, room[n][cottage], room[n][guests], addon[id] | admin/edit.php | as bookings (desk may change a stay under way) | yes | booking_rooms, booking_addons |
| R | from, to, plans[], price | admin/rates.php | real future dates <=400 nights; price guard rails | yes | rates |
| N | id, status, staff_note | admin/enquiries.php | existing id; listed status; note <=2000 | yes | enquiries |
| U | id, property, start, days, q, filters | admin pages (URL) | existing ids / active property / real date | no | nothing |
