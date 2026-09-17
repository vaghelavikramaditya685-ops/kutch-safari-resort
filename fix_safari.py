import os

filepath = "client/src/pages/Home.tsx"

with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

target = 'sunsetTree: "/assets/images/new/pro-kala_dungar.jpg"'
new_str = 'sunsetTree: "/assets/images/new/safari-camel-experience.png"'

if target in content:
    content = content.replace(target, new_str)
    with open(filepath, "w", encoding="utf-8") as f:
        f.write(content)
    print("Updated sunsetTree image to the camel safari image.")
else:
    print("Could not find sunsetTree.")
