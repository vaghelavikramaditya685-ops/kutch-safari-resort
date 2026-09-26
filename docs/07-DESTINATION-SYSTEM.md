# Destination Routing and Content System

## 1. Overview
`Destination.tsx` (about 280 lines) renders every local-attraction guide from one template, keyed by the URL slug.

Route: `<Route path="/destination/:slug" component={Destination} />`. The slug comes from `useRoute("/destination/:slug")` and is looked up in `DESTINATION_DATA`.

## 2. `DESTINATION_DATA`

```ts
{ title, subtitle, tag, img, alt, distance, travelTime, whatIsIt, details: string[] }
```

| Slug | Title | Distance / time |
|---|---|---|
| `dholavira` | Dholavira | 220 km · 4–4.5 h |
| `road-to-heaven` | Road to Heaven | 200 km · 3.5–4 h |
| `the-great-white-rann` | The Great White Rann | 85 km · 1.5–2 h |
| `mandvi-beach-palace` | Mandvi Beach & Palace | see file |
| `artisan-villages` | Artisan villages | see file |
| `kala-dungar` | Kala Dungar | see file |

### Linked from
`Experiences.tsx` links its six cards to these slugs. "Banni Villages" and "Bhuj & Bhujodi" both go to `artisan-villages`, and **nothing links to `dholavira`**. The Home Experiences cards don't link anywhere.

A `slugify()` helper is defined in the file but never used.

## 3. Layout
* **Own header**, not the shared Navbar: `logo-mark.png` (4.5 MB) plus a text logo, with a back link to `/#explore` (that anchor doesn't exist).
* Hero image with title overlay, then distance and travel time, "What is it", a details list, and a **Plan Your Visit** box.
* **Own dark footer** (`#2c2c2c`), not the shared Footer.
* `<Link><a>…</a></Link>` nesting: wouter 3's `Link` already renders `<a>`, so this produces invalid nested anchors.
* Unknown slug: shows "Destination not found" with a button to `/#explore`.

## 4. CTA
"Inquire on WhatsApp":
`https://wa.me/919925238599?text=Hello Kutch Safari Resort, I would like to plan a visit to <title> from the resort.`

Possible improvement: add a "Stay with us" button → `bookingUrl()`, and switch the page to the shared Navbar and Footer (which would also bring the "Already booked? Check status" link).

_Checked 26 Sep 2026: unchanged by the booking-engine work._
