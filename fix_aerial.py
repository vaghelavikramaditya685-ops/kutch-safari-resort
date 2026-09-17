import os

filepath = "client/src/pages/Home.tsx"

with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

target = 'cottagesLawn: "/assets/images/new/guests-img-20180402-wa0053.jpg"'
new_str = 'cottagesLawn: "/assets/images/new/aerial-property-shot.jpg"'

if target in content:
    content = content.replace(target, new_str)
    with open(filepath, "w", encoding="utf-8") as f:
        f.write(content)
    print("Updated dancing photo to aerial property shot.")
else:
    print("Could not find cottagesLawn mapping.")
