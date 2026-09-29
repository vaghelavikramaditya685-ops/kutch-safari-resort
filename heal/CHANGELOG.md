# Changelog

| Finding | File | Before | After |
|---|---|---|---|
| F1 | frontend/index.html | preload of KSR_VIDEO.mp4 with as="video" | line removed |
| F2 | frontend/src/pages/Destination.tsx | 3 x `<Link href><a className>` | `<Link href className>` |
| F2 | frontend/src/pages/RannUtsavPackage.tsx | 2 x `<Link href><a className>` | `<Link href className>` |
| F3 | backend/booking-engine/{index,manage,document}.php, admin/_auth.php | no icon | `<link rel="icon" type="image/svg+xml" href="assets/icon.svg">` (admin: ../assets/icon.svg) |
| F3 | backend/booking-engine/assets/icon.svg | none | new 32x32 SVG icon |
| F3 | frontend/public/favicon.ico, apple-touch-icon.png | none | new, from logo-mark.png |
| F3 | frontend/index.html | icon = 4.5 MB logo-mark.png (wrong MIME) | icon = /favicon.ico + apple-touch-icon |
