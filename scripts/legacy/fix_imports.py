with open("client/src/pages/Stay.tsx", "r", encoding="utf-8") as f:
    content = f.read()

content = content.replace('Archive, X }', 'Archive, X, Wind }')

with open("client/src/pages/Stay.tsx", "w", encoding="utf-8") as f:
    f.write(content)

print("Imported Wind.")
