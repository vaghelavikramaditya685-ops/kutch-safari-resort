import os
import glob

OLD_BEIGE = "#faf8eb"
NEW_BEIGE = "#f8f5e2"

# Update Navbar.tsx
with open("client/src/components/Navbar.tsx", "r", encoding="utf-8") as f:
    nav = f.read()

nav = nav.replace(OLD_BEIGE, NEW_BEIGE)
nav = nav.replace('mix-blend-multiply', '')

with open("client/src/components/Navbar.tsx", "w", encoding="utf-8") as f:
    f.write(nav)

# Update all pages
for file in glob.glob("client/src/pages/*.tsx"):
    with open(file, "r", encoding="utf-8") as f:
        content = f.read()
    
    content = content.replace(OLD_BEIGE, NEW_BEIGE)
    
    with open(file, "w", encoding="utf-8") as f:
        f.write(content)

# Update Footer
with open("client/src/components/Footer.tsx", "r", encoding="utf-8") as f:
    footer = f.read()

footer = footer.replace(OLD_BEIGE, NEW_BEIGE)

with open("client/src/components/Footer.tsx", "w", encoding="utf-8") as f:
    f.write(footer)

print("Updated beige color and removed mix-blend-multiply.")
