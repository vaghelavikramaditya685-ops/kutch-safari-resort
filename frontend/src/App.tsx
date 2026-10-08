import { Toaster } from "@/components/ui/sonner";
import { TooltipProvider } from "@/components/ui/tooltip";
import NotFound from "@/pages/NotFound";
import { Redirect, Route, Switch, useLocation } from "wouter";
import { useEffect } from "react";
import ErrorBoundary from "./components/ErrorBoundary";
import { ThemeProvider } from "./contexts/ThemeContext";
import Dining from "./pages/Dining";
import GalleryPage from "./pages/GalleryPage";
import PlanYourVisit from "./pages/PlanYourVisit";
import Packages from "./pages/Packages";
import Home from "./pages/Home";
import Stay from "./pages/Stay";
import Destination from "./pages/Destination";
import RannUtsavPackage from "./pages/RannUtsavPackage";
import Experiences from "./pages/Experiences";
import AroundTheResort from "./pages/AroundTheResort";
import PackageDetail from "./pages/PackageDetail";
import { packageBySlug } from "./lib/packages";
import BookingRedirect from "./pages/BookingRedirect";
import Enquire from "./pages/Enquire";
import { BOOKING_ENABLED, ENQUIRY_PATH, adminUrl } from "./lib/booking";



const SITE = "Kutch Safari Resort";
const TITLES: Record<string, string> = {
  "/": "Kutch Safari Resort | Bhunga Cottages by the Lake, Bhuj",
  "/stay": `The Stay: Kutchi & Deluxe AC Cottages | ${SITE}`,
  "/experiences": `Experiences | ${SITE}`,
  "/around-the-resort": `Around the Resort: Places to Explore in Kutch | ${SITE}`,
  "/dining": `Dining at The Banni | ${SITE}`,
  "/gallery": `Gallery | ${SITE}`,
  "/plan-your-visit": `Plan Your Visit | ${SITE}`,
  "/packages": `Kutch Tour Packages | ${SITE}`,
  "/enquire": `Send an Enquiry | ${SITE}`,
  "/rann-utsav-package": `White Rann Camp & Rann Utsav | ${SITE}`,
  "/white-rann-camp": `White Rann Camp & Rann Utsav | ${SITE}`,
  "/white-rann-camp/tariff": `White Rann Camp Tariff 2026–27 | ${SITE}`,
  // Only while online booking is on; otherwise /book redirects to /enquire and /admin is not found.
  ...(BOOKING_ENABLED ? {
    "/booking": `Book your stay | ${SITE}`,
    "/book": `Book your stay | ${SITE}`,
    "/admin": `Reservations | ${SITE}`,
  } : {}),
};
const DESTINATIONS: Record<string, string> = {
  dholavira: "Dholavira", "road-to-heaven": "Road to Heaven", "the-great-white-rann": "The Great White Rann",
  "mandvi-beach-palace": "Mandvi Beach & Palace", "artisan-villages": "Artisan Villages", "kala-dungar": "Kala Dungar",
};

const SITE_URL = "https://kutchsafariresort.in";
// Addresses that show the same page as another: search engines are pointed at one.
const CANONICAL: Record<string, string> = {
  "/rann-utsav-package": "/white-rann-camp",
  "/white-rann-camp/tariff": "/white-rann-camp",
};
const NOT_CONTENT = ["/booking", "/book", "/admin"];   // redirects to the booking engine

/** Set or remove one <head> tag. */
function headTag(selector: string, create: () => HTMLElement, set: ((el: HTMLElement) => void) | null) {
  let el = document.head.querySelector<HTMLElement>(selector);
  if (!set) { el?.remove(); return; }
  if (!el) { el = create(); document.head.appendChild(el); }
  set(el);
}

/**
 * Every page gets its own browser-tab title (for search results and bookmarks),
 * and a canonical address. An address that is not a page answers with the site
 * (the host serves index.html for every path), so it is marked noindex: search
 * engines otherwise report it as a "soft 404" or list it as a real page.
 */
