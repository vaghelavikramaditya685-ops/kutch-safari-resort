<?php
/* Price a cart without committing. POST a cart object. */
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../lib/booking.php';
require_post();
rate_limit('quote', 90, 60);
json_out(quote_cart(input()));
