import os

filepath = "client/src/pages/Home.tsx"

with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

# Change sunriseBreakfast back to authentic-sunrise-bhungas
target = 'sunriseBreakfast: "/assets/images/new/new-experience-image.jpg"'
new_str = 'sunriseBreakfast: "/assets/images/new/authentic-sunrise-bhungas.jpg"'

if target in content:
    content = content.replace(target, new_str)
    with open(filepath, "w", encoding="utf-8") as f:
        f.write(content)
    print("Reverted sunriseBreakfast back to the authentic sunrise photo.")
else:
    print("Could not find sunriseBreakfast.")
