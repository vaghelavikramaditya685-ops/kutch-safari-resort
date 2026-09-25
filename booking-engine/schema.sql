-- ===========================================================================
--  KUTCH SAFARI RESORT / WHITE RANN CAMP — booking engine schema (MySQL 5.7+)
--  ---------------------------------------------------------------------------
--  Import this once into the MySQL database you create in cPanel:
--      cPanel → MySQL Databases → create DB + user → phpMyAdmin → Import
--  Or from a shell:  mysql -u USER -p DBNAME < schema.sql
-- ===========================================================================

SET NAMES utf8mb4;
SET time_zone = '+05:30';

-- ---------------------------------------------------------------------------
-- Properties. One row per place a guest can book.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS properties (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  code          VARCHAR(40)  NOT NULL UNIQUE,   -- 'kutch-safari-resort'
  name          VARCHAR(120) NOT NULL,
  tagline       VARCHAR(200) DEFAULT NULL,
  phone         VARCHAR(40)  DEFAULT NULL,
  phone_alt     VARCHAR(40)  DEFAULT NULL,
  email         VARCHAR(120) DEFAULT NULL,
  address       TEXT,
  gst_number    VARCHAR(20)  DEFAULT NULL,
  website_url   VARCHAR(200) DEFAULT NULL,
  logo          VARCHAR(200) DEFAULT NULL,
  accent        VARCHAR(10)  DEFAULT '#B85C2E', -- themes the engine per property
  check_in_time VARCHAR(10)  DEFAULT '12:00',
  check_out_time VARCHAR(10) DEFAULT '10:00',
  -- A seasonal property refuses dates outside its window (the camp runs Dec–Jan).
  season_start  DATE DEFAULT NULL,
  season_end    DATE DEFAULT NULL,
  -- Stayflexi identifiers, filled in once you have API access.
  sf_hotel_id   VARCHAR(60) DEFAULT NULL,
  active        TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Room types (cottages, tents).
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS room_types (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  property_id   INT NOT NULL,
  code          VARCHAR(40) NOT NULL,
  name          VARCHAR(120) NOT NULL,
  description   TEXT,
  total_rooms   INT NOT NULL DEFAULT 0,       -- physical rooms owned
  base_occupancy INT NOT NULL DEFAULT 2,      -- price covers this many adults
  max_adults    INT NOT NULL DEFAULT 3,
  max_children  INT NOT NULL DEFAULT 1,
  extra_adult_price  DECIMAL(10,2) NOT NULL DEFAULT 0,
  extra_child_price  DECIMAL(10,2) NOT NULL DEFAULT 0,
  bed_type      VARCHAR(120) DEFAULT NULL,
  size_label    VARCHAR(60)  DEFAULT NULL,
  images        TEXT,                          -- JSON array of image paths
  amenities     TEXT,                          -- JSON array of strings
  sort_order    INT NOT NULL DEFAULT 0,
  sf_room_type_id VARCHAR(60) DEFAULT NULL,    -- Stayflexi mapping
  active        TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_room_code (property_id, code),
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Rate plans — the meal basis a room is sold on (EP / CP / MAP / AP).
-- One room type can be sold on several, as on the Rann Riders engine.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rate_plans (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  room_type_id  INT NOT NULL,
  code          VARCHAR(20) NOT NULL,          -- 'CP', 'MAP', 'AP'
  name          VARCHAR(120) NOT NULL,         -- 'Room with breakfast'
  meal_note     VARCHAR(200) DEFAULT NULL,
  base_price    DECIMAL(10,2) NOT NULL,        -- fallback when no date rate exists
  refundable    TINYINT(1) NOT NULL DEFAULT 1,
  sort_order    INT NOT NULL DEFAULT 0,
  sf_rate_plan_id VARCHAR(60) DEFAULT NULL,
  active        TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_plan_code (room_type_id, code),
  FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- The rate calendar: one row per rate plan per date. Anything missing falls
-- back to rate_plans.base_price, so you only store the dates that differ
-- (peak dates, Rann Utsav, Uttarayan, the full moon nights).
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rates (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  rate_plan_id  INT NOT NULL,
  stay_date     DATE NOT NULL,
  price         DECIMAL(10,2) NOT NULL,
  min_stay      INT NOT NULL DEFAULT 1,
  closed        TINYINT(1) NOT NULL DEFAULT 0, -- stop-sell for this date
  UNIQUE KEY uq_rate (rate_plan_id, stay_date),
  FOREIGN KEY (rate_plan_id) REFERENCES rate_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- The inventory calendar. `rooms_open` is how many of this room type may be
-- sold on this date; leave a date out and room_types.total_rooms is used.
-- When Stayflexi is connected this table is refreshed from it.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS inventory (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  room_type_id  INT NOT NULL,
  stay_date     DATE NOT NULL,
  rooms_open    INT NOT NULL,
  note          VARCHAR(200) DEFAULT NULL,     -- 'blocked for maintenance'
  synced_at     DATETIME DEFAULT NULL,
  UNIQUE KEY uq_inv (room_type_id, stay_date),
  FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Extras sold on the payment step.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS addons (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  property_id   INT NOT NULL,
  code          VARCHAR(40) NOT NULL,
  name          VARCHAR(120) NOT NULL,
  description   VARCHAR(255) DEFAULT NULL,
  price         DECIMAL(10,2) NOT NULL,
  -- how the price multiplies out
  price_type    VARCHAR(20) NOT NULL DEFAULT 'per_booking', -- per_booking|per_person|per_night|per_room_night
  tax_rate      DECIMAL(5,2) NOT NULL DEFAULT 5.00,
  image         VARCHAR(200) DEFAULT NULL,
  sort_order    INT NOT NULL DEFAULT 0,
  active        TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_addon (property_id, code),
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Colors of Kutch packages. Priced per person, cheaper as the group grows.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS packages (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  property_id   INT NOT NULL,
  code          VARCHAR(40) NOT NULL UNIQUE,
  name          VARCHAR(160) NOT NULL,
  summary       VARCHAR(255) DEFAULT NULL,
  nights        INT NOT NULL,
  itinerary     TEXT,                           -- JSON [{day,title,detail}]
  inclusions    TEXT,                           -- JSON array
  exclusions    TEXT,                           -- JSON array
  image         VARCHAR(200) DEFAULT NULL,
  tax_rate      DECIMAL(5,2) NOT NULL DEFAULT 5.00,
  active        TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS package_prices (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  package_id    INT NOT NULL,
  min_pax       INT NOT NULL,                   -- price applies from this group size up
  price_per_person DECIMAL(10,2) NOT NULL,
  extra_person_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Bookings.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bookings (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  ref           VARCHAR(20) NOT NULL UNIQUE,    -- 'KSR-7F3K2A' — given to the guest
  property_id   INT NOT NULL,
  package_id    INT DEFAULT NULL,               -- set when this is a package booking
  status        VARCHAR(20) NOT NULL DEFAULT 'pending',
                -- pending | confirmed | cancelled | no_show | completed
  check_in      DATE NOT NULL,
  check_out     DATE NOT NULL,
  nights        INT NOT NULL,
  adults        INT NOT NULL DEFAULT 2,
  children      INT NOT NULL DEFAULT 0,

  guest_name    VARCHAR(160) NOT NULL,
  guest_email   VARCHAR(160) DEFAULT NULL,
  guest_phone   VARCHAR(40)  NOT NULL,
  guest_city    VARCHAR(120) DEFAULT NULL,
  guest_country VARCHAR(80)  DEFAULT 'India',
  special_requests TEXT,
  arrival_time  VARCHAR(40) DEFAULT NULL,

  rooms_subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  addons_subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  discount      DECIMAL(10,2) NOT NULL DEFAULT 0,
  coupon_code   VARCHAR(40) DEFAULT NULL,
  tax_amount    DECIMAL(10,2) NOT NULL DEFAULT 0,
  total         DECIMAL(10,2) NOT NULL DEFAULT 0,
  amount_paid   DECIMAL(10,2) NOT NULL DEFAULT 0,
  -- how the guest chose to pay: full | advance | hotel
  payment_mode  VARCHAR(20) NOT NULL DEFAULT 'full',
  amount_due_now DECIMAL(10,2) NOT NULL DEFAULT 0,

  source        VARCHAR(40) NOT NULL DEFAULT 'website',
  -- Stayflexi's id for this reservation, once pushed.
  sf_booking_id VARCHAR(60) DEFAULT NULL,
  sf_synced_at  DATETIME DEFAULT NULL,
  sf_sync_error TEXT,

  manage_token  VARCHAR(64) NOT NULL,           -- lets a guest view/cancel without an account
  created_at    DATETIME NOT NULL,
  updated_at    DATETIME DEFAULT NULL,
  cancelled_at  DATETIME DEFAULT NULL,
  cancel_reason VARCHAR(255) DEFAULT NULL,
  refund_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  KEY idx_dates (check_in, check_out),
  KEY idx_status (status),
  FOREIGN KEY (property_id) REFERENCES properties(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One row per room type + rate plan in the booking (a guest may book two
-- categories at once).
CREATE TABLE IF NOT EXISTS booking_rooms (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  booking_id    INT NOT NULL,
  room_type_id  INT NOT NULL,
  rate_plan_id  INT NOT NULL,
  room_type_name VARCHAR(120) NOT NULL,         -- copied so history survives edits
  rate_plan_name VARCHAR(120) NOT NULL,
  rooms         INT NOT NULL DEFAULT 1,
  adults        INT NOT NULL DEFAULT 2,
  children      INT NOT NULL DEFAULT 0,
  extra_adults  INT NOT NULL DEFAULT 0,
  nightly       TEXT,                            -- JSON {date: price} for the invoice
  subtotal      DECIMAL(10,2) NOT NULL,
  tax_amount    DECIMAL(10,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS booking_addons (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  booking_id    INT NOT NULL,
  addon_id      INT NOT NULL,
  addon_name    VARCHAR(120) NOT NULL,
  quantity      INT NOT NULL DEFAULT 1,
  unit_price    DECIMAL(10,2) NOT NULL,
  subtotal      DECIMAL(10,2) NOT NULL,
  tax_amount    DECIMAL(10,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Payments. Razorpay, the direct UPI QR, and anything taken offline.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS payments (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  booking_id    INT NOT NULL,
  provider      VARCHAR(20) NOT NULL,           -- razorpay | upi_qr | offline
  purpose       VARCHAR(20) NOT NULL DEFAULT 'booking', -- booking | balance | refund
  order_id      VARCHAR(80) DEFAULT NULL,       -- razorpay_order_id
  payment_id    VARCHAR(80) DEFAULT NULL,       -- razorpay_payment_id
  signature     VARCHAR(255) DEFAULT NULL,
  upi_ref       VARCHAR(80) DEFAULT NULL,       -- the note/ref shown on the QR
  method        VARCHAR(40) DEFAULT NULL,       -- upi | card | netbanking …
  amount        DECIMAL(10,2) NOT NULL,
  status        VARCHAR(20) NOT NULL DEFAULT 'created', -- created|paid|failed|refunded|awaiting_confirmation
  raw_response  TEXT,
  verified_by   VARCHAR(120) DEFAULT NULL,      -- staff member, for UPI QR payments
  created_at    DATETIME NOT NULL,
  paid_at       DATETIME DEFAULT NULL,
  KEY idx_order (order_id),
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- A short hold on inventory while the guest is on the payment screen, so two
-- people cannot buy the last cottage at the same moment.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS holds (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  token         VARCHAR(64) NOT NULL UNIQUE,
  room_type_id  INT NOT NULL,
  check_in      DATE NOT NULL,
  check_out     DATE NOT NULL,
  rooms         INT NOT NULL DEFAULT 1,
  booking_id    INT DEFAULT NULL,
  expires_at    DATETIME NOT NULL,
  created_at    DATETIME NOT NULL,
  KEY idx_expiry (expires_at),
  FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Enquiries — the offline path stays alive alongside the engine.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS enquiries (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  property_id   INT DEFAULT NULL,
  name          VARCHAR(160) NOT NULL,
  phone         VARCHAR(40) NOT NULL,
  email         VARCHAR(160) DEFAULT NULL,
  check_in      DATE DEFAULT NULL,
  check_out     DATE DEFAULT NULL,
  guests        VARCHAR(60) DEFAULT NULL,
  interest      VARCHAR(160) DEFAULT NULL,
  message       TEXT,
  status        VARCHAR(20) NOT NULL DEFAULT 'new', -- new|contacted|converted|closed
  staff_note    TEXT,
  created_at    DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Discount codes.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS coupons (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  code          VARCHAR(40) NOT NULL UNIQUE,
  description   VARCHAR(200) DEFAULT NULL,
  discount_type VARCHAR(10) NOT NULL DEFAULT 'percent', -- percent | flat
  amount        DECIMAL(10,2) NOT NULL,
  min_nights    INT NOT NULL DEFAULT 1,
  valid_from    DATE DEFAULT NULL,
  valid_to      DATE DEFAULT NULL,
  max_uses      INT DEFAULT NULL,
  times_used    INT NOT NULL DEFAULT 0,
  active        TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Staff logins for the admin panel.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(160) NOT NULL UNIQUE,
  name          VARCHAR(120) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          VARCHAR(20) NOT NULL DEFAULT 'staff', -- owner | staff
  last_login    DATETIME DEFAULT NULL,
  active        TINYINT(1) NOT NULL DEFAULT 1,
  created_at    DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Anything that changes money or inventory is written here.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_log (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  actor         VARCHAR(120) NOT NULL DEFAULT 'system',
  action        VARCHAR(80) NOT NULL,
  entity        VARCHAR(40) DEFAULT NULL,
  entity_id     VARCHAR(40) DEFAULT NULL,
  detail        TEXT,
  ip            VARCHAR(45) DEFAULT NULL,
  created_at    DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Settings you may want to change without touching code.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
  name          VARCHAR(60) PRIMARY KEY,
  value         TEXT,
  note          VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
