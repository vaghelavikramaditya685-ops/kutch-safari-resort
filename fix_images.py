import os

filepath = "client/src/pages/Home.tsx"

with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

# Replace the specific class strings
content = content.replace(
    'className="w-full aspect-[3/4] object-cover shadow-lg border-2 border-white/20"',
    'className="w-full aspect-[4/3] object-cover shadow-lg border-2 border-white/20"'
)

with open(filepath, "w", encoding="utf-8") as f:
    f.write(content)
print("Updated image classes.")
