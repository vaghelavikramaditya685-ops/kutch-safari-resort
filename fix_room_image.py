import os

filepath = "client/src/pages/Rooms.tsx"

with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

# Replace the redundant image
old_img = '"/assets/images/new/kutchi-ac-room-_-deluxe-ac-cottage-kutchi-ac-room1.jpg"'
new_img = '"/assets/images/new/kutchi-ac-room-_-deluxe-ac-cottage-kutch-safari-ab-vision-17.jpg"'

content = content.replace(old_img, new_img)

with open(filepath, "w", encoding="utf-8") as f:
    f.write(content)
print("Updated room images.")
