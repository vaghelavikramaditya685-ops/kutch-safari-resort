import os

nav_path = "client/src/components/Navbar.tsx"
with open(nav_path, "r", encoding="utf-8") as f:
    nav = f.read()

# I removed mix-blend-multiply previously. Let's add mix-blend-darken.
nav = nav.replace('className="h-14 md:h-16 object-contain "', 'className="h-14 md:h-16 object-contain mix-blend-darken"')
nav = nav.replace('className="h-14 md:h-16 object-contain"', 'className="h-14 md:h-16 object-contain mix-blend-darken"')

with open(nav_path, "w", encoding="utf-8") as f:
    f.write(nav)

print("Added mix-blend-darken to logo.")
