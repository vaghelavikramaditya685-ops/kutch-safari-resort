with open("client/index.html", "r", encoding="utf-8") as f:
    content = f.read()

head_start = content.find("<head>") + len("<head>")
favicon_tag = '\n    <link rel="icon" type="image/jpeg" href="/assets/images/new/logo-main.jpg" />'
content = content[:head_start] + favicon_tag + content[head_start:]

with open("client/index.html", "w", encoding="utf-8") as f:
    f.write(content)

print("Added favicon.")
