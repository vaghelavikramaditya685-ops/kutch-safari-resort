import os

filepath = "client/src/pages/Home.tsx"

with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

# First, revert sunrisePath back to the beautiful authentic sunrise bhungas
content = content.replace('sunrisePath: "/assets/images/new/new-experience-image.jpg"', 'sunrisePath: "/assets/images/new/authentic-sunrise-bhungas.jpg"')

# Now, add a new variable sunriseBreakfast
content = content.replace('sunrisePath: "/assets/images/new/authentic-sunrise-bhungas.jpg",', 'sunrisePath: "/assets/images/new/authentic-sunrise-bhungas.jpg",\n  sunriseBreakfast: "/assets/images/new/new-experience-image.jpg",')

# Now, find the Experiences section and replace IMG.sunrisePath with IMG.sunriseBreakfast
content = content.replace('<img src={IMG.sunrisePath} alt="Sunrise Breakfast by lake"', '<img src={IMG.sunriseBreakfast} alt="Sunrise Breakfast by lake"')

with open(filepath, "w", encoding="utf-8") as f:
    f.write(content)
print("Decoupled the gallery image from the Experiences image.")
