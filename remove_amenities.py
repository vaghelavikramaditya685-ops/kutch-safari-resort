import os

files = ["client/src/pages/Home.tsx", "client/src/pages/Rooms.tsx"]

intercom_block = """            <div className="flex flex-col items-center gap-3">
              <PhoneCall className="h-8 w-8 text-foreground/40 stroke-[1.5]" />
              <span className="text-xs uppercase tracking-wider text-foreground/60 font-medium">Intercom</span>
            </div>"""

fridge_block = """            <div className="flex flex-col items-center gap-3">
              <Archive className="h-8 w-8 text-foreground/40 stroke-[1.5]" />
              <span className="text-xs uppercase tracking-wider text-foreground/60 font-medium">Mini Refrigerator</span>
            </div>"""

for filepath in files:
    if os.path.exists(filepath):
        with open(filepath, "r", encoding="utf-8") as f:
            content = f.read()
            
        content = content.replace(intercom_block, "")
        content = content.replace(fridge_block, "")
        
        # In case the grid-cols count needs adjusting:
        # "grid-cols-4 md:grid-cols-8" might need to become "grid-cols-3 md:grid-cols-6" because we removed 2 items.
        content = content.replace("grid-cols-4 md:grid-cols-8", "grid-cols-3 md:grid-cols-6")
        
        with open(filepath, "w", encoding="utf-8") as f:
            f.write(content)
        print(f"Updated {filepath}")
