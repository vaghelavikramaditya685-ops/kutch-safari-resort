from PIL import Image
from collections import Counter

img = Image.open(r"client\public\assets\images\new\logo-main.jpg").convert("RGB")
width, height = img.size

edges = []
for x in range(width):
    edges.append(img.getpixel((x, 0)))
    edges.append(img.getpixel((x, height - 1)))
for y in range(1, height - 1):
    edges.append(img.getpixel((0, y)))
    edges.append(img.getpixel((width - 1, y)))

most_common = Counter(edges).most_common(5)

def rgb2hex(r, g, b):
    return "#{:02x}{:02x}{:02x}".format(r, g, b)

print("Most common edge colors:")
for color, count in most_common:
    print(f"rgb{color} -> {rgb2hex(*color)}: {count} pixels")
