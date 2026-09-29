# Self-healing report (29 Sep 2026)

Snapshot before any fix: branch `heal/backup-2026-09-29`.

## How it was run
* **Debug rig:** a throwaway copy of the booking engine and its database (never the real one), served with PHP `error_reporting=E_ALL`, startup errors shown, every error/warning written to a log file; the website on the Vite dev server (development build, React dev warnings on).
* **Each pass:** 54 scripted guest + admin actions over HTTP (book, pay (test), UPI, status, receipt, cancel, change booking, refunds, special prices, enquiries, CSV, sign-in/out, security tokens); a browser click-through of every website route (21 including 404s) and of the engine (search → room → extras → details → I've paid (test) → confirmation, status page, receipt and terms viewers, every admin screen, change-booking price check), watching the console and network; the PHP error log and server log.

## Results
| Loop | Errors | Warnings | Failed requests | Notes |
|---|---|---|---|---|
| 1 | 9 (1 kind) | 21 (1 kind) | favicon 404 on every engine page | F1–F3 found |
| 2 | 0 | 0 | favicon 404 still on engine pages | F1, F2 gone; F3 fix did not hold |
| 3 | 0 | 0 | 0 | clean |

Server side (PHP) was clean in every loop: 0 errors, 0 warnings, 0 notices. The only non-200 responses are the rejections the tests trigger on purpose (wrong mobile → 404, wrong receipt token → 404, guest cancel → 403, form without security token → 403).

## Every finding
| ID | Category | Where | Root cause | Fix | Found / fixed |
|---|---|---|---|---|---|
| F1 | Console warning | `frontend/index.html` | `<link rel="preload" as="video">` (browsers don't support `as="video"`) for `KSR_VIDEO.mp4`, a 12.8 MB video no page plays | Removed the preload line (also saves the wasted download) | loop 1 / loop 2 |
| F2 | Console error | `frontend/src/pages/Destination.tsx` (3), `RannUtsavPackage.tsx` (2) | `<Link><a>…</a></Link>`: wouter's `Link` already renders `<a>`, so this nested a link inside a link (invalid HTML, React warns) | Moved the classes onto `Link` and removed the inner `<a>` | loop 1 / loop 2 |
| F3 | Network 404 | every engine page | No site-root `/favicon.ico`, and no icon declared on engine pages. Data-URI icons did not stop the browser asking for `/favicon.ico` (tried twice) | Real icon file `backend/booking-engine/assets/icon.svg` linked from every engine page, **and** a real 9 KB `frontend/public/favicon.ico` (+ `apple-touch-icon.png`) made from the logo. In production the engine is under `/book/`, so `/favicon.ico` comes from the website. The website's tab icon was the 4.5 MB `logo-mark.png`; it now uses the 9 KB `favicon.ico` | loop 1 / loop 3 |

Recurring root cause: none; three separate small issues.

## NEEDS-HUMAN
None.

## After the loop
* Debug mode was only ever on the throwaway copy's command line; nothing debug-related was changed in the project.
* Regression: `tsc` pass, `npm run build` pass (ships `favicon.ico`), PHP lint 0 errors, `bin/test-changes.php` 17/17.
* Dev-only note: opening the engine directly on port 8080 (not through `localhost:3000/book/`) has no `/favicon.ico` at that root. That only affects direct access in development.
