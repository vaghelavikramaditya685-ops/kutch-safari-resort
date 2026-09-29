# Tech Stack
> Purpose: what the project is built with and why | Mode: A | Confidence: High | Related: [env_and_setup.md](env_and_setup.md), [decisions_and_assumptions.md](decisions_and_assumptions.md)

## Website (`frontend/`)
| Tech | Version | Why | Source |
|---|---|---|---|
| React | 19.2 | UI | [CODE `package.json`] |
| Vite | 7 | dev server, build, `/book/` proxy | [CODE `vite.config.ts`] |
| TypeScript | 5.6 | types (`pnpm check`) | [CODE] |
| Tailwind CSS | 4 (config-less, `@theme` in CSS) | styling | [CODE `frontend/src/index.css`] |
| wouter | 3 | tiny router for flat routes | [CODE] |
| lucide-react, sonner, Radix slot/tooltip, cva, clsx, tailwind-merge | — | icons, toasts, shadcn/ui bits | [CODE] |
| Express | 4 | optional server for the built site + `POST /api/contact` | [CODE `backend/server/index.ts`] |
| pnpm | 10 | package manager (npm also works) | [CODE `packageManager`] |

## Booking engine (`backend/booking-engine/`)
| Tech | Why | Source |
|---|---|---|
| PHP 8.3 (8.x) | runs on any cPanel host, no build step | [CODE], [DOC `docs/20`] |
| PDO: SQLite (local) / MySQL (live) | one schema for both | [CODE `lib/db.php`, `bin/setup.php`] |
| cURL | Razorpay, Stayflexi | [CODE] |
| Built-in PDF writer | receipts/terms without a library | [CODE `lib/pdf.php`] |
| PDF.js (cdnjs) | show PDFs in the tab, never download | [CODE `document.php`] |
| qrious (CDN) | draw the UPI QR | [CODE `assets/engine.js`] |
| Razorpay Checkout (CDN) | card/UPI/netbanking | [CODE] |
| Google Fonts: Marcellus, Montserrat | engine look | [CODE] |

## Services
| Service | Use | State |
|---|---|---|
| Razorpay | online payments, refunds | test keys on this PC; live keys exist (secret to regenerate) [DOC `docs/26`] |
| Stayflexi | channel manager (OTAs) | off; endpoints unverified [CODE `config.php`] |
| Vercel | website hosting (`kutchsafaribhuj.in`) | [DOC `docs/15`] |
| PHP host (cPanel) | engine + MySQL | [PLANNED] not deployed |
| SMTP | confirmation emails | [PLANNED] not set |