function usePageTitle() {
  const [location] = useLocation();
  useEffect(() => {
    const slug = location.startsWith("/destination/") ? location.slice("/destination/".length) : "";
    const pkg = location.startsWith("/packages/") ? packageBySlug(location.slice("/packages/".length)) : undefined;
    document.title = TITLES[location]
      ?? (pkg ? `${pkg.title} (${pkg.nights} Nights / ${pkg.days} Days) | ${SITE}`
      : DESTINATIONS[slug] ? `${DESTINATIONS[slug]} | ${SITE}`
      : location.startsWith("/book/") || location.startsWith("/admin/") ? SITE : `Page not found | ${SITE}`);
    const isPage = (TITLES[location] !== undefined && !NOT_CONTENT.includes(location)) || DESTINATIONS[slug] !== undefined || pkg !== undefined;
    headTag('link[rel="canonical"]', () => Object.assign(document.createElement("link"), { rel: "canonical" }),
      isPage ? el => { (el as HTMLLinkElement).href = SITE_URL + (CANONICAL[location] ?? location); } : null);
    headTag('meta[name="robots"]', () => Object.assign(document.createElement("meta"), { name: "robots" }),
      isPage ? null : el => { (el as HTMLMetaElement).content = "noindex"; });
  }, [location]);
}

function Router() {
  usePageTitle();
  return (
    <Switch>
      <Route path={"/"} component={Home} />
      <Route path={"/enquire"} component={Enquire} />
      {BOOKING_ENABLED ? (
        <>
          <Route path={"/booking"}>{() => <BookingRedirect />}</Route>
          <Route path={"/book"}>{() => <BookingRedirect />}</Route>
          <Route path={"/book/*"}>{() => <BookingRedirect />}</Route>
          {/* Short address for the staff panel: /admin → the booking engine's admin. */}
          <Route path={"/admin"}>{() => <BookingRedirect to={adminUrl()} label="Opening the admin panel…" />}</Route>
          <Route path={"/admin/*"}>{() => <BookingRedirect to={adminUrl()} label="Opening the admin panel…" />}</Route>
        </>
      ) : (
        <>
          {/* Online booking is off (website deployed on its own): old booking links become the enquiry page; /admin is not found. */}
          <Route path={"/booking"}>{() => <Redirect to={ENQUIRY_PATH} replace />}</Route>
          <Route path={"/book"}>{() => <Redirect to={ENQUIRY_PATH} replace />}</Route>
          <Route path={"/book/*"}>{() => <Redirect to={ENQUIRY_PATH} replace />}</Route>
        </>
      )}
      <Route path={"/stay"} component={Stay} />
      <Route path={"/experiences"} component={Experiences} />
      <Route path={"/around-the-resort"} component={AroundTheResort} />
      {/* The Our Journey page was removed; send old links home. */}
      <Route path={"/our-journey"}>{() => <Redirect to="/" replace />}</Route>
      <Route path={"/dining"} component={Dining} />
      <Route path={"/gallery"} component={GalleryPage} />
      <Route path={"/plan-your-visit"} component={PlanYourVisit} />
      <Route path={"/packages"} component={Packages} />
      <Route path={"/packages/:slug"} component={PackageDetail} />

      <Route path={"/destination/:slug"} component={Destination} />
      <Route path={"/rann-utsav-package"} component={RannUtsavPackage} />
      <Route path={"/white-rann-camp"} component={RannUtsavPackage} />
      <Route path={"/white-rann-camp/tariff"} component={RannUtsavPackage} />
      
      <Route path={"/404"} component={NotFound} />
      {/* Final fallback route */}
      <Route component={NotFound} />
    </Switch>
  );
}

// NOTE: About Theme
// - First choose a default theme according to your design style (dark or light bg), than change color palette in index.css
//   to keep consistent foreground/background color across components
// - If you want to make theme switchable, pass `switchable` ThemeProvider and use `useTheme` hook

function App() {
  return (
    <ErrorBoundary>
      <ThemeProvider
        defaultTheme="light"
        // switchable
      >
        <TooltipProvider>
          <Toaster />
          <Router />
        </TooltipProvider>
      </ThemeProvider>
    </ErrorBoundary>
  );
}

export default App;
