# Component Dependency Map

## 1. React site

```mermaid
graph TD
    main[main.tsx] --> App[App.tsx]
    App --> EB[ErrorBoundary]
    App --> TP[ThemeProvider]
    App --> TT[TooltipProvider]
    App --> Toaster[sonner Toaster]
    App --> Router[wouter Switch]

    Router --> Home & Stay & Experiences & OurJourney & Dining & Gallery[GalleryPage] & Plan[PlanYourVisit] & Packages
    Router --> Destination
    Router --> RUP[RannUtsavPackage]
    Router --> BR[BookingRedirect]
    Router --> NF[NotFound]

    Home & Stay & Experiences & OurJourney & Dining & Gallery & Plan & Packages & BR --> Navbar
    Home & Stay & Experiences & OurJourney & Dining & Gallery & Plan & Packages & BR --> Footer

    Navbar --> BL[lib/booking.ts bookingUrl]
    Home --> BL
    Stay --> BL
    BR --> BL
    NF --> UI[ui/button + ui/card]

    BL -. "<a href> full page load" .-> ENGINE[(booking-engine /book/)]
```

`Destination` and `RannUtsavPackage` import neither `Navbar` nor `Footer`. They have their own markup.

## 2. Per-file imports
| File | Imports |
|---|---|
| `App.tsx` | wouter, ErrorBoundary, ThemeContext, ui/sonner, ui/tooltip, all pages |
| `Navbar.tsx` | wouter `Link`/`useLocation`, lucide `Menu`/`X`, `bookingUrl` |
| `Footer.tsx` | wouter `Link`, lucide `Instagram` |
| `Home.tsx` | wouter, sonner `toast`, lucide (several unused), Navbar, Footer, `bookingUrl` |
| `Stay.tsx` | lucide amenity icons, Navbar, Footer, `bookingUrl` |
| `Experiences.tsx` | wouter `Link`, lucide `ArrowRight`, Navbar, Footer |
| `Destination.tsx` | wouter `Link`/`useRoute`, many lucide icons (most unused) |
| `RannUtsavPackage.tsx` | wouter `Link`, lucide icons |
| `BookingRedirect.tsx` | lucide `Phone`/`MessageCircle`, Navbar, Footer, `bookingUrl` |
| `NotFound.tsx` | ui/button, ui/card, lucide, wouter `useLocation` |
| OurJourney, Dining, Gallery, PlanYourVisit, Packages | Navbar, Footer |

## 3. Booking engine (PHP)
```
index.php / manage.php ──► assets/engine.js ──fetch──► api/*.php
                                                   │
api/_init.php (CORS, JSON, rate limit) ─► lib/db.php ─► config.php (+ config.local.php)
api/quote|availability ─► lib/inventory.php
api/book|booking-* ─────► lib/booking.php ─► lib/inventory.php, lib/channel.php, lib/mail.php
api/payment-*|webhook ──► lib/payment.php ─► lib/booking.php
admin/*.php ─► admin/_auth.php ─► lib/*
bin/*.php (CLI) ─► lib/*
```

## 4. Third-party
* **Site:** react 19, wouter 3, lucide-react, sonner, @radix-ui (slot, tooltip), cva, clsx, tailwind-merge, next-themes (installed; ThemeContext doesn't use it), express.
* **Engine:** PHP PDO (MySQL/SQLite), Razorpay Checkout (CDN, loaded only when enabled), qrious 4.0.2 (CDN, UPI QR), Google Fonts (Marcellus, Montserrat).
