import os

files = ["client/src/pages/Home.tsx", "client/src/pages/Rooms.tsx"]

old = '<a href="/#contact" className="btn-explore">Book Rann Utsav Package</a>'
new = '<Link href="/rann-utsav-package"><a className="btn-explore">Book Rann Utsav Package</a></Link>'

for filepath in files:
    if os.path.exists(filepath):
        with open(filepath, "r", encoding="utf-8") as f:
            content = f.read()
        if old in content:
            content = content.replace(old, new)
            with open(filepath, "w", encoding="utf-8") as f:
                f.write(content)
            print(f"Updated {filepath}")
