import os

app_path = "client/src/App.tsx"
with open(app_path, "r", encoding="utf-8") as f:
    content = f.read()

imports_to_add = """import OurJourney from "./pages/OurJourney";
import Dining from "./pages/Dining";
import Weddings from "./pages/Weddings";
import GalleryPage from "./pages/GalleryPage";
import PlanYourVisit from "./pages/PlanYourVisit";
import Packages from "./pages/Packages";
"""

routes_to_add = """      <Route path={"/our-journey"} component={OurJourney} />
      <Route path={"/dining"} component={Dining} />
      <Route path={"/weddings"} component={Weddings} />
      <Route path={"/gallery"} component={GalleryPage} />
      <Route path={"/plan-your-visit"} component={PlanYourVisit} />
      <Route path={"/packages"} component={Packages} />
"""

content = content.replace('import Home from "./pages/Home";', imports_to_add + 'import Home from "./pages/Home";')
content = content.replace('<Route path={"/rooms"} component={Rooms} />', '<Route path={"/rooms"} component={Rooms} />\n' + routes_to_add)

with open(app_path, "w", encoding="utf-8") as f:
    f.write(content)

print("Updated App.tsx with new routes.")
