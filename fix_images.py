import os

files_to_update = [
    'client/src/pages/Home.tsx',
    'client/src/pages/Rooms.tsx',
    'client/src/pages/Destination.tsx'
]

replacements = {
    '"/assets/images/new/pro-road_to_heaven.jpg"': '"/assets/images/new/kutch-destination-road_2.jpg"',
}

for filepath in files_to_update:
    if os.path.exists(filepath):
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        for old, new in replacements.items():
            content = content.replace(old, new)
            
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Updated {filepath}")
