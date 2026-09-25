import os

# Fix App.tsx
app_path = "client/src/App.tsx"
with open(app_path, "r", encoding="utf-8") as f:
    content = f.read()

content = content.replace('import Rooms from "./pages/Rooms";', 'import Stay from "./pages/Stay";')
content = content.replace('<Route path={"/rooms"} component={Rooms} />', '<Route path={"/stay"} component={Stay} />')

with open(app_path, "w", encoding="utf-8") as f:
    f.write(content)

# Fix Stay.tsx
stay_path = "client/src/pages/Stay.tsx"
with open(stay_path, "r", encoding="utf-8") as f:
    content = f.read()

import re
# Add imports
imports = """import Navbar from "../components/Navbar";
import Footer from "../components/Footer";
"""
content = content.replace('import { PhoneCall, Archive, Lock, Tv, Coffee, Wifi, Snowflake, useEffect, useState } from "react";', imports + 'import { PhoneCall, Archive, Lock, Tv, Coffee, Wifi, Snowflake, useEffect, useState } from "react";')

# Remove LogoBlock, WeatherBar, Navbar, Footer
content = re.sub(r'function LogoBlock[\s\S]*?\}\n', '', content)
content = re.sub(r'const NAV = \[[\s\S]*?\];\n', '', content)
content = re.sub(r'function Navbar[\s\S]*?\}\n', '', content)
content = re.sub(r'function Footer[\s\S]*?\}\n', '', content)

# Remove the ContactSection injection we did earlier (if any) or just leave it since Stay doesn't have it unless we injected it. Actually we injected it into Home.tsx.

# Replace default export name
content = content.replace('export default function Rooms() {', 'export default function Stay() {')

with open(stay_path, "w", encoding="utf-8") as f:
    f.write(content)

print("Fixed Stay.tsx and App.tsx")
