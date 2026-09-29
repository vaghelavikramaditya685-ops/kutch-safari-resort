from PIL import Image
from collections import Counter
import math

img = Image.open(r"client\public\assets\images\new\logo-main.jpg").convert("RGB")

def color_dist(c1, c2):
    return math.sqrt(sum((a - b) ** 2 for a, b in zip(c1, c2)))

bg_color = (250, 248, 235)
pixels = list(img.getdata())
foreground_pixels = [p for p in pixels if color_dist(p, bg_color) > 30]
counter = Counter(foreground_pixels)
most_common = counter.most_common(200)

palette = []
for color, count in most_common:
    if not any(color_dist(color, p) < 40 for p, _ in palette):
        palette.append((color, count))

def rgb2hex(r, g, b):
    return "#{:02x}{:02x}{:02x}".format(r, g, b)

print("Foreground Palette:")
for color, count in palette:
    print(f"rgb{color} -> {rgb2hex(*color)} (approx {count} pixels)")
