# Shared Components Reference

## 1. Layout

### 1.1 `components/Navbar.tsx`
* Top info bar (`hidden md:block`): phone, email, address, Instagram.
* Sticky header (`sticky top-0 z-50`, `bg-[#f8f5e2]`): `logo-main.jpg` with `mix-blend-darken`.
* Desktop nav from `NAV_LINKS` plus a "White Rann Camp →" link. The active link turns terracotta.
* `[WRC LOGO]` placeholder button → `whiteranncamp.travstack.com`.
* **Book Now** → `bookingUrl()` (plain `<a>`, loads the PHP engine).
* Mobile: `Menu`/`X` toggle and a fixed overlay at `top-[116px]`.

### 1.2 `components/Footer.tsx`
Four columns (Brand, Explore, Plan, Reservations). See `03-ROUTING-AND-NAVIGATION.md`. It contains one broken link (`/white-rann-camp/tariff`).

Used by: Home, Stay, Experiences, OurJourney, Dining, Gallery, PlanYourVisit, Packages, BookingRedirect. **Not** used by Destination or RannUtsavPackage, which have their own header and footer.

---

## 2. Infrastructure

### 2.1 `components/ErrorBoundary.tsx`
Class component (`getDerivedStateFromError`). The fallback shows "An unexpected error occurred.", **the full stack trace**, and a Reload button. Hiding the stack in production would be better.

### 2.2 `contexts/ThemeContext.tsx`
`light` or `dark`, with optional `switchable` (persisted in `localStorage`). `App.tsx` uses `defaultTheme="light"` and does not switch.

### 2.3 `lib/utils.ts`
`cn()` = `clsx` + `tailwind-merge`.

### 2.4 `lib/booking.ts` (new)
The single place that knows where the booking engine lives.
* `BOOKING_URL`: `import.meta.env.VITE_BOOKING_URL || "/book/"`
* `bookingUrl({ property, checkIn, checkOut, adults, rooms })` builds `…?property=kutch-safari-resort&check_in=…`
* `statusUrl()` → the engine's `manage.php` ("Already booked? Check status"), used in the Navbar top bar and mobile menu, the Home hero and the Footer.
* `adminUrl()` → the engine's `admin/`, used by the `/admin` redirect.
* `property` is `"kutch-safari-resort"` (default) or `"white-rann-camp"` (switched off in the engine for now).

Always use it for booking links, with a plain `<a>` and never wouter's `<Link>`.

### 2.5 `pages/BookingRedirect.tsx` (new)
Handles `/booking`, `/book`, `/book/*`, `/admin` and `/admin/*`. It takes `to` and `label` props (the admin routes pass `adminUrl()`), and does `window.location.replace(…)`. The routes use children render functions, because passing props through wouter's `component` gave a TypeScript error. If the target is the current path (the SPA is answering `/book/` because the engine isn't deployed there), it shows phone and WhatsApp buttons instead.

---

## 3. shadcn/ui (`components/ui/`)
Only four remain:
* `button.tsx`, `card.tsx`: used only by `NotFound.tsx`.
* `tooltip.tsx`: `TooltipProvider` wraps the app; no tooltips are rendered.
* `sonner.tsx`: `<Toaster />` in `App.tsx`; `toast.success` is used by the Home mock form.

Pages mostly use raw Tailwind classes, not these components.
