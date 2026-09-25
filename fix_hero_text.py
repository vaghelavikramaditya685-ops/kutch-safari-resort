import os

with open("client/src/pages/Home.tsx", "r", encoding="utf-8") as f:
    home = f.read()

# Fix the Hero text
old_hero_text = '<h2 className="font-display text-5xl md:text-7xl font-bold mb-8 max-w-4xl leading-tight">'
new_hero_text = '<h2 className="font-display text-5xl md:text-7xl font-bold mb-8 max-w-4xl leading-tight text-white">'

home = home.replace(old_hero_text, new_hero_text)

with open("client/src/pages/Home.tsx", "w", encoding="utf-8") as f:
    f.write(home)

print("Hero text fixed.")
