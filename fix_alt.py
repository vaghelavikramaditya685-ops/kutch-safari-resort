import os

filepath = "client/src/pages/Home.tsx"

with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

target = 'alt="Guests enjoying the lawn at Kutch Safari Resort"'
new_str = 'alt="Aerial view of Kutch Safari Resort by the lake showing property size"'

if target in content:
    content = content.replace(target, new_str)
    with open(filepath, "w", encoding="utf-8") as f:
        f.write(content)
    print("Updated alt text.")
else:
    print("Could not find alt text.")
