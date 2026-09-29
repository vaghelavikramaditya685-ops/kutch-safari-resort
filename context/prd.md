# Product Requirements
> Purpose: what the product must do and why | Mode: A | Confidence: Medium-High | Related: [features.md](features.md), [user_stories.md](user_stories.md), [roadmap.md](roadmap.md)

## Problem
The resort took bookings by phone, WhatsApp and online travel agencies (OTAs, via Stayflexi) [DOC `docs/27`]. The previous website had a React booking engine that stored reservations in a JSON file and could not run on Vercel [DOC `docs/20`]. Direct bookings mean no OTA commission [INFERRED: usual reason for a direct engine].

## Vision
A beige, "Sundown Terracotta" website that shows the resort and Kutch [DOC `docs/04`], and a booking engine where a guest books and pays in a few minutes, while the owner manages everything (bookings, changes, money, availability, prices) from one admin panel [DOC].

## Users
* **Guest**: books online, pays 50% or in full, checks status later, receives a receipt [CODE `index.php`, `manage.php`].
* **Owner / front desk** (single admin role `owner`): sees the day's arrivals, changes bookings, records cash/UPI, cancels, sets special prices [CODE `admin/`].

## Goals
1. Book any mix of Kutchi / Deluxe cottages, Single/Double/Triple per room, with extras (cars, dinners) [CODE `lib/booking.php quote_cart`].
2. Take money: Razorpay online, UPI QR confirmed by the desk, or at the desk [CODE `lib/payment.php`].
3. Price changes transparently: old total + added − taken off [CODE `modification_delta`] (owner requirement [DOC]).
4. Never sell a room twice (availability locks, Stayflexi bridge) [CODE `rooms_booked`, `lib/channel.php`].
5. Owner-controlled data only: no invented data in the real database [DOC owner rule; exception: 6 marked demo bookings, 29 Sep].

## Non-goals (for now)
* White Rann Camp online booking (switched off: `properties.active = 0`) [CODE].
* Guest self-cancellation (owner decision: only the admin cancels) [CODE `api/booking-cancel.php` returns 403].
* Bots / WhatsApp notifications ("right now no bot") [DOC].
* Colors of Kutch packages are enquiry-only [DOC `docs/08`].

## Core requirements
* GST-inclusive prices for the resort, per-night slabs (5% ≤ ₹7,500, else 18%) [CODE `tax_split`].
* Payment modes: full or 50% advance; pay-at-property off [CODE `config.php payment_modes`].
* Cancellation ladder: free 30+ days, 75% at 21–29, 100% under 21 [CODE `config.php cancellation`].
* Limits: 21 nights, 5 rooms online, up to 2 years ahead, ≤ 50 of one extra [CODE `config.php rules`].
* Admin always asks for the password (per tab, 10-minute idle); username/password sent as SHA-256 [CODE `admin/_auth.php`, `admin/login.php`].

## Success measures
[UNKNOWN] — none defined. Suggested [PLANNED]: share of bookings made direct, abandoned-checkout rate (pending bookings that lapse), time from search to payment.

## Scope of the first live version
Website + engine for Kutch Safari Resort only, Razorpay live, UPI QR, Stayflexi connected or inventory split by hand, sample mode and test payments off [DOC `docs/30` go-live checklist].
