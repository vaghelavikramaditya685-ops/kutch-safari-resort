import { useEffect } from "react";
import { Route, Switch, useLocation } from "wouter";
import Home from "@/pages/Home";
import Tents from "@/pages/Tents";
import Tours from "@/pages/Tours";
import Gallery from "@/pages/Gallery";
import PlanYourVisit from "@/pages/PlanYourVisit";
import Enquire from "@/pages/Enquire";
import NotFound from "@/pages/NotFound";

const SITE = "White Rann Camp";
const DEFAULT_DESCRIPTION =
  "White Rann Camp, Dhordo: twenty Swiss tents three minutes from Rann Utsav and the entry to the White Rann of Kutch. Dinner, breakfast and hi-tea included. Open 1 December 2026 to 31 January 2027.";

/** Browser-tab title and search description for each page. */
const PAGES: Record<string, { title: string; description: string }> = {
  "/": { title: `${SITE} | Swiss Tents by Rann Utsav, Dhordo`, description: DEFAULT_DESCRIPTION },
  "/tents": {
    title: `Tents & Tariff 2026–27 | ${SITE}`,
    description: "Six Deluxe Air-Cool and fourteen Non-AC Swiss tents at Dhordo, each with an attached bathroom. 2026–27 tariff, peak dates and what every stay includes.",
  },
  "/tours": {
    title: `Rann Utsav Tours | ${SITE}`,
    description: "Rann Utsav tours from Bhuj with a night by the White Rann: Banni villages, Dholavira, the Road to Heaven and Mandvi. Hotels, meals, transport and sightseeing included.",
  },
  "/gallery": {
    title: `Gallery | ${SITE}`,
    description: "Photos of White Rann Camp's Swiss tents at Dhordo, and of the White Rann of Kutch on its doorstep.",
  },
  "/plan-your-visit": {
    title: `Plan Your Visit | ${SITE}`,
    description: "How to reach White Rann Camp at Dhordo, about 80 km from Bhuj; season dates, check-in times and answers to common questions.",
  },
  "/enquire": {
    title: `Send an Enquiry | ${SITE}`,
    description: "Send White Rann Camp your dates and who's coming; our reservations desk will confirm availability and the rate on WhatsApp or by email.",
  },
};

function usePageMeta() {
  const [location] = useLocation();
  useEffect(() => {
    const page = PAGES[location];
    document.title = page?.title ?? `Page not found | ${SITE}`;
    document.querySelector('meta[name="description"]')?.setAttribute("content", page?.description ?? DEFAULT_DESCRIPTION);
    // A new page starts at the top, unless the address points at a section (#tariff, #faq).
    if (!window.location.hash) window.scrollTo(0, 0);
  }, [location]);
}

export default function App() {
  usePageMeta();
  return (
    <Switch>
      <Route path="/" component={Home} />
      <Route path="/tents" component={Tents} />
      <Route path="/tours" component={Tours} />
      <Route path="/gallery" component={Gallery} />
      <Route path="/plan-your-visit" component={PlanYourVisit} />
      <Route path="/enquire" component={Enquire} />
      <Route component={NotFound} />
    </Switch>
  );
}
