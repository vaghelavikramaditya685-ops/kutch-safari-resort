import os

filepath = "client/src/pages/Home.tsx"

with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

old_source = '<source src="/assets/images/new/kutch-safari-resort-website-hero.mp4" type="video/mp4" />'
new_source = '<source src="/assets/images/new/KSR_VIDEO.mp4" type="video/mp4" />'

if old_source in content:
    content = content.replace(old_source, new_source)
    with open(filepath, "w", encoding="utf-8") as f:
        f.write(content)
    print("Updated Home.tsx with KSR_VIDEO.")
else:
    print("Old source not found.")
