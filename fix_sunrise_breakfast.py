import os

filepath = "client/src/pages/Home.tsx"

with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

# Let's change the sunrisePath variable to point to the new image.
target = 'sunrisePath: "/assets/images/new/authentic-sunrise-bhungas.jpg"'
new_str = 'sunrisePath: "/assets/images/new/new-experience-image.jpg"'

if target in content:
    content = content.replace(target, new_str)
    with open(filepath, "w", encoding="utf-8") as f:
        f.write(content)
    print("Updated sunrisePath to the newly uploaded image.")
else:
    print("Could not find sunrisePath.")
