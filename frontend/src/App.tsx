import { Toaster } from "@/components/ui/sonner";
import { TooltipProvider } from "@/components/ui/tooltip";
import NotFound from "@/pages/NotFound";
import { Route, Switch, useLocation } from "wouter";
import { useEffect } from "react";
import ErrorBoundary from "./components/ErrorBoundary";
import { ThemeProvider } from "./contexts/ThemeContext";
import OurJourney from "./pages/OurJourney";
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
import BookingRedirect from "./pages/BookingRedirect";
import { adminUrl } from "./lib/booking";



const SITE = "Kutch Safari Resort";
const TITLES: Record<string, string> = {
  "/": "Kutch Safari Resort | Bhunga Cottages by the Lake, Bhuj",
  "/stay": `The Stay: Kutchi & Deluxe AC Cottages | ${SITE}`,
  "/experiences": `Experiences | ${SITE}`,
  "/around-the-resort": `Around the Resort: Places to Explore in Kutch | ${SITE}`,
  "/our-journey": `Our Journey | ${SITE}`,
  "/dining": `Dining at The Banni | ${SITE}`,
  "/gallery": `Gallery | ${SITE}`,
  "/plan-your-visit": `Plan Your Visit | ${SITE}`,
  "/packages": `Colors of Kutch Packages | ${SITE}`,
  "/rann-utsav-package": `White Rann Camp & Rann Utsav | ${SITE}`,
  "/white-rann-camp": `White Rann Camp & Rann Utsav | ${SITE}`,
  "/white-rann-camp/tariff": `White Rann Camp Tariff 2026–27 | ${SITE}`,
  "/booking": `Book your stay | ${SITE}`,
  "/book": `Book your stay | ${SITE}`,
  "/admin": `Reservations | ${SITE}`,
};
const DESTINATIONS: Record<string, string> = {
  dholavira: "Dholavira", "road-to-heaven": "Road to Heaven", "the-great-white-rann": "The Great White Rann",
  "mandvi-beach-palace": "Mandvi Beach & Palace", "artisan-villages": "Artisan Villages", "kala-dungar": "Kala Dungar",
};

/** Every page gets its own browser-tab title (for search results and bookmarks). */
function usePageTitle() {
  const [location] = useLocation();
  useEffect(() => {
    const slug = location.startsWith("/destination/") ? location.slice("/destination/".length) : "";
    document.title = TITLES[location]
      ?? (DESTINATIONS[slug] ? `${DESTINATIONS[slug]} | ${SITE}`
      : location.startsWith("/book/") || location.startsWith("/admin/") ? SITE : `Page not found | ${SITE}`);
  }, [location]);
}

function Router() {
  usePageTitle();
  return (
    <Switch>
      <Route path={"/"} component={Home} />
      <Route path={"/booking"}>{() => <BookingRedirect />}</Route>
      <Route path={"/book"}>{() => <BookingRedirect />}</Route>
      <Route path={"/book/*"}>{() => <BookingRedirect />}</Route>
      {/* Short address for the staff panel: /admin → the booking engine's admin. */}
      <Route path={"/admin"}>{() => <BookingRedirect to={adminUrl()} label="Opening the admin panel…" />}</Route>
      <Route path={"/admin/*"}>{() => <BookingRedirect to={adminUrl()} label="Opening the admin panel…" />}</Route>
      <Route path={"/stay"} component={Stay} />
      <Route path={"/experiences"} component={Experiences} />
      <Route path={"/around-the-resort"} component={AroundTheResort} />
      <Route path={"/our-journey"} component={OurJourney} />
      <Route path={"/dining"} component={Dining} />
      <Route path={"/gallery"} component={GalleryPage} />
      <Route path={"/plan-your-visit"} component={PlanYourVisit} />
      <Route path={"/packages"} component={Packages} />

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
