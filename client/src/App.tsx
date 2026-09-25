import { Toaster } from "@/components/ui/sonner";
import { TooltipProvider } from "@/components/ui/tooltip";
import NotFound from "@/pages/NotFound";
import { Route, Switch } from "wouter";
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
import BookingRedirect from "./pages/BookingRedirect";



function Router() {
  return (
    <Switch>
      <Route path={"/"} component={Home} />
      <Route path={"/booking"} component={BookingRedirect} />
      <Route path={"/book"} component={BookingRedirect} />
      <Route path={"/book/*"} component={BookingRedirect} />
      <Route path={"/stay"} component={Stay} />
      <Route path={"/experiences"} component={Experiences} />
      <Route path={"/our-journey"} component={OurJourney} />
      <Route path={"/dining"} component={Dining} />
      <Route path={"/gallery"} component={GalleryPage} />
      <Route path={"/plan-your-visit"} component={PlanYourVisit} />
      <Route path={"/packages"} component={Packages} />

      <Route path={"/destination/:slug"} component={Destination} />
      <Route path={"/rann-utsav-package"} component={RannUtsavPackage} />
      <Route path={"/white-rann-camp"} component={RannUtsavPackage} />
      
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
