import os

files = ["client/src/pages/Home.tsx", "client/src/pages/Rooms.tsx"]

for filepath in files:
    if os.path.exists(filepath):
        with open(filepath, "r", encoding="utf-8") as f:
            content = f.read()
            
        content = content.replace('href: "#contact"', 'href: "/contact"')
        
        with open(filepath, "w", encoding="utf-8") as f:
            f.write(content)
print("Updated Nav links")
