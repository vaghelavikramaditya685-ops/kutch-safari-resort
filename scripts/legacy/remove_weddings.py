import os

# 1. Update App.tsx
with open("client/src/App.tsx", "r", encoding="utf-8") as f:
    app_content = f.read()

app_content = app_content.replace('import Weddings from "./pages/Weddings";\n', '')
app_content = app_content.replace('      <Route path={"/weddings"} component={Weddings} />\n', '')

with open("client/src/App.tsx", "w", encoding="utf-8") as f:
    f.write(app_content)

# 2. Update Navbar.tsx
with open("client/src/components/Navbar.tsx", "r", encoding="utf-8") as f:
    nav_content = f.read()

nav_content = nav_content.replace('    { label: "Weddings", href: "/weddings" },\n', '')

with open("client/src/components/Navbar.tsx", "w", encoding="utf-8") as f:
    f.write(nav_content)

# 3. Update Home.tsx
with open("client/src/pages/Home.tsx", "r", encoding="utf-8") as f:
    home_content = f.read()

home_content = home_content.replace('Weddings, conferences and celebrations for up to 300.', 'Conferences and private celebrations for up to 300.')

with open("client/src/pages/Home.tsx", "w", encoding="utf-8") as f:
    f.write(home_content)

# 4. Update sitemap.xml
with open("client/public/sitemap.xml", "r", encoding="utf-8") as f:
    sitemap_content = f.read()

sitemap_block = """  <url>
    <loc>https://kutchsafaribhuj.in/weddings-events</loc>
    <priority>0.7</priority>
    <changefreq>monthly</changefreq>
  </url>
"""
sitemap_content = sitemap_content.replace(sitemap_block, '')

with open("client/public/sitemap.xml", "w", encoding="utf-8") as f:
    f.write(sitemap_content)

# 5. Delete Weddings.tsx if exists
weddings_path = "client/src/pages/Weddings.tsx"
if os.path.exists(weddings_path):
    os.remove(weddings_path)
    print("Deleted Weddings.tsx")

print("Removed all wedding references.")
