import os

with open("client/src/pages/Home.tsx", "r", encoding="utf-8") as f:
    content = f.read()

# Replace the Kutch Safari Bhunga image with the old Rann Utsav image
content = content.replace('src="/assets/images/new/authentic-sunrise-bhungas.jpg" alt="White Rann Camp"', 'src="/assets/images/new/kutchi-tribes-rabari-ravechi-festival.jpg" alt="White Rann Camp"')

with open("client/src/pages/Home.tsx", "w", encoding="utf-8") as f:
    f.write(content)

print("Fixed White Rann Camp image.")
