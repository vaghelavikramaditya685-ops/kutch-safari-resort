from PIL import Image
import math

img = Image.open(r"client\public\assets\images\new\logo-main.jpg").convert("RGB")
width, height = img.size

# Average the top-left 10x10 pixels
r_sum, g_sum, b_sum = 0, 0, 0
count = 0
for x in range(10):
    for y in range(10):
        r, g, b = img.getpixel((x, y))
        r_sum += r
        g_sum += g
        b_sum += b
        count += 1

r_avg = int(r_sum / count)
g_avg = int(g_sum / count)
b_avg = int(b_sum / count)

hex_color = "#{:02x}{:02x}{:02x}".format(r_avg, g_avg, b_avg)
print(f"Top-left average color: {hex_color} (rgb({r_avg}, {g_avg}, {b_avg}))")
