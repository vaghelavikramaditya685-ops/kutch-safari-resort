import os

nav_path = "client/src/components/Navbar.tsx"
with open(nav_path, "r", encoding="utf-8") as f:
    content = f.read()

# Make Header smaller
content = content.replace('h-20', 'h-16')

# Make logo smaller
content = content.replace('text-2xl md:text-3xl', 'text-xl md:text-2xl')
content = content.replace('text-[0.65rem] md:text-xs', 'text-[0.6rem] md:text-[0.65rem]')

# Make font smaller and space evenly
# From: className="hidden lg:flex items-center gap-5 text-sm font-medium text-zinc-700 uppercase tracking-widest"
# To: className="hidden lg:flex flex-1 justify-center items-center gap-4 xl:gap-7 text-[11px] font-semibold text-zinc-700 uppercase tracking-widest"
old_nav_class = 'className="hidden lg:flex items-center gap-5 text-sm font-medium text-zinc-700 uppercase tracking-widest"'
new_nav_class = 'className="hidden lg:flex flex-1 justify-center items-center gap-4 xl:gap-8 text-[11px] font-semibold text-zinc-700 uppercase tracking-widest px-4"'
content = content.replace(old_nav_class, new_nav_class)

# Wrap Logo and Book Now in fixed-width containers so the center is perfectly centered
# But `flex justify-between` is fine. Let's make Logo take up w-48 and the right side take up w-48 so they balance.
content = content.replace('<Link href="/" className="flex flex-col items-start gap-1">', '<Link href="/" className="flex flex-col items-start gap-1 lg:w-48">')
content = content.replace('<div className="flex items-center gap-4">', '<div className="flex items-center justify-end gap-4 lg:w-48">')

with open(nav_path, "w", encoding="utf-8") as f:
    f.write(content)

print("Navbar updated.")
