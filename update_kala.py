import os

filepath = "client/src/pages/Home.tsx"

with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

old_img = 'img: "/assets/images/new/pro-kala_dungar.jpg"'
new_img = 'img: "/assets/images/new/kala-dungar-scenic.jpg"'

old_alt = 'alt: "Rabari camel caravan near Kala Dungar"'
new_alt = 'alt: "Panoramic view of the White Rann from Kala Dungar"'

content = content.replace(old_img, new_img)
content = content.replace(old_alt, new_alt)

with open(filepath, "w", encoding="utf-8") as f:
    f.write(content)
print("Updated Kala Dungar destination image.")
