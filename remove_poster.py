import os

filepath = "client/src/pages/Home.tsx"

with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

if "poster={IMG.heroWide}" in content:
    content = content.replace("poster={IMG.heroWide}", "")
    with open(filepath, "w", encoding="utf-8") as f:
        f.write(content)
    print("Removed poster attribute from Home.tsx")
else:
    print("Poster attribute not found.")
