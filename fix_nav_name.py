import os

filepath = "client/src/pages/Contact.tsx"
with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

content = content.replace("const NAV_LINKS = [", "const NAV = [")

with open(filepath, "w", encoding="utf-8") as f:
    f.write(content)
print("Fixed NAV variable name.")
