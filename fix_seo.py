import os

# 1. Change favicon to logo-mark.png
with open("client/index.html", "r", encoding="utf-8") as f:
    content = f.read()

content = content.replace('href="/assets/images/new/logo-main.jpg"', 'href="/assets/images/logo-mark.png"')

with open("client/index.html", "w", encoding="utf-8") as f:
    f.write(content)

# 2. Create robots.txt
robots_content = """User-agent: *
Allow: /

Sitemap: https://kutchsafaribhuj.in/sitemap.xml
"""
with open("client/public/robots.txt", "w", encoding="utf-8") as f:
    f.write(robots_content)

# 3. Create sitemap.xml
sitemap_content = """<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://kutchsafaribhuj.in/</loc>
    <priority>1.0</priority>
    <changefreq>weekly</changefreq>
  </url>
  <url>
    <loc>https://kutchsafaribhuj.in/stay</loc>
    <priority>0.8</priority>
    <changefreq>monthly</changefreq>
  </url>
  <url>
    <loc>https://kutchsafaribhuj.in/our-journey</loc>
    <priority>0.7</priority>
    <changefreq>monthly</changefreq>
  </url>
  <url>
    <loc>https://kutchsafaribhuj.in/dining</loc>
    <priority>0.7</priority>
    <changefreq>monthly</changefreq>
  </url>
  <url>
    <loc>https://kutchsafaribhuj.in/weddings-events</loc>
    <priority>0.7</priority>
    <changefreq>monthly</changefreq>
  </url>
</urlset>
"""
with open("client/public/sitemap.xml", "w", encoding="utf-8") as f:
    f.write(sitemap_content)

print("Added SEO files and updated favicon.")
