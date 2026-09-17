# Kutch Safari Resort

Marketing site for Kutch Safari Resort, Bhuj. React 19 + Vite 7 + Tailwind 4,
client-side routing via wouter, with a small Express server for static hosting
and a contact endpoint.

## Setup

```bash
pnpm install
pnpm dev          # http://localhost:3000
```

## Scripts

| Script | What it does |
| --- | --- |
| `pnpm dev` | Vite dev server |
| `pnpm build` | Builds client to `dist/public` and server bundle to `dist/index.js` |
| `pnpm start` | Serves the production build |
| `pnpm check` | TypeScript typecheck (`tsc --noEmit`) |
| `pnpm format` | Prettier |

## Layout

```
client/
  index.html
  public/assets/      static images and video served at /assets/...
  src/
    pages/            Home, Rooms, Destination, RannUtsavPackage, NotFound
    components/ui/    shadcn components in use (button, card, sonner, tooltip)
    contexts/         ThemeContext
api/contact.ts        Vercel serverless contact handler
server/index.ts       Express static server + POST /api/contact
```

Routes: `/`, `/rooms`, `/destination/:slug`, `/rann-utsav-package`.

## Notes

- Images are referenced by absolute path (`/assets/...`) from `client/public`,
  not imported, so unused files are not tree-shaken — check references before
  adding or removing media.
- `client/public/assets` is ~215 MB of unoptimized originals; compressing and
  converting to WebP/AVIF is the single biggest available win for page weight.
