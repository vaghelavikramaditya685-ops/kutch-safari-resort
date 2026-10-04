# CLAUDE.md

Map of every Markdown file in this repository and what it contains. Updated 30 Sep 2026.

The project: website (`frontend/`, React 19 + Vite) and booking engine + admin panel (`backend/booking-engine/`, PHP 8, served at `/book/`) for Kutch Safari Resort, Bhuj.

## Rules to follow first (full list in `README.md` → "Instructions for AI Agents and Developers")
* Do not push to GitHub unless the owner asks in that conversation.
* No invented data in the real database (`backend/booking-engine/data/booking.sqlite`); test on a throwaway copy. The 6 demo bookings are named "Demo …" / @example.com.
* Never run full `bin/setup.php` on real data (only `--admin`).
* Secrets only in `backend/booking-engine/config.local.php` (git-ignored); never commit, print or copy them.
* Keep the colours; the owner approves visual changes.
* When docs change, keep this file and the README tables in sync. All Markdown lives in `README.md`, `BUGS.md` and `docs/01`–`09` (plus this file).

## Every .md file

| File | What it contains |
|---|---|
| `CLAUDE.md` | This map. |
| [`BUGS.md`](BUGS.md) | Bugs to fix, with file:line, proof and suggested fix. Covers the 30 Sep 2026 test with the demo data (B1–B17: money, refunds, heavy traffic, admin; the one-admin-at-a-time requirement is B10) and the 4 Oct 2026 debug-mode and syntax check (no syntax errors; B18–B19 found; F1 list-instead-of-text input and F2 arrival-wheel JS error fixed). B1–B19 are not fixed yet. |
| [`README.md`](README.md) | Start here. What the project is, the owner's hard rules for anyone changing code, AI instructions and glossary, project overview and current state, quick start (install/run), repository layout, and a table mapping every old doc to its new place. |
| [`docs/01-ARCHITECTURE-AND-SETUP.md`](docs/01-ARCHITECTURE-AND-SETUP.md) | How the React site and the PHP booking engine fit together, tech stack and why, the full folder tree with entry points, environment/settings/run commands and common setup errors, component and PHP dependency maps. |
| [`docs/02-WEBSITE.md`](docs/02-WEBSITE.md) | The React site (`frontend/`): every route, the menu (full from 1,320 px), each page (Home, Stay, Experiences from the brochure, Around the Resort, destination guides, content pages), shared components, SEO/titles/sitemap, forms and interactions, visitor journeys. |
| [`docs/03-DESIGN-AND-ASSETS.md`](docs/03-DESIGN-AND-ASSETS.md) | Colours, fonts, Tailwind setup, custom CSS classes, navbar width rules, booking-engine CSS details, and the image/video inventory (sizes, unused files, brochure photos, optimisation plan). |
| [`docs/04-BUSINESS-CONTENT-AND-PRICES.md`](docs/04-BUSINESS-CONTENT-AND-PRICES.md) | Resort facts, contacts, owner directives, the 2026–27 brochure and where it disagrees with the site, published prices, GST, packages (Colors of Kutch, White Rann Camp), product goals and user stories. |
| [`docs/05-BOOKING-ENGINE.md`](docs/05-BOOKING-ENGINE.md) | The PHP engine (`backend/booking-engine/`): modules and business rules, every API endpoint, guest/staff flows, the original engine README and handover (with current-state notes), pricing logic, changing a booking and money (50%/full), availability, guest booking flow internals, payments (Razorpay/UPI/test), Stayflexi. |
| [`docs/06-ADMIN-AND-SECURITY.md`](docs/06-ADMIN-AND-SECURITY.md) | Admin panel screen by screen, and security: SHA-256 sign-in, sessions, CSRF, tokens, input checks, risks. |
| [`docs/07-DATABASE.md`](docs/07-DATABASE.md) | The database tables and fields, hidden couplings, sample records, and the rules for real vs test/demo data (never re-run full setup on real data). |
| [`docs/08-STATUS-ISSUES-AND-ROADMAP.md`](docs/08-STATUS-ISSUES-AND-ROADMAP.md) | Current status and % complete, every feature with status, all known issues, decisions and assumptions, open questions for the owner, roadmap, todo and ideas notes. |
| [`docs/09-HISTORY-TESTING-AND-GO-LIVE.md`](docs/09-HISTORY-TESTING-AND-GO-LIVE.md) | Change log (newest first) and design decisions, lessons learned and gotchas, how to test, go-live checklist and handover, deployment/infrastructure, and the 29 Sep 2026 reports (folder cleanup, self-healing, button audit, chaos test). |

