import os
import shutil

src_dir = r"client\public\assets\images\new\KUTCH SAFARI RESORT"
dest_dir = r"client\public\assets\images\new\gallery"

if not os.path.exists(dest_dir):
    os.makedirs(dest_dir)

image_files = []

for root, dirs, files in os.walk(src_dir):
    for file in files:
        if file.lower().endswith((".jpg", ".jpeg", ".png")):
            src_path = os.path.join(root, file)
            dest_path = os.path.join(dest_dir, file)
            shutil.move(src_path, dest_path)
            image_files.append(f"/assets/images/new/gallery/{file}")

# Also include the ones that were previously added to the gallery
existing_gallery_imgs = [
    "/assets/images/new/kutch-ac-cottage-kutchi-cottage-interior-2.jpg",
    "/assets/images/new/kutch-ac-cottage-_dsc9435.jpg",
    "/assets/images/new/authentic-sunrise-bhungas.jpg",
    "/assets/images/new/restaurant-kutch-safari-ab-vision-11.jpg",
    "/assets/images/new/kutchi-tribes-rabari-ravechi-festival.jpg",
    "/assets/images/new/safari-camel-experience.png",
    "/assets/images/new/aerial-property-shot.jpg"
]

all_images = existing_gallery_imgs + image_files

# Update GalleryPage.tsx
gallery_path = "client/src/pages/GalleryPage.tsx"
with open(gallery_path, "r", encoding="utf-8") as f:
    content = f.read()

import re

# Replace the grid content
images_html = "\n".join([f'         <img src="{img}" className="w-full h-64 object-cover rounded-sm hover:opacity-90 transition-opacity cursor-pointer shadow-sm" alt="Gallery" loading="lazy" />' for img in all_images])

new_grid = f'<div className="grid grid-cols-2 md:grid-cols-3 gap-6">\n{images_html}\n      </div>'

content = re.sub(r'<div className="grid grid-cols-2 md:grid-cols-3 gap-4">[\s\S]*?</div>', new_grid, content)

with open(gallery_path, "w", encoding="utf-8") as f:
    f.write(content)

print(f"Updated GalleryPage.tsx with {len(all_images)} images.")
