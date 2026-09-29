# Kutch Safari Resort — Design Brief (v2)

## Ground Truth: RannRiders.com (user-provided reference)
The site must follow the RannRiders design system as the ground-truth spec (see /home/ubuntu/rannriders_analysis.md). Key rules:
- Alternating white ↔ warm sand (#DDB58D-ish) full-width section bands
- Centered uppercase serif section titles (Playfair/Cormorant-like), italic serif tagline under the title
- Terracotta/burnt-orange (#D2622D) rectangular buttons with uppercase text ("EXPLORE", "Enquire Now")
- Faint wildlife line-art sketch behind/near section titles as decoration
- Photo mosaics: 1 tall + 2 stacked, or large + 2 stacked, or 3-col grid patterns
- Dark charcoal footer with sub-link columns, credits band, contact icons, social tiles
- Floating fixed "Enquire Now" terracotta pill button + WhatsApp/call widget bottom-right
- Sticky white navbar: wordmark left, centered uppercase nav links, BOOK NOW right
- Copy tone: experiential, "path less trodden", tagline under hero headline


# Kutch Safari Resort — Design Brainstorm (v1, superseded)

## Three Stylistic Approaches

### 1. Sundown Terracotta (editorial heritage)
Warm earthy editorial style rooted in Kutchi craft — terracotta, ochre, and sand tones with serif display type, evoking a printed travel journal about the White Rann. Emotional intent: warmth, authenticity, cultural richness.
Probability: 0.07

### 2. Midnight Mirrors (dark luxury with mirror-work accents)
A deep indigo/night-sky palette referencing the famous Kutch starlit nights and mirror-work textiles, with glowing gold accents and crisp minimal layouts. Emotional intent: romance, exclusivity, quiet luxury.
Probability: 0.03

### 3. Daybreak Lakehouse (airy lakeside modern)
Bright, airy lakeside aesthetic — pale aqua and white reflecting the Rudramata reservoir at dawn, with rounded organic shapes and soft photography. Emotional intent: freshness, calm, simplicity.
Probability: 0.05

## CHOSEN: Sundown Terracotta (editorial heritage)

**Design Movement**: Heritage editorial / Indian craft modernism — inspired by travel magazines, Kutchi handicraft aesthetics (mirror work, mud appliqué, Rogan art), and contemporary Indian hospitality branding.

**Core Principles**:
1. Warmth over polish — the palette must feel like sun on white mud walls, not like a hotel brochure.
2. Craft texture everywhere — subtle grain, border motifs, and asymmetric compositions echo Kutchi textile work.
3. The sunrise is the hero — imagery and gradients always celebrate the lake-facing dawn moment.
4. Editorial asymmetry — offset grids, overlapping panels, and generous margins, never flat centered stacks.

**Color Philosophy**:
- Base: warm sand / ivory (like lime-washed bhunga walls) — `oklch(0.97 0.015 85)`
- Ink: deep brown-charcoal — `oklch(0.28 0.03 50)`
- Primary: deep terracotta / sindoor red — `oklch(0.55 0.16 35)` — the color of Kutch mud plaster and sunsets
- Accent: saffron-gold — `oklch(0.75 0.13 75)` — mirroring mirror-work gold
- Supporting: muted olive-green — the lush lawn around cottages
The intent: evoke heat, earth, and craft. No blues, no purples.

**Layout Paradigm**: Asymmetric editorial spreads. Hero is a split composition (text left over ivory, full-bleed image right). Sections alternate ivory/sand/terracotta bands with diagonal or stepped transitions. Cards and photo panels are offset, overlapping with thin 1px ink frames like framed textile art, not uniform white cards with shadows.

**Signature Elements**:
1. "Mirror-frame" motif — thin double-line borders with small diamond/corner marks, inspired by Kutch mirror work, used on images and cards.
2. Terracotta sun disc — a simple circular sun motif used as bullets, section markers, and the logo mark.
3. Stitched border strips — thin dashed/serrated rules between sections, echoing textile seams.

**Interaction Philosophy**: Interactions feel tactile and slow-warm: images gently lift and warm on hover, links underline with a hand-drawn stroke, buttons compress like pressed clay. Nothing bounces or glows.

**Animation**: Entrance animations are soft fades + 12px upward drift, 500–700ms, ease-out, staggered 60–80ms. Image hovers: scale 1.03 over 600ms. Underline draw-in for links. Respect prefers-reduced-motion.

**Typography System**:
- Display: "Cormorant Garamond" (600/700) — tall, characterful serif with Indian-print flavor
- Body: "Jost" or "Karla" (400/500) — clean geometric humanist
- Accent/overlines: uppercase letter-spaced Jost 600, terracotta
- Hierarchy: overline kicker → big serif headline → readable body; drop caps on intro paragraphs

**Brand Essence**: The lakeside gateway to the White Rann — 17 mirror-work bhunga cottages on 10 acres above the Rudramata reservoir, where Kutchi craft meets a 20-year legacy of warm hospitality. Adjectives: warm, handcrafted, welcoming.

**Brand Voice**: Poetic but grounded; speaks like a host, not a marketer. No "Welcome to our website".
Examples:
- "Wake where the sun paints the lake."
- "Twenty years of sunrise, served warm with chai."

**Wordmark & Logo**: "KUTCH SAFARI" set in Cormorant Garamond small caps with a terracotta sun-disc glyph (circle with a dot) to the left; "RESORT · BHUJ" in letter-spaced Jost beneath. Mark: terracotta sun disc on ivory.

**Signature Brand Color**: Terracotta sindoor `#B54A2B`-ish (oklch 0.55 0.16 35) — the color of Kutch mud walls at sunset.
