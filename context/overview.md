# Overview
> Purpose: one-page summary and map of the 20 context files | Mode: A (existing code) | Confidence: High | Related: [current_status.md](current_status.md), [../docs/01-PROJECT-OVERVIEW.md](../docs/01-PROJECT-OVERVIEW.md)

**What it is.** The website and direct-booking system for **Kutch Safari Resort**, a family-run resort of 20 lake-view cottages near Bhuj, Gujarat [DOC]. It has two apps in one repository [CODE `README.md`]:
1. a **marketing website** (React 19 + Vite) with the resort's pages, destination guides and "Book Now" links [CODE `frontend/`], and
2. a **PHP booking engine** where guests search, pick cottages and extras, and pay 50% or in full, plus an **admin panel** for the owner to manage bookings, availability, special prices and enquiries [CODE `backend/booking-engine/`].

**Who it's for.** Guests booking a stay (domestic and international travellers heading to the White Rann) [DOC], and the owner/front desk (admin user `manvir`) [CODE `data/booking.sqlite` admin_users].

**Status (29 Sep 2026).** Works end to end on the owner's PC with test payments; not yet deployed; Razorpay on test keys, Stayflexi not connected, sample mode and test payments still on [CODE `config.php`, `config.local.php`]. See [current_status.md](current_status.md).

**Tech in one line.** React 19 / Vite 7 / Tailwind 4 / wouter (site) + PHP 8.3 / SQLite locally, MySQL on the host (engine), Razorpay + UPI QR, Stayflexi bridge (off) [CODE `package.json`, `backend/booking-engine/lib/`].

**Deeper detail:** the 30 files in [`../docs/`](../docs/) (01–20 site and project, 21–30 booking-engine logic, testing and go-live).

## The 20 context files
| # | File | What's in it |
|---|---|---|
| 1 | overview.md | this page |
| 2 | [prd.md](prd.md) | problem, goals, non-goals, scope, success measures |
| 3 | [user_stories.md](user_stories.md) | personas and stories with acceptance criteria |
| 4 | [features.md](features.md) | every feature, status, where it lives |
| 5 | [architecture.md](architecture.md) | the two apps, how they connect, data flow, diagram |
| 6 | [tech_stack.md](tech_stack.md) | languages, libraries, services and why |
| 7 | [folder_structure.md](folder_structure.md) | the tree after the 29 Sep cleanup, entry points |
| 8 | [data_models.md](data_models.md) | the 18 tables, fields, hidden couplings |
| 9 | [api_spec.md](api_spec.md) | every engine endpoint with requests and responses |
| 10 | [frontend_spec.md](frontend_spec.md) | website routes, components, the booking page's JS |
| 11 | [backend_spec.md](backend_spec.md) | engine modules, business rules, validation |
| 12 | [user_flows.md](user_flows.md) | guest and staff journeys, click by click |
| 13 | [design_system.md](design_system.md) | colours, fonts, components, responsive rules |
| 14 | [auth_and_security.md](auth_and_security.md) | admin sign-in (SHA-256), sessions, tokens, risks |
| 15 | [env_and_setup.md](env_and_setup.md) | install, settings, run commands, common errors |
| 16 | [testing_and_deployment.md](testing_and_deployment.md) | tests, how to run them, deploy steps |
| 17 | [current_status.md](current_status.md) | what works, what's missing, % per area |
| 18 | [roadmap.md](roadmap.md) | Now / Next / Later |
| 19 | [decisions_and_assumptions.md](decisions_and_assumptions.md) | why things are the way they are; open questions |
| 20 | [ai_instructions.md](ai_instructions.md) | rules for anyone (human or AI) changing the code |

## Reading order
* **New developer:** overview → architecture → folder_structure → env_and_setup → backend_spec → frontend_spec → data_models → testing_and_deployment → current_status.
* **AI agent about to change code:** ai_instructions (first, always) → current_status → the spec for the area (backend_spec / frontend_spec / data_models / api_spec) → testing_and_deployment → decisions_and_assumptions.
* **Non-technical stakeholder:** overview → prd → features → current_status → roadmap → decisions_and_assumptions (open questions).
