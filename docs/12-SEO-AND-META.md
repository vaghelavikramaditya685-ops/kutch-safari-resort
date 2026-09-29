# SEO and Meta Configuration

## `frontend/index.html`
```html
<html lang="en">
<link rel="icon" type="image/jpeg" href="/assets/images/logo-mark.png" />   <!-- 4.5 MB PNG, wrong MIME type -->
<link rel="preload" as="video" href="/assets/images/new/KSR_VIDEO.mp4" type="video/mp4" />  <!-- 12.8 MB, not used by any page -->
<meta name="viewport" content="width=device-width, initial-scale=1.0" />  <!-- pinch zoom allowed since 29 Sep 2026 -->
<title>Kutch Safari Resort | Bhunga Cottages by the Lake, Bhuj</title>
<meta name="description" content="Kutch Safari Resort — traditional Kutchi bhunga cottages on a hilltop above Rudramata Dam lake, near Bhuj. …" />
```

### Open Graph / Twitter
* `og:type` website, `og:url` `https://kutchsafaribhuj.in/`
* `og:title` / `og:description`: same as the title and description
* `og:image`: `https://kutchsafaribhuj.in/assets/LAKEVIEW%20KUTCH%20SAFARI1.jpeg` (6.6 MB, spaces in filename; many scrapers reject images over 5–8 MB)
* `twitter:card` summary_large_image, plus title and description. There is no `twitter:image`.

### Fonts
Google Fonts preconnect, then Cormorant Garamond (500/600/700 + italics) and Jost (400/500/600 + italic).

## `frontend/public/robots.txt`
```
User-agent: *
Allow: /
Sitemap: https://kutchsafaribhuj.in/sitemap.xml
```
Add `Disallow: /book/` so the booking engine isn't crawled. The engine also sends `<meta name="robots" content="noindex">`. The admin panel, check-status page and PDFs are for guests and staff only; none of them should be in the sitemap. The website's `/admin` route only redirects and has no content.

## `frontend/public/sitemap.xml`
Lists only `/`, `/stay`, `/our-journey`, `/dining`. **Missing:** `/experiences`, `/gallery`, `/plan-your-visit`, `/packages`, `/white-rann-camp`, and the six `/destination/*` pages.

## Gaps
1. ~~Same title on every route~~ Fixed 29 Sep 2026: `usePageTitle()` in `App.tsx` gives every page (and each destination) its own title. Descriptions are still shared (would need pre-rendering).
2. No `<link rel="canonical">`.
3. No JSON-LD. A `Resort`/`LodgingBusiness` schema with address, phone, geo and `priceRange` would help.
4. Destination pages are the best SEO content but aren't in the sitemap.
5. Image alt text is weak (e.g. Gallery uses "Gallery" for all 26 images).
6. ~~4.5 MB favicon and unused video preload~~ Fixed 29 Sep 2026: 9 KB `favicon.ico` + `apple-touch-icon.png`; the preload was removed. The sitemap now lists all 15 public pages and `robots.txt` disallows `/book/` and `/admin`.
