from PIL import Image

img = Image.open(r"client\public\assets\images\new\logo-main.jpg").convert("RGB")
width, height = img.size

def get_avg(x_start, y_start):
    r_sum, g_sum, b_sum = 0, 0, 0
    for x in range(x_start, x_start + 10):
        for y in range(y_start, y_start + 10):
            r, g, b = img.getpixel((x, y))
            r_sum += r
            g_sum += g
            b_sum += b
    return (int(r_sum/100), int(g_sum/100), int(b_sum/100))

tl = get_avg(0, 0)
tr = get_avg(width-10, 0)
bl = get_avg(0, height-10)
br = get_avg(width-10, height-10)

print(f"TL: #{tl[0]:02x}{tl[1]:02x}{tl[2]:02x}")
print(f"TR: #{tr[0]:02x}{tr[1]:02x}{tr[2]:02x}")
print(f"BL: #{bl[0]:02x}{bl[1]:02x}{bl[2]:02x}")
print(f"BR: #{br[0]:02x}{br[1]:02x}{br[2]:02x}")
