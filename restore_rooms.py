import os
import re

with open("old_rooms.tsx", "r", encoding="utf-16") as f:
    old_rooms = f.read()

# Extract RoomTemplate function
template_start = old_rooms.find("function RoomTemplate")
template_end = old_rooms.find("function Experiences")
room_template = old_rooms[template_start:template_end]

room_template = room_template.replace("text-foreground/80", "text-zinc-800")
room_template = room_template.replace("text-foreground/70", "text-zinc-600")
room_template = room_template.replace("text-foreground/90", "text-zinc-800")
room_template = room_template.replace("text-foreground/60", "text-zinc-500")
room_template = room_template.replace("text-foreground/40", "text-zinc-400")
room_template = room_template.replace("bg-[#f9f9f9]", "bg-[#f4efe1]") # matching the beige
room_template = room_template.replace('a href="/contact"', 'a href="/#contact"')
room_template = room_template.replace('border-border', 'border-zinc-200')

# Now reconstruct Stay.tsx
stay_content = f"""import {{ useEffect, useState }} from "react";
import Navbar from "../components/Navbar";
import Footer from "../components/Footer";
import {{ Snowflake, Wifi, Coffee, Tv, Lock, Archive, X }} from "lucide-react";

{room_template}

export default function Stay() {{
  useEffect(() => {{
    window.scrollTo(0, 0);
  }}, []);

  return (
    <div className="min-h-screen bg-[#f8f5e2] font-sans text-zinc-900">
      <Navbar />

      <div className="pt-24 pb-16 border-b border-[#e4d5c7]">
        <div className="container mx-auto px-6 text-center max-w-3xl">
          <p className="text-sm font-semibold tracking-[0.2em] uppercase text-[var(--terracotta)] mb-4">Accommodation</p>
          <h1 className="text-4xl md:text-5xl font-display font-bold text-zinc-900 mb-6">The Stay</h1>
          <p className="text-lg text-zinc-600">Two categories, twenty cottages, every one of them looking out over the lake.</p>
        </div>
      </div>

      <div className="py-24">
        <div className="container px-4 mt-8">
            <RoomTemplate 
              title="Kutch AC Cottage"
              exteriorTitle="Exterior"
              interiorTitle="Interior"
              extImgs={{[
                "/assets/images/new/kutch-ac-cottage-bhunga1.jpg",
                "/assets/images/new/kutch-ac-cottage-kutchi-bathroom1.jpg"
              ]}}
              intImgs={{[
                "/assets/images/new/kutch-ac-cottage-kutchi-cottage-interior-2.jpg",
                "/assets/images/new/kutch-ac-cottage-_dsc9435.jpg"
              ]}}
              subtitle="Cottages inspired by Local Styles"
              description="Our signature circular bhungas feature traditional thatched roofs that keep the interior cool, adorned with authentic Kutchi mirror-work. Each cottage is fully air-conditioned with modern en-suite bathrooms and a private veranda where you enjoy a luxurious lifestyle called Rustic Luxury!"
            />

            <RoomTemplate 
              title="Deluxe AC Cottage"
              exteriorTitle="Exterior"
              interiorTitle="Interior"
              extImgs={{[
                "/assets/images/new/kutchi-ac-room-_-deluxe-ac-cottage-deluxe-ac-cottage.jpg",
                "/assets/images/new/kutchi-ac-room-_-deluxe-ac-cottage-kutch-safari-ab-vision-17.jpg"
              ]}}
              intImgs={{[
                "/assets/images/new/kutchi-ac-room-_-deluxe-ac-cottage-deluxe-ac-cottage-interior.jpg",
                "/assets/images/new/kutchi-ac-room-_-deluxe-ac-cottage-deluxe-ac-cottage-interior-02.jpg"
              ]}}
              subtitle="Spacious & Elegantly Designed"
              description="The deluxe cottages offer enhanced comfort while retaining the rich cultural aesthetics of the region. Perfect for families looking for an extended lakeside retreat, these spacious rooms feature exquisite decor, beautiful garden views, and full amenities."
            />
        </div>
      </div>

      <Footer />
    </div>
  );
}}
"""

with open("client/src/pages/Stay.tsx", "w", encoding="utf-8") as f:
    f.write(stay_content)

print("Restored old Rooms layout to Stay.tsx")
