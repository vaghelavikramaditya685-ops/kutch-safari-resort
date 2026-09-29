# Folder cleanup report (29 Sep 2026)

Snapshot before any move: branch `restructure/backup-2026-09-29`. To undo everything: `git checkout restructure/backup-2026-09-29`.

## 1. The new tree
| Folder | Purpose |
|---|---|
| `frontend/` | React 19 + Vite marketing site (was `client/`) |
| `backend/booking-engine/` | PHP booking engine, served at `/book/` (was `booking-engine/`) |
| `backend/server/` | Express server for the built site (was `server/`) |
| `api/` | Vercel serverless function. **Not moved:** Vercel only picks up functions from `/api` at the project root |
| `docs/` | 30 project docs; `docs/notes/` holds `ideas.md` and `todo.md` |
| `scripts/legacy/` | old one-off `*.py` edit scripts and `old_rooms.tsx` |
| `restructure/` | this report, the move map and the change log |
| root | `package.json`, lockfiles, `vite.config.ts`, `tsconfig*.json`, `components.json`, Prettier/Vercel/git ignore files, `README.md` (where the tools expect them) |

Full annotated tree: `TARGET_TREE.md`.

## 2. Move log
Every old → new path is in `CHANGELOG.md` (all moves done with `git mv`, so history is kept). The engine's git-ignored files (`config.local.php` with the Razorpay keys, `data/booking.sqlite`) moved with the folder and were checked afterwards.

## 3. References updated
* **Tooling:** `vite.config.ts` (root, `@` alias), `tsconfig.json` (include, paths), `components.json`, `package.json` (`dev:book`, `setup:book`, `build`), `.gitignore`, `.prettierignore`, `.vercelignore`, `.claude/launch.json`.
* **Code:** `backend/server/index.ts` now finds the project root whether it runs from the bundle (`dist/index.js`) or from source; a comment in `frontend/src/lib/booking.ts`.
* **Docs:** `README.md` and 21 docs. Change-log history (doc 20) and the note about the removed old React engine keep the paths they had at the time.
* **Not changed on purpose:** the scripts in `scripts/legacy/` still mention `client/…`. They are finished one-off edits, kept only as history.

## 4. Possibly unused files (NOT deleted)
`scripts/legacy/` — 36 files: the one-off `*.py` edit scripts and `old_rooms.tsx`. Nothing imports or runs them. They can be deleted once the owner confirms.

## 5. Verification (same checks as the baseline)
| Check | Before | After |
|---|---|---|
| TypeScript (`tsc --noEmit`) | pass | pass |
| Build (`npm run build`: Vite + esbuild) | pass | pass (`dist/public`, `dist/index.js`) |
| PHP lint (43 engine files) | 0 errors | 0 errors |
| Engine tests (`bin/test-changes.php`) | 17/17 | 17/17 |
| Express server from `dist/index.js` and from `backend/server/index.ts` | — | both serve `/` and `/stay` (200) |
| Dev setup (`dev:book` + Vite, via the preview config) | — | site, `/book/`, check status, admin, terms PDF, room search all 200; database still has the owner's booking |

Not tested: posting the Express contact form, because it would write a fake enquiry into the real `data/enquiries.json`. The path logic for it was checked in code.

## 6. Where to start
* Website code: `frontend/src` (pages in `frontend/src/pages`).
* Booking engine: `backend/booking-engine` (settings in `config.php`, logic in `lib/`).
* Run it: `pnpm dev:book` then `pnpm dev`, and open http://localhost:3000.
