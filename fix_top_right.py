import os

filepath = "client/src/pages/Home.tsx"

with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

target = 'src={"/assets/images/new/safari-white-rann.png"}\n                alt="Camel Safari on the White Rann"'
new_str = 'src={IMG.bougainvillea}\n                alt="Lakeside view"'

if target in content:
    content = content.replace(target, new_str)
    with open(filepath, "w", encoding="utf-8") as f:
        f.write(content)
    print("Restored original image.")
else:
    print("Could not find the target to replace.")
