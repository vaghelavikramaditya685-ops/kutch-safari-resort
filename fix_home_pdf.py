import os
import re

with open("client/src/pages/Home.tsx", "r", encoding="utf-8") as f:
    content = f.read()

# 1. StatsBand: Remove the 4th stat
stats_old = """          <div className="flex flex-col gap-2">
            <span className="text-4xl md:text-5xl font-display text-[var(--terracotta)] font-bold">15</span>
            <span className="text-sm uppercase tracking-widest text-zinc-600 font-semibold">Km from Bhuj</span>
          </div>
          <div className="flex flex-col gap-2">
            <span className="text-4xl md:text-5xl font-display text-[var(--terracotta)] font-bold">300</span>
            <span className="text-sm uppercase tracking-widest text-zinc-600 font-semibold">Guests for Events</span>
          </div>
        </div>"""

stats_new = """          <div className="flex flex-col gap-2">
            <span className="text-4xl md:text-5xl font-display text-[var(--terracotta)] font-bold">15</span>
            <span className="text-sm uppercase tracking-widest text-zinc-600 font-semibold">Km from Bhuj</span>
          </div>
        </div>"""

content = content.replace(stats_old, stats_new)

# Make the grid cols 3 instead of 4
content = content.replace('grid-cols-2 md:grid-cols-4 gap-8', 'grid-cols-1 md:grid-cols-3 gap-8')

# 2. Amenities to 6 items and rename heading
content = content.replace('What We Do Well</p>', 'Why Guests Return</p>')
content = content.replace('>Everything You Need, Nothing You Don\'t</h2>', '>What We Do Well</h2>')

amenities_old = """    { title: "Swimming Pool", desc: "An open-air pool for beating the Kutch afternoon." },
    { title: "The Banni Restaurant", desc: "Multi-cuisine, vegetarian and non-vegetarian, by the lake." },
    { title: "Travel Desk", desc: "Taxis, guides, airport and station transfers, birding jeeps." },
    { title: "Free Wi-Fi", desc: "Throughout the cottages and the public areas." },
    { title: "Indoor Games", desc: "For long afternoons and slow evenings." },
    { title: "Open Garden Lawn", desc: "Weddings, conferences and celebrations for up to 300." },
    { title: "Gala Dinners", desc: "Folk music, bonfires and Kutchi thalis under the stars." },
    { title: "Room Service", desc: "In-room dining directly to your cottage." },"""

amenities_new = """    { title: "Swimming Pool", desc: "An open-air pool for beating the Kutch afternoon." },
    { title: "The Banni Restaurant", desc: "Multi-cuisine, vegetarian and non-vegetarian, by the lake." },
    { title: "Travel Desk & Experiences", desc: "Taxis, guides, airport and station transfers, plus curated local experiences." },
    { title: "Free Wi-Fi", desc: "Throughout the cottages and the public areas." },
    { title: "Open Garden Lawn", desc: "Weddings, conferences and celebrations for up to 300." },
    { title: "Room Service", desc: "In-room dining directly to your cottage." },"""

content = content.replace(amenities_old, amenities_new)
content = content.replace('lg:grid-cols-4 gap-x-8', 'lg:grid-cols-3 gap-x-8')

# 3. Add the 4 feature bullet points under "The Resort" section
features = """
              <div className="mt-6 flex flex-wrap gap-4">
                <span className="bg-zinc-100 text-zinc-800 px-4 py-2 rounded-full text-sm font-semibold shadow-sm">Lake-facing cottages</span>
                <span className="bg-zinc-100 text-zinc-800 px-4 py-2 rounded-full text-sm font-semibold shadow-sm">Pet friendly</span>
                <span className="bg-zinc-100 text-zinc-800 px-4 py-2 rounded-full text-sm font-semibold shadow-sm">Centrally located</span>
                <span className="bg-zinc-100 text-zinc-800 px-4 py-2 rounded-full text-sm font-semibold shadow-sm">Non-veg & Veg cuisine</span>
              </div>
"""
# Insert it after "places to stay in Bhuj and the Rann of Kutch."
content = content.replace('and the Rann of Kutch.\n                  </p>\n                </div>', 'and the Rann of Kutch.\n                  </p>\n                </div>' + features)

with open("client/src/pages/Home.tsx", "w", encoding="utf-8") as f:
    f.write(content)

print("Home.tsx fixed according to PDF.")
