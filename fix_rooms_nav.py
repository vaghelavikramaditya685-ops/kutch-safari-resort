import os

rooms_path = "client/src/pages/Rooms.tsx"
with open(rooms_path, "r", encoding="utf-8") as f:
    rooms_content = f.read()

rooms_content = rooms_content.replace('href: "/contact"', 'href: "/#contact"')

with open(rooms_path, "w", encoding="utf-8") as f:
    f.write(rooms_content)

print("Fixed Rooms.tsx nav.")
