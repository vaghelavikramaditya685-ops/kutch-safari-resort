import os

filepath = "client/src/pages/Rooms.tsx"

with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

# Replace the redundant bottom-right image
old_img = '"/assets/images/new/kutchi-ac-room-_-deluxe-ac-cottage-kutchi-ac-room.jpeg"'
new_img = '"/assets/images/new/kutchi-ac-room-_-deluxe-ac-cottage-deluxe-ac-cottage-interior-02.jpg"'

content = content.replace(old_img, new_img)

with open(filepath, "w", encoding="utf-8") as f:
    f.write(content)
print("Updated bottom-right image.")
