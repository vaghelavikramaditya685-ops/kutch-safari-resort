import os

filepath = "client/src/pages/Home.tsx"

with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

target = 'className="absolute inset-0 h-full w-full object-cover"'
new_str = 'className="absolute inset-0 h-full w-full object-cover bg-[#2a2a2a]"'

if target in content:
    content = content.replace(target, new_str)
    with open(filepath, "w", encoding="utf-8") as f:
        f.write(content)
    print("Added background color to video tag.")
else:
    print("Video class not found.")
