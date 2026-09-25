import os

nav_path = "client/src/components/Navbar.tsx"
with open(nav_path, "r", encoding="utf-8") as f:
    content = f.read()

# Replace the text logo with the image
old_logo = """<Link href="/" className="flex flex-col items-start gap-1 lg:w-48">
            <h1 className="font-display text-xl md:text-2xl font-bold tracking-tight text-zinc-900">
              KUTCH SAFARI
            </h1>
            <p className="text-[0.6rem] md:text-[0.65rem] uppercase tracking-[0.3em] font-medium text-[var(--terracotta)]">
              Resort
            </p>
          </Link>"""

new_logo = """<Link href="/" className="flex items-center lg:w-48">
            <img 
              src="/assets/images/new/logo-main.jpg" 
              alt="Kutch Safari Resort Logo" 
              className="h-14 md:h-16 object-contain mix-blend-multiply" 
            />
          </Link>"""

content = content.replace(old_logo, new_logo)

with open(nav_path, "w", encoding="utf-8") as f:
    f.write(content)

print("Navbar logo updated.")
