# Decisions and Assumptions
> Purpose: why things are the way they are; what's still open | Mode: A | Confidence: High for decisions, Medium for assumptions | Related: [../docs/20-CHANGE-LOG-AND-DESIGN-DECISIONS.md](../docs/20-CHANGE-LOG-AND-DESIGN-DECISIONS.md), [../docs/29-LESSONS-LEARNED-AND-GOTCHAS.md](../docs/29-LESSONS-LEARNED-AND-GOTCHAS.md)

## Key decisions
| Decision | Reason | Source |
|---|---|---|
| Engine is a separate PHP app at `/book/` | cPanel-friendly, survives Vercel (the old JSON-file engine couldn't), keeps keys off the static host | [DOC docs/20] |
| One pricing function `price_rooms()` | the list and checkout used to disagree | [DOC docs/21] |
| Changes priced as a difference | owner: "added total added, removed total subtracted" | [DOC docs/22] |
| 50% balance labelled "before arrival" | matches the payment note guests see | [CODE config.php] |
| Only the admin cancels | owner decision | [CODE booking-cancel.php] |
| Availability by cottage only; guest side panel | owner/friend's request (26 Sep) | [DOC docs/23] |
| No browser pop-ups (on-page confirm) | owner | [DOC docs/24] |
| Admin credentials sent as SHA-256 | owner request (29 Sep); server still bcrypts | [CODE admin/login.php] |
| Folder split frontend/ backend/ (api/ stays at root) | clean structure; Vercel needs `/api` at root | [CODE restructure/REPORT.md] |
| `config.php` defaults to SQLite; `website_url` localhost in seed | owner/friend request; live config must override | [CODE] |
| No fake data unless the owner asks; demo data marked | owner rule | [DOC] |
| Tests only on a throwaway DB copy | same rule | [CODE bin/test-changes.php] |

## Assumptions
1. The live site will be served over HTTPS [INFERRED: required for payments].
2. One owner account is enough (no staff roles) [CODE: single role].
3. Guests are mostly Indian mobile users [INFERRED: +91 examples, ₹, UPI].
4. The engine will be hosted under the same domain at `/book/` [DOC docs/15 default].

## Open questions for the owner
1. Where will the engine be hosted (which PHP + MySQL host)? [UNKNOWN]
2. Is GST 18% right for the ₹8,000 Deluxe triple (inclusive price falls between slabs)? [UNKNOWN — accountant]
3. 50% plan: is the balance due 30 days before arrival (current wording) or at check-in? [UNKNOWN]
4. Stayflexi: connect it, or keep some cottages only for direct bookings? [UNKNOWN]
5. Diwali / Christmas–New Year resort prices (set as special prices)? [UNKNOWN]
6. Should new bookings email the owner (and which address)? [UNKNOWN]
7. Is the cancellation ladder (free 30+, 75% 21–29, 100% < 21) final? [UNKNOWN]
8. When should the 6 demo bookings be removed? [UNKNOWN]
9. Delete `scripts/legacy/` (36 old scripts)? [UNKNOWN]
10. What do gallery photos 8–26 show (for alt text)? [UNKNOWN]

## Risks
Overselling while Stayflexi is off; live key exposure (secret shared in chat); sample mode left on at launch; re-running full setup on live wipes prices; SQLite hides length errors that MySQL would raise (now guarded in code) [CODE/DOC].
