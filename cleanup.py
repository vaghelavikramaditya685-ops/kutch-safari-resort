import os

app_path = "client/src/App.tsx"
with open(app_path, "r", encoding="utf-8") as f:
    app_content = f.read()

app_content = app_content.replace('import Contact from "./pages/Contact";\n', '')
app_content = app_content.replace('<Route path={"/contact"} component={Contact} />\n', '')

with open(app_path, "w", encoding="utf-8") as f:
    f.write(app_content)

if os.path.exists("client/src/pages/Contact.tsx"):
    os.remove("client/src/pages/Contact.tsx")

print("Cleaned up App.tsx and removed Contact.tsx")
