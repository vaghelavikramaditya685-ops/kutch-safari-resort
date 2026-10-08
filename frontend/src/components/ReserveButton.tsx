import { Link } from "wouter";
import { BOOKING_ENABLED, bookingUrl, enquiryUrl } from "@/lib/booking";

/**
 * Every "Book Now" on the site. With online booking on, a plain <a> into the PHP
 * engine (a separate app, so a full page load). Without it, "Enquire Now" →
 * the enquiry page, with the room the guest was looking at filled in.
 */
export default function ReserveButton({
  className,
  room,
  bookLabel = "Book Now",
  enquireLabel = "Enquire Now",
  onClick,
}: {
  className: string;
  room?: string;
  bookLabel?: string;
  enquireLabel?: string;
  onClick?: () => void;
}) {
  if (BOOKING_ENABLED) {
    return (
      <a href={bookingUrl()} onClick={onClick} className={className}>
        {bookLabel}
      </a>
    );
  }
  return (
    <Link href={enquiryUrl(room)} onClick={onClick} className={className}>
      {enquireLabel}
    </Link>
  );
}
