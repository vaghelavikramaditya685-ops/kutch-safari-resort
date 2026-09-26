# Image and Asset Inventory

_Measured 25 Sep 2026._

## Summary
| Location | Size | Notes |
|---|---|---|
| `client/public/assets/` | **~269 MB** | Unoptimised originals, served as-is at `/assets/...` |
| `booking-engine/assets/img/` | 3.1 MB | Already WebP, used only by the booking engine |

Images are referenced by absolute path strings, not imports, so Vite never removes unused files. Before deleting anything, grep for the path in `client/src` and `client/index.html`.

## Largest files (`client/public`)
| Size | File | Used by |
|---|---|---|
| 71.1 MB | `images/new/guest-feedback-video-guest-feedback-mr-parekh.mov` | **Unused** |
| 16.7 MB | `images/new/restaurant-kutch-safari-ab-vision-11.jpg` | Dining, Gallery |
| 14.0 MB | `images/new/kutchi-ac-room-_-deluxe-ac-cottage-kutch-safari-ab-vision-17.jpg` | Stay |
| 12.8 MB | `images/new/KSR_VIDEO.mp4` | Only a `<link rel="preload">` in `index.html` (**nothing plays it**) |
| 11.5 MB | `images/new/kutch-ac-cottage-_dsc9435.jpg` | Home, Stay |
| 11.2 MB | `images/new/gallery/_DSC9412.JPG` | Gallery |
| 10.6 MB | `images/new/gallery/_DSC9408.JPG` | Gallery |
| 10.2 MB | `images/new/kutch-safari-resort-website-hero.mp4` | Home hero |
| 9.4 MB | `…deluxe-ac-cottage-interior.jpg` | Stay |
| 8.8 MB | `…deluxe-ac-cottage-interior-02.jpg` | Home, Stay |
| 6.6 MB | `LAKEVIEW KUTCH SAFARI1.jpeg` | `og:image` (the filename has spaces) |
| 6.5 MB | `images/new/kutch-destination-road_2.jpg` | Home, Experiences, Packages, Destination |
| 4.5 MB | `images/logo-mark.png` | **Favicon**, Destination and RannUtsavPackage headers |
| 5–6 MB each | `images/new/gallery/2018…`, `IMG_20190310…` | Gallery |
| 37 KB | `images/new/logo-main.jpg` | Navbar |

## Unused files (not referenced anywhere)
`campfire-night.png` (4.9 MB), `hero-kutch-safari.png` (4.5 MB), `lake-sunrise-reference.png` (5.6 MB), `sketch-camel.png` (4.1 MB), `sketch-wild-ass.png` (5.2 MB), `guest-feedback…mov` (71.1 MB), `new/guests-*.jpg` (4 files, ~13 MB, duplicates of gallery photos), `new/kutch-ac-cottage-kutchi-cottage-interior.jpg`. Together that is about **109 MB** that can be deleted or moved out of `public/`.

## Booking engine photos (`booking-engine/assets/img`)
* `ksr/`: 22 WebP files (cottage exterior and interior, bathroom, lake dusk, gazebo, restaurant, cuisine, gala dinner, road to heaven, Mandvi, etc.)
* `wrc/`: 20 WebP files (tents inside and out, washrooms, camp, full moon Rann, etc.)

These are the right format and size. They could also be reused on the main site (e.g. Dining). The `wrc/` photos are kept although White Rann Camp is switched off in the engine.

_Re-checked 26 Sep 2026: no images were added or removed by the booking-engine work. Receipts and terms are PDFs built on the fly (`lib/pdf.php`) with no images, so nothing is stored for them._

## Optimisation plan
1. Delete or move the unused files (about 109 MB).
2. Convert JPG and PNG to WebP/AVIF at max 1920 px (hero) and 800 px (grid).
3. Re-encode the hero video to 720p MP4 + WebM (target under 3 MB). Remove the unused `KSR_VIDEO.mp4` preload.
4. Make a 32×32/180×180 favicon and a small logo PNG to replace the 4.5 MB `logo-mark.png`.
5. Rename `LAKEVIEW KUTCH SAFARI1.jpeg` to use hyphens and a small size, then update `og:image`.
