# Current Status (29 Sep 2026)
> Purpose: honest snapshot | Mode: A | Confidence: High | Related: [features.md](features.md), [roadmap.md](roadmap.md), [../docs/16-KNOWN-ISSUES-AND-BUGS.md](../docs/16-KNOWN-ISSUES-AND-BUGS.md)

## Works (evidence)
* Full guest booking, payment (test), status, receipts — 24/24 guest flow checks; browser click-through [CODE `heal/REPORT.md`].
* Admin: bookings, payments/refunds (incl. confirming pending on cash), change booking with difference pricing, cancel, special prices, availability, enquiries, CSV — 35/35 admin checks [CODE].
* Pricing and money logic — 17/17 `test-changes.php` [CODE].
* Zero console errors/warnings and zero PHP errors on a full pass [CODE `heal/REPORT.md`].
* Nonsense input: 107 values, 0 wrongly accepted [CODE `chaos/REPORT.md`].
* All website links and routes resolve; no sideways scroll at 375/768 px [CODE `button_audit/REPORT.md`].

## Half-built / off
* Razorpay: test keys only; live secret must be regenerated [DOC].
* UPI QR: no UPI id set [CODE].
* Stayflexi: code present, endpoints unverified, off [CODE `lib/channel.php`].
* Email: needs SMTP on the host [CODE].
* Sample mode + test payments **on** (turn off for launch) [CODE].
* White Rann Camp: switched off [CODE].

## Broken / missing
* Home contact form is a mock [CODE `Home.tsx`].
* ~269 MB unoptimised images [DOC `docs/11`].
* No screen to close cottages by hand [DOC `docs/23`].
* No owner alert for new bookings [DOC].
* Engine not deployed [DOC].

## Technical debt
No CI; no automated browser tests; old destination/camp pages use their own header/footer; `scripts/legacy/` to delete; `audit_log` grows with rate-limit rows [CODE].

## Estimated completeness [INFERRED from the above]
| Area | % |
|---|---|
| Website pages | 85% (contact form, images, content placeholders) |
| Booking engine (guest) | 90% (live payments, UPI id, email) |
| Admin panel | 90% (close rooms, notifications) |
| Integrations | 30% (Stayflexi, SMTP, live Razorpay) |
| Deployment | 10% (not deployed) |

## Data
Real DB: 7 bookings — owner's KSR-GJKQYG + 6 demo ("Demo …", @example.com) added at the owner's request; one demo (KSR-6JQ9JK) cancelled by the owner while exploring [CODE].
