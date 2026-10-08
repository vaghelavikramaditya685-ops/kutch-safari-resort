/**
 * Links into the PHP booking engine (backend/booking-engine/).
 *
 * The engine is a separate app. In dev, Vite proxies /book/ to `pnpm dev:book`.
 * In production it is served from /book/ on a PHP host; if it lives elsewhere
 * (e.g. the site stays on Vercel), set VITE_BOOKING_URL to its full URL.
 */
export const BOOKING_URL: string = import.meta.env.VITE_BOOKING_URL || "/book/";

/**
 * Online booking is switched on only when the engine is deployed with the site
 * (VITE_BOOKING_ENABLED=true; docs/10). Until then the website goes live on its
 * own (Vercel) and every Book Now is an enquiry instead: ReserveButton shows
 * "Enquire Now" → /enquire, and the engine's links (check status, admin) are hidden.
 */
export const BOOKING_ENABLED: boolean = import.meta.env.VITE_BOOKING_ENABLED === "true";

export const ENQUIRY_PATH = "/enquire";

/** The enquiry page, with the room the guest was looking at filled in. */
export function enquiryUrl(room?: string): string {
  return room ? `${ENQUIRY_PATH}?${new URLSearchParams({ room })}` : ENQUIRY_PATH;
}

export type BookingProperty = "kutch-safari-resort" | "white-rann-camp";

export interface BookingLink {
  property?: BookingProperty;
  checkIn?: string; // YYYY-MM-DD
  checkOut?: string;
  adults?: number;
  rooms?: number;
}

export function bookingUrl({ property = "kutch-safari-resort", checkIn, checkOut, adults, rooms }: BookingLink = {}): string {
  const params = new URLSearchParams({ property });
  if (checkIn) params.set("check_in", checkIn);
  if (checkOut) params.set("check_out", checkOut);
  if (adults) params.set("adults", String(adults));
  if (rooms) params.set("rooms", String(rooms));
  return `${BOOKING_URL}?${params}`;
}

/** "Already booked? Check status" — find a booking by code, see the receipt, call. */
export function statusUrl(): string {
  return `${BOOKING_URL}manage.php`;
}

/** The booking engine's staff panel. /admin on the website forwards here. */
export function adminUrl(): string {
  return `${BOOKING_URL}admin/`;
}
