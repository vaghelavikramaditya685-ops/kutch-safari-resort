import os

filepath = "client/src/pages/Home.tsx"

with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

target = 'alt="Lakeside view"'
new_str = 'alt="Camel Safari on the White Rann"'

if target in content:
    content = content.replace(target, new_str)
    with open(filepath, "w", encoding="utf-8") as f:
        f.write(content)
    print("Updated Safari alt text.")
else:
    print("Could not find alt text.")
