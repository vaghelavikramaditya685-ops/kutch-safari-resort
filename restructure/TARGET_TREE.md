# Target tree

Principle: separate the three apps by role, keep each app's own internal layout (they were already well organised), keep tool configs at the root where the tools expect them, and archive — not delete — the one-off scripts.

```
kutch-safari-resort/
├── frontend/                 React 19 + Vite marketing site          (was client/)
│   ├── index.html
│   ├── public/               static files served at / (assets/, robots.txt, sitemap.xml, vercel.json)
│   └── src/                  App.tsx, main.tsx, index.css, components/, contexts/, lib/, pages/
├── backend/
│   ├── booking-engine/       PHP 8 booking engine, served at /book/   (was booking-engine/)
│   │                         admin/, api/, assets/, bin/, lib/, data/ (SQLite, ignored), config.local.php (ignored)
│   └── server/               Express server for the built site        (was server/)
├── api/                      Vercel serverless function (contact.ts)  — stays at the root: Vercel only
│                             picks up functions from /api at the project root
├── docs/                     the 30 project docs
│   └── notes/                ideas.md, todo.md                        (were at the root)
├── scripts/
│   └── legacy/               34 one-off *.py edit scripts + old_rooms.tsx (were at the root; already applied,
│                             unused — kept for history, candidates for deletion)
├── restructure/              this cleanup's records
├── package.json, pnpm-lock.yaml, package-lock.json
├── vite.config.ts, tsconfig.json, tsconfig.node.json, components.json
├── .prettierrc, .prettierignore, .vercelignore, .gitignore
└── README.md
```

Not moved (regenerated or runtime, git-ignored): `node_modules/`, `dist/` (build output), `data/` (enquiries from the Express server), `.vercel/`.
`.claude/launch.json` stays (tool location) but its paths are updated.
