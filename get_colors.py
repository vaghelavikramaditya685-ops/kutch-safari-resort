from PIL import Image
from collections import Counter

img = Image.open(r"client\public\assets\images\new\logo-main.jpg").convert("RGB")
width, height = img.size

# Background from top-left
bg_color = img.getpixel((0, 0))

# Get most common colors to find the palette
pixels = list(img.getdata())
counter = Counter(pixels)
most_common = counter.most_common(10)

def rgb2hex(r, g, b):
    return "#{:02x}{:02x}{:02x}".format(r, g, b)

print(f"Background color: rgb{bg_color} -> {rgb2hex(*bg_color)}")
print("Most common colors:")
for color, count in most_common:
    print(f"rgb{color} -> {rgb2hex(*color)}: {count} pixels")
