import os

filepath = "client/src/pages/Home.tsx"

with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

target = 'poster="/assets/images/new/guests-20180326_174201.jpg"'
if target in content:
    content = content.replace(target, "")
    with open(filepath, "w", encoding="utf-8") as f:
        f.write(content)
    print("Removed second poster attribute from Home.tsx")
else:
    print("Poster 2 attribute not found.")
