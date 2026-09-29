# Findings (before -> after)

Before = first run, after = same values re-sent after the fixes. Raw output of both runs is in EVIDENCE/.

## 2. Gaps found and fixed
| # | Field | Nonsense | What the app did | Severity | Fix |
|---|---|---|---|---|---|
| 1 | Extras quantity (booking + desk change) | 1,000,000 Innova | quoted **₹2.1 billion** | data corruption | refused above 50 (`rules.max_extra_quantity`), "please call us" |
| 2 | Extras quantity | −5, 0, "abc" | silently booked **1 car** | wrong data saved | refused: "Choose how many… (1 or more)" |
| 3 | Extra id | unknown / another property's | silently dropped | wrong data | refused: "no longer offered" |
| 4 | Guest name / city / note / arrival time | 5,000 / 1,000 / 200,000 / 3,000 chars | saved; **MySQL would crash** ("data too long") | crash on live | limits matching the columns, clear messages; page fields have `maxlength` |
| 5 | Phone (booking + enquiry) | "abc", "12", 60 digits | saved | wrong data | 7–15 digits (spaces, dashes, + allowed), clear example in the message |
| 6 | Arrival time | "25:99 PM" | saved | wrong data | must be "h:mm AM/PM" like the wheel, or blank |
| 7 | Dates (guest + desk) | year 3000, "2026-13-45" | year 3000 offered; bad day accepted by the pattern | wrong data | real calendar dates only; up to 2 years ahead (`rules.max_days_ahead`) |
| 8 | Enquiry form | bad email, −3/"lots" guests, "not-a-date", reversed dates, 100k message | all saved | wrong data | same checks as bookings |
| 9 | Payment method (desk) | "bitcoin" | saved | wrong data | only cash / card / bank transfer / upi |
| 10 | Staff notes, payment note, cancel reason, enquiry note | 50k–200k chars | saved; MySQL crash | crash on live | limits + messages; `maxlength` on the fields |
| 11 | Enquiry status | "hacked" | saved | wrong data | only new / contacted / converted / closed |
| 12 | Enquiry id | 99999 | "Enquiry updated." | confusing | "That enquiry was not found." |
| 13 | Admin messages | any refusal | shown in a green "success" box | confusing | refusals shown in red |
| 14 | Availability address | `?property=abc` | PHP warning on the page | crash-ish | falls back to the property on sale |
| 15 | Special prices first night | "abc" | "The last night is before the first night." | confusing | "Choose the first and the last night." |
| 16 | Request body | broken JSON | "Unknown property." | confusing | "We could not read that request…" (400) |
| 17 | `rooms` not a list | "lots" | refused but PHP warning | log noise | treated as nothing chosen |
| 18 | Guest details missing entirely | guest = text | PHP warnings | log noise | handled |
| 19 | CSV download | name "=HYPERLINK(…)" | would run as a formula in Excel | security | cells starting with = + − @ get a leading ' |

Also improved: a refused guest detail now keeps the guest on the details step with what they typed (before, they were sent back to step 1 and lost it), and the booking page checks phone and email itself before sending.

