# Changelog

## Moves (git mv, history kept)

| Old path | New path |
|---|---|
| client/ | frontend/ |
| booking-engine/ (with ignored config.local.php and data/booking.sqlite) | backend/booking-engine/ |
| server/ | backend/server/ |
| ideas.md, todo.md | docs/notes/ |
| analyze_edges.py | scripts/legacy/analyze_edges.py |
| create_pages.py | scripts/legacy/create_pages.py |
| fix_camp_img.py | scripts/legacy/fix_camp_img.py |
| fix_favicon.py | scripts/legacy/fix_favicon.py |
| fix_hero_text.py | scripts/legacy/fix_hero_text.py |
| fix_home_pdf.py | scripts/legacy/fix_home_pdf.py |
| fix_home_wrc.py | scripts/legacy/fix_home_wrc.py |
| fix_imports.py | scripts/legacy/fix_imports.py |
| fix_logo_bg.py | scripts/legacy/fix_logo_bg.py |
| fix_logo_bg2.py | scripts/legacy/fix_logo_bg2.py |
| fix_logo_blend.py | scripts/legacy/fix_logo_blend.py |
| fix_logo_darken.py | scripts/legacy/fix_logo_darken.py |
| fix_nav_pdf.py | scripts/legacy/fix_nav_pdf.py |
| fix_navbar.py | scripts/legacy/fix_navbar.py |
| fix_route.py | scripts/legacy/fix_route.py |
| fix_seo.py | scripts/legacy/fix_seo.py |
| fix_stay.py | scripts/legacy/fix_stay.py |
| fix_white.py | scripts/legacy/fix_white.py |
| fix_wrc_link.py | scripts/legacy/fix_wrc_link.py |
| gen_navbar_footer.py | scripts/legacy/gen_navbar_footer.py |
| get_colors.py | scripts/legacy/get_colors.py |
| get_logo_palette.py | scripts/legacy/get_logo_palette.py |
| get_logo_palette2.py | scripts/legacy/get_logo_palette2.py |
| old_rooms.tsx | scripts/legacy/old_rooms.tsx |
| remove_weddings.py | scripts/legacy/remove_weddings.py |
| restore_rooms.py | scripts/legacy/restore_rooms.py |
| revert_all.py | scripts/legacy/revert_all.py |
| revert_nav.py | scripts/legacy/revert_nav.py |
| rewrite_home.py | scripts/legacy/rewrite_home.py |
| rewrite_stay.py | scripts/legacy/rewrite_stay.py |
| update_all_pages.py | scripts/legacy/update_all_pages.py |
| update_app.py | scripts/legacy/update_app.py |
| update_gallery.py | scripts/legacy/update_gallery.py |
| update_navbar_logo.py | scripts/legacy/update_navbar_logo.py |
| update_server.py | scripts/legacy/update_server.py |
| update_theme.py | scripts/legacy/update_theme.py |

## Reference updates

| File | Change | Count |
|---|---|---|
| vite.config.ts | `/** Where `pnpm dev:book` serves the PHP booking engine (booking-engin` → `/** Where `pnpm dev:book` serves the PHP booking engine (backend/booki` | 1× |
| vite.config.ts | `path.resolve(import.meta.dirname, "client", "src")` → `path.resolve(import.meta.dirname, "frontend", "src")` | 1× |
| vite.config.ts | `root: path.resolve(import.meta.dirname, "client"),` → `root: path.resolve(import.meta.dirname, "frontend"),` | 1× |
| tsconfig.json | `"include": ["client/src/**/*", "server/**/*", "api/**/*"]` → `"include": ["frontend/src/**/*", "backend/server/**/*", "api/**/*"]` | 1× |
| tsconfig.json | `"@/*": ["./client/src/*"]` → `"@/*": ["./frontend/src/*"]` | 1× |
| components.json | `"css": "client/src/index.css"` → `"css": "frontend/src/index.css"` | 1× |
| package.json | `"dev:book": "php -S 127.0.0.1:8080 -t booking-engine"` → `"dev:book": "php -S 127.0.0.1:8080 -t backend/booking-engine"` | 1× |
| package.json | `"setup:book": "php booking-engine/bin/setup.php"` → `"setup:book": "php backend/booking-engine/bin/setup.php"` | 1× |
| package.json | `esbuild server/index.ts` → `esbuild backend/server/index.ts` | 1× |
| .gitignore | `client/public/__manus__/version.json` → `frontend/public/__manus__/version.json` | 1× |
| .prettierignore | `booking-engine/` → `backend/booking-engine/` | 1× |
| .vercelignore | `booking-engine/` → `backend/` | 1× |
| .claude/launch.json | `"-t", "booking-engine"]` → `"-t", "backend/booking-engine"]` | 1× |
| frontend/src/lib/booking.ts | ` * Links into the PHP booking engine (booking-engine/).` → ` * Links into the PHP booking engine (backend/booking-engine/).` | 1× |
| backend/server/index.ts | paths to dist/public and data/ now go through projectRoot (works from dist/index.js and from backend/server/index.ts) | 3 edits |
| README.md | 12 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/01-PROJECT-OVERVIEW.md | 5 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/02-ARCHITECTURE.md | 10 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/03-ROUTING-AND-NAVIGATION.md | 1 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/04-DESIGN-SYSTEM.md | 2 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/06-ACCOMMODATION-PAGES.md | 2 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/08-PACKAGES-AND-PRICING.md | 2 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/10-CONTENT-PAGES.md | 1 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/11-IMAGE-ASSET-INVENTORY.md | 5 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/12-SEO-AND-META.md | 3 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/13-FORMS-AND-INTERACTIONS.md | 1 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/14-STYLING-DEEP-DIVE.md | 2 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/15-DEPLOYMENT-AND-INFRASTRUCTURE.md | 12 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/16-KNOWN-ISSUES-AND-BUGS.md | 2 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/17-BUSINESS-CONTENT-REFERENCE.md | 1 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/21-PRICING-LOGIC-DEEP-DIVE.md | 1 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/24-ADMIN-PANEL-GUIDE.md | 1 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/25-GUEST-BOOKING-FLOW-INTERNALS.md | 2 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/26-PAYMENTS-RAZORPAY-UPI-TEST.md | 1 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/28-DATABASE-AND-DATA-RULES.md | 1 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| docs/30-TESTING-GO-LIVE-AND-HANDOVER.md | 6 line(s): booking-engine/ -> backend/booking-engine/, client/ -> frontend/, server/index.ts -> backend/server/index.ts |
| README.md, docs/02-ARCHITECTURE.md | directory trees redrawn by hand | 2 |
| docs/16-KNOWN-ISSUES-AND-BUGS.md | root clutter item marked resolved | 1 |
