# Roadmap
> Purpose: what's left, in order | Mode: A | Confidence: Medium (priorities inferred; owner to confirm) | Related: [current_status.md](current_status.md), [decisions_and_assumptions.md](decisions_and_assumptions.md)

Effort: S (hours), M (a day or two), L (several days). All items [PLANNED].

## Now (before real guests)
| Item | Effort | Depends on |
|---|---|---|
| Choose PHP + MySQL host; deploy engine at `/book/` | M | owner: hosting account |
| Regenerate live Razorpay secret; live keys + webhook in server config | S | owner: Razorpay dashboard |
| Turn off sample mode, test payments, debug; long admin password | S | — |
| Set `website_url` / `base_url` to the live site | S | deploy |
| SMTP for confirmation emails | S | owner: mail account |
| Decide Stayflexi: connect (verify endpoints) or split rooms by hand | M–L | owner + Stayflexi support |
| Remove demo bookings (names "Demo …") | S | owner says when |

## Next
| Item | Effort |
|---|---|
| Wire the Home contact form to `api/enquiry.php` | S |
| Owner email alert on new booking | S |
| UPI id for QR payments | S |
| Screen to close cottages for maintenance (rooms on sale) | M |
| Optimise images (WebP, sizes) | M |
| Confirm GST on the ₹8,000 Deluxe triple; 50% balance timing; festival prices | S (owner) |

## Later
| Item | Effort |
|---|---|
| Re-enable White Rann Camp with checked prices | M |
| Gallery lightbox; real alt text for 19 photos | S |
| Shared header/footer on destination and camp pages | S |
| CI with typecheck, build, PHP lint and `bin/test-changes.php` | M |
| Automated browser tests (Playwright) for the booking flow | L |

Recommended order: host → config switches → payments → Stayflexi decision → emails → contact form → the rest.
