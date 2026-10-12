import Navbar from "@/components/Navbar";
import useScrollToHash from "@/useScrollToHash";
import Footer from "@/components/Footer";
import Figure, { PageHeader, SectionTitle } from "@/components/Figure";
import { CAMP_PHOTOS as P, COMMONS_PHOTOS, RANN, type Photo } from "@/data";

const rann = Object.values(RANN);

/* The camp's own photos, grouped. Most are from the owner's 2026 photo set. */
const GROUPS: { eyebrow: string; title: string; photos: Photo[] }[] = [
  { eyebrow: "After Dark", title: "The Camp at Night", photos: [P.entranceNight, P.campNight, P.tentsNight, P.lightsNight, P.canopyNight] },
  { eyebrow: "Stay", title: "Inside the Tents", photos: [P.deluxeSitting, P.deluxeBed, P.creamDouble, P.creamTwin, P.creamSitting, P.creamWide, P.yellowTwin, P.yellowDouble, P.yellowDoor, P.yellowBed, P.nonacInterior] },
  { eyebrow: "Every Tent", title: "Attached Washrooms", photos: [P.washroomBasinToilet, P.washroomToilet, P.washroomCorner] },
  { eyebrow: "At the Camp", title: "Dining and Crafts", photos: [P.diningTent, P.textiles] },
  { eyebrow: "By Day", title: "The Tents in Daylight", photos: [P.tentsRow, P.tentsSky, P.tentDay] },
];

export default function Gallery() {
  useScrollToHash();
  return (
    <div className="min-h-screen">
      <Navbar />
      <PageHeader eyebrow="Gallery" title="The Camp and the Rann" intro="Our tents, and the White Rann on our doorstep." />

      {GROUPS.map((g, i) => (
        <section key={g.title} className={`py-16 ${i % 2 ? "bg-white border-y border-rann-200" : ""}`}>
          <div className="container mx-auto px-6">
            <SectionTitle eyebrow={g.eyebrow} title={g.title} />
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
              {g.photos.map((p) => <Figure key={p.src} photo={p} className="h-64 rounded-sm" />)}
            </div>
          </div>
        </section>
      ))}

      <section className="py-16 bg-rann-50 border-y border-rann-200">
        <div className="container mx-auto px-6">
          <SectionTitle eyebrow="On Our Doorstep" title="The White Rann" />
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {rann.map((p) => <Figure key={p.src} photo={p} className="h-72 rounded-sm" />)}
          </div>
        </div>
      </section>

      <section id="credits" className="py-16 scroll-mt-24">
        <div className="container mx-auto px-6 max-w-4xl">
          <h2 className="text-3xl font-bold text-zinc-900 mb-4">Photo credits</h2>
          <p className="text-sm text-zinc-600 mb-4">
            The tent and camp photographs are White Rann Camp's own. The White Rann landscapes and the photos of places nearby are from Wikimedia Commons,
            used under the Creative Commons licences shown, and were resized for this site.
          </p>
          <ul className="space-y-2 text-sm text-zinc-700">
            {COMMONS_PHOTOS.map((p) => (
              <li key={p.src}>
                “<a href={p.credit.page} target="_blank" rel="noreferrer" className="text-rann-700 underline underline-offset-2">{p.credit.title}</a>”
                {" "}by {p.credit.author},{" "}
                <a href={p.credit.licenceUrl} target="_blank" rel="noreferrer" className="text-rann-700 underline underline-offset-2">{p.credit.licence}</a>, via Wikimedia Commons
              </li>
            ))}
          </ul>
        </div>
      </section>

      <Footer />
    </div>
  );
}
