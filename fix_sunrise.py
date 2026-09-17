import os

filepath = "client/src/pages/Home.tsx"

with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

target = 'sunrisePath: "/assets/images/lake-sunrise-reference.png"'
new_str = 'sunrisePath: "/assets/images/new/authentic-sunrise-bhungas.jpg"'

if target in content:
    content = content.replace(target, new_str)
    with open(filepath, "w", encoding="utf-8") as f:
        f.write(content)
    print("Updated sunrise photo to the real one.")
else:
    print("Could not find sunrisePath.")
