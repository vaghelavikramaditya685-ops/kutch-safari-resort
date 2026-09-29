# Folder Structure
> Purpose: where everything lives, where new code goes | Mode: A | Confidence: High | Related: [../restructure/REPORT.md](../restructure/REPORT.md), [architecture.md](architecture.md)

Reorganised on 29 Sep 2026 (`client/` → `frontend/`, `booking-engine/` → `backend/booking-engine/`, `server/` → `backend/server/`) [CODE `restructure/CHANGELOG.md`].

```
kutch-safari-resort/
├── frontend/                  React website (Vite root)                       [CODE vite.config.ts]
│   ├── index.html             entry HTML (meta, fonts, favicon)
│   ├── public/                served at / : assets/ (images, video), favicon.ico, robots.txt, sitemap.xml, vercel.json
│   └── src/
│       ├── main.tsx           ENTRY: mounts <App/>
│       ├── App.tsx            routes + page titles
│       ├── components/        Navbar, Footer, ErrorBoundary, ui/ (shadcn)
│       ├── lib/booking.ts     bookingUrl / statusUrl / adminUrl — the only link to the engine
│       └── pages/             one file per route
├── backend/
│   ├── booking-engine/        PHP engine, served at /book/
│   │   ├── index.php          ENTRY (guest booking page)   manage.php (check status)
│   │   ├── document.php, receipt.php, terms.php   PDFs
│   │   ├── api/               JSON endpoints (_init.php shared)
│   │   ├── admin/             staff panel (_auth.php shared: session, head, confirm box)
│   │   ├── lib/               db, inventory (pricing/availability), booking, payment, channel, mail, pdf, documents
│   │   ├── bin/               CLI: setup, test-changes, check-system, sync/retry (cron), Stayflexi tools
│   │   ├── assets/            engine.js, engine.css, icon.svg, img/
│   │   ├── config.php         all settings;  config.local.php (git-ignored) = secrets + local overrides
│   │   ├── schema.sql, seed.sql
│   │   └── data/              booking.sqlite (git-ignored)
│   └── server/index.ts        Express server for the built site
├── api/contact.ts             Vercel function (must stay at the root)
├── docs/                      30 project docs (+ notes/)
├── context/                   these 20 context files
├── restructure/ heal/ button_audit/ chaos/   reports from the 29 Sep workflows
├── scripts/legacy/            old one-off *.py edit scripts (unused; do not run)
└── package.json, vite.config.ts, tsconfig.json, components.json, .gitignore, …
```

## Where to put new code
* New website page → `frontend/src/pages/X.tsx` + route in `App.tsx` + title in `TITLES` + `public/sitemap.xml` [CODE].
* New engine endpoint → `backend/booking-engine/api/x.php` starting with `require_once __DIR__ . '/_init.php';` [CODE].
* Business logic → `backend/booking-engine/lib/` (not in pages/endpoints) [CODE pattern].
* New admin screen → `admin/x.php` with `require_login()`, `check_csrf()`, `admin_head()` / `admin_foot()` [CODE].
* Git-ignored and never committed: `config.local.php`, `data/*.sqlite`, `/data/`, `dist/`, `node_modules/` [CODE `.gitignore`].
