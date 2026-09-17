import os

files = ["client/src/pages/Home.tsx", "client/src/pages/Rooms.tsx"]

old_button = '''<a
              href="https://wa.me/919925238599?text=Hello%20Kutch%20Safari%20Resort%2C%20I%20would%20like%20to%20book%20a%20Rann%20Utsav%20package."
              target="_blank"
              rel="noreferrer"
              className="inline-block bg-[var(--terracotta)] text-white px-8 py-3 uppercase tracking-widest text-sm font-semibold hover:bg-[var(--terracotta)]/90 transition-colors shadow-sm"
            >
              Book Rann Utsav Package
            </a>'''

new_button = '''<Link href="/rann-utsav-package">
              <a className="inline-block bg-[var(--terracotta)] text-white px-8 py-3 uppercase tracking-widest text-sm font-semibold hover:bg-[var(--terracotta)]/90 transition-colors shadow-sm">
                Book Rann Utsav Package
              </a>
            </Link>'''

for filepath in files:
    if os.path.exists(filepath):
        with open(filepath, "r", encoding="utf-8") as f:
            content = f.read()
        
        if "Book Rann Utsav Package" in content and old_button in content:
            content = content.replace(old_button, new_button)
            with open(filepath, "w", encoding="utf-8") as f:
                f.write(content)
            print(f"Updated {filepath}")
        else:
            print(f"Button not found in {filepath} or already updated.")