## Sections inside each file

Each section is an older doc merged in on 30 Sep 2026; its heading says "(was `old/path.md`)". Text that says "doc 22" or "docs/30" means the section marked `docs/22` / `docs/30`.

### `README.md`
* [Overview](README.md#context-overview) — was `context/overview.md`
* [Instructions for AI Agents and Developers](README.md#context-ai-instructions) — was `context/ai_instructions.md`
* [Project Overview: Kutch Safari Resort Website](README.md#docs-01) — was `docs/01-PROJECT-OVERVIEW.md`
* [Quick start and repository layout](README.md#quick-start) — was `README.md`

### `docs/01-ARCHITECTURE-AND-SETUP.md`
* [Architecture](docs/01-ARCHITECTURE-AND-SETUP.md#context-architecture) — was `context/architecture.md`
* [Tech Stack](docs/01-ARCHITECTURE-AND-SETUP.md#context-tech-stack) — was `context/tech_stack.md`
* [Folder Structure](docs/01-ARCHITECTURE-AND-SETUP.md#context-folder-structure) — was `context/folder_structure.md`
* [Environment and Setup](docs/01-ARCHITECTURE-AND-SETUP.md#context-env-and-setup) — was `context/env_and_setup.md`
* [Technical Architecture](docs/01-ARCHITECTURE-AND-SETUP.md#docs-02) — was `docs/02-ARCHITECTURE.md`
* [Component Dependency Map](docs/01-ARCHITECTURE-AND-SETUP.md#docs-18) — was `docs/18-COMPONENT-DEPENDENCY-MAP.md`

### `docs/02-WEBSITE.md`
* [Frontend Spec](docs/02-WEBSITE.md#context-frontend-spec) — was `context/frontend_spec.md`
* [Routing and Navigation](docs/02-WEBSITE.md#docs-03) — was `docs/03-ROUTING-AND-NAVIGATION.md`
* [Home Page (`frontend/src/pages/Home.tsx`)](docs/02-WEBSITE.md#docs-05) — was `docs/05-HOME-PAGE.md`
* [Accommodation Pages](docs/02-WEBSITE.md#docs-06) — was `docs/06-ACCOMMODATION-PAGES.md`
* [Destination Routing and Content System](docs/02-WEBSITE.md#docs-07) — was `docs/07-DESTINATION-SYSTEM.md`
* [Content Pages](docs/02-WEBSITE.md#docs-10) — was `docs/10-CONTENT-PAGES.md`
* [Shared Components Reference](docs/02-WEBSITE.md#docs-09) — was `docs/09-SHARED-COMPONENTS.md`
* [SEO and Meta Configuration](docs/02-WEBSITE.md#docs-12) — was `docs/12-SEO-AND-META.md`
* [Forms and Interactions](docs/02-WEBSITE.md#docs-13) — was `docs/13-FORMS-AND-INTERACTIONS.md`
* [User Flow and UX](docs/02-WEBSITE.md#docs-19) — was `docs/19-USER-FLOW-AND-UX.md`

### `docs/03-DESIGN-AND-ASSETS.md`
* [Design System](docs/03-DESIGN-AND-ASSETS.md#context-design-system) — was `context/design_system.md`
* [Design System and Theming](docs/03-DESIGN-AND-ASSETS.md#docs-04) — was `docs/04-DESIGN-SYSTEM.md`
* [Styling Deep Dive](docs/03-DESIGN-AND-ASSETS.md#docs-14) — was `docs/14-STYLING-DEEP-DIVE.md`
* [Image and Asset Inventory](docs/03-DESIGN-AND-ASSETS.md#docs-11) — was `docs/11-IMAGE-ASSET-INVENTORY.md`

### `docs/04-BUSINESS-CONTENT-AND-PRICES.md`
* [Product Requirements](docs/04-BUSINESS-CONTENT-AND-PRICES.md#context-prd) — was `context/prd.md`
* [User Stories](docs/04-BUSINESS-CONTENT-AND-PRICES.md#context-user-stories) — was `context/user_stories.md`
* [Business Content Reference](docs/04-BUSINESS-CONTENT-AND-PRICES.md#docs-17) — was `docs/17-BUSINESS-CONTENT-REFERENCE.md`
* [Packages and Pricing](docs/04-BUSINESS-CONTENT-AND-PRICES.md#docs-08) — was `docs/08-PACKAGES-AND-PRICING.md`

### `docs/05-BOOKING-ENGINE.md`
* [Backend Spec](docs/05-BOOKING-ENGINE.md#context-backend-spec) — was `context/backend_spec.md`
* [API Spec](docs/05-BOOKING-ENGINE.md#context-api-spec) — was `context/api_spec.md`
* [User Flows](docs/05-BOOKING-ENGINE.md#context-user-flows) — was `context/user_flows.md`
* [Booking engine — Kutch Safari Resort & White Rann Camp](docs/05-BOOKING-ENGINE.md#engine-readme) — was `backend/booking-engine/README.md`
* [Kutch Safari Resort & White Rann Camp — project handover](docs/05-BOOKING-ENGINE.md#engine-project-context) — was `backend/booking-engine/PROJECT-CONTEXT.md`
* [Pricing Logic Deep Dive (booking engine)](docs/05-BOOKING-ENGINE.md#docs-21) — was `docs/21-PRICING-LOGIC-DEEP-DIVE.md`
* [Booking Changes and Money Logic](docs/05-BOOKING-ENGINE.md#docs-22) — was `docs/22-BOOKING-CHANGES-AND-MONEY.md`
* [Availability and Inventory Logic](docs/05-BOOKING-ENGINE.md#docs-23) — was `docs/23-AVAILABILITY-AND-INVENTORY-LOGIC.md`
* [Guest Booking Flow — Internals](docs/05-BOOKING-ENGINE.md#docs-25) — was `docs/25-GUEST-BOOKING-FLOW-INTERNALS.md`
* [Payments: Razorpay, UPI QR, Test Payments, Refunds](docs/05-BOOKING-ENGINE.md#docs-26) — was `docs/26-PAYMENTS-RAZORPAY-UPI-TEST.md`
* [Stayflexi (Channel Manager) Sync](docs/05-BOOKING-ENGINE.md#docs-27) — was `docs/27-STAYFLEXI-CHANNEL-SYNC.md`

### `docs/06-ADMIN-AND-SECURITY.md`
* [Auth and Security](docs/06-ADMIN-AND-SECURITY.md#context-auth-and-security) — was `context/auth_and_security.md`
* [Admin Panel Guide](docs/06-ADMIN-AND-SECURITY.md#docs-24) — was `docs/24-ADMIN-PANEL-GUIDE.md`

### `docs/07-DATABASE.md`
* [Data Models](docs/07-DATABASE.md#context-data-models) — was `context/data_models.md`
* [Database and Data Rules](docs/07-DATABASE.md#docs-28) — was `docs/28-DATABASE-AND-DATA-RULES.md`

### `docs/08-STATUS-ISSUES-AND-ROADMAP.md`
* [Current Status (30 Sep 2026)](docs/08-STATUS-ISSUES-AND-ROADMAP.md#context-current-status) — was `context/current_status.md`
* [Features](docs/08-STATUS-ISSUES-AND-ROADMAP.md#context-features) — was `context/features.md`
* [Known Issues and Bugs](docs/08-STATUS-ISSUES-AND-ROADMAP.md#docs-16) — was `docs/16-KNOWN-ISSUES-AND-BUGS.md`
* [Decisions and Assumptions](docs/08-STATUS-ISSUES-AND-ROADMAP.md#context-decisions-and-assumptions) — was `context/decisions_and_assumptions.md`
* [Roadmap](docs/08-STATUS-ISSUES-AND-ROADMAP.md#context-roadmap) — was `context/roadmap.md`
* [RannRiders Redesign — Todo](docs/08-STATUS-ISSUES-AND-ROADMAP.md#docs-notes-todo) — was `docs/notes/todo.md`
* [Kutch Safari Resort — Design Brief (v2)](docs/08-STATUS-ISSUES-AND-ROADMAP.md#docs-notes-ideas) — was `docs/notes/ideas.md`

### `docs/09-HISTORY-TESTING-AND-GO-LIVE.md`
* [Change Log and Design Decisions](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#docs-20) — was `docs/20-CHANGE-LOG-AND-DESIGN-DECISIONS.md`
* [Lessons Learned and Gotchas](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#docs-29) — was `docs/29-LESSONS-LEARNED-AND-GOTCHAS.md`
* [Testing and Deployment](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#context-testing-and-deployment) — was `context/testing_and_deployment.md`
* [Testing, Go-Live Checklist and Handover](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#docs-30) — was `docs/30-TESTING-GO-LIVE-AND-HANDOVER.md`
* [Deployment and Infrastructure](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#docs-15) — was `docs/15-DEPLOYMENT-AND-INFRASTRUCTURE.md`
* [Folder cleanup report (29 Sep 2026)](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#restructure-report) — was `restructure/REPORT.md`
* [Changelog](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#restructure-changelog) — was `restructure/CHANGELOG.md`
* [Move map](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#restructure-move-map) — was `restructure/MOVE_MAP.md`
* [Current tree (26 Sep 2026, before the cleanup)](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#restructure-current-tree) — was `restructure/CURRENT_TREE.md`
* [Target tree](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#restructure-target-tree) — was `restructure/TARGET_TREE.md`
* [Self-healing report (29 Sep 2026)](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#heal-report) — was `heal/REPORT.md`
* [Findings](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#heal-findings) — was `heal/FINDINGS.md`
* [Changelog](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#heal-changelog) — was `heal/CHANGELOG.md`
* [Run log](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#heal-run-log) — was `heal/RUN_LOG.md`
* [State](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#heal-state) — was `heal/STATE.md`
* [Button, link and page audit (29 Sep 2026)](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#button-audit-report) — was `button_audit/REPORT.md`
* [Chaos-monkey report (29 Sep 2026): nonsense input](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#chaos-report) — was `chaos/REPORT.md`
* [Findings (before -> after)](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#chaos-findings) — was `chaos/FINDINGS.md`
* [Changelog](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#chaos-changelog) — was `chaos/CHANGELOG.md`
* [Input map](docs/09-HISTORY-TESTING-AND-GO-LIVE.md#chaos-target-map) — was `chaos/TARGET_MAP.md`

## Where to look for a task
* Prices, changing a booking, money due/refund, availability, payments, Stayflexi → `docs/05-BOOKING-ENGINE.md`
* Admin screens, sign-in → `docs/06-ADMIN-AND-SECURITY.md`
* A website page, menu, links, SEO → `docs/02-WEBSITE.md`
* Colours, CSS, images → `docs/03-DESIGN-AND-ASSETS.md`
* Resort facts, brochure, published tariffs → `docs/04-BUSINESS-CONTENT-AND-PRICES.md`
* Tables and data rules → `docs/07-DATABASE.md`
* Bugs to fix next → `BUGS.md`; everything open → `docs/08-STATUS-ISSUES-AND-ROADMAP.md`
* What changed, tests, go-live → `docs/09-HISTORY-TESTING-AND-GO-LIVE.md`
* Install/run, folder layout → `docs/01-ARCHITECTURE-AND-SETUP.md` and `README.md`
