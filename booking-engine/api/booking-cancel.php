<?php
/* Guests cannot cancel online. Cancellations are made by the reservations desk
   in the admin panel (Bookings → "Cancel a booking" → enter the booking code),
   so this endpoint only tells the guest how to reach us. */
require_once __DIR__ . '/_init.php';
json_fail('To cancel, please call or WhatsApp us on ' . cfg('contact.phone') . ' with your booking code.', 403);
