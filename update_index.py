import os

filepath = "client/index.html"

with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

preload_tag = '    <link rel="preload" as="video" href="/assets/images/new/KSR_VIDEO.mp4" type="video/mp4" />\n'

if preload_tag not in content:
    content = content.replace('<head>\n', '<head>\n' + preload_tag)
    with open(filepath, "w", encoding="utf-8") as f:
        f.write(content)
    print("Added preload tag to index.html")
else:
    print("Preload tag already exists.")
