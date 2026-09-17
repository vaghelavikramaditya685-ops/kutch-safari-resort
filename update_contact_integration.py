import os
import re

contact_path = "client/src/pages/Contact.tsx"
home_path = "client/src/pages/Home.tsx"

with open(home_path, "r", encoding="utf-8") as f:
    home_content = f.read()

# Extract NAV_LINKS, LogoBlock, WeatherBar, Navbar, Footer from Home.tsx
def extract_function(name, content):
    pattern = r'(function ' + name + r'[\s\S]*?\n\n)'
    match = re.search(pattern, content)
    if match:
        return match.group(1)
    # try matching till EOF or another function
    pattern2 = r'(function ' + name + r'[\s\S]*?)(?=function |\Z)'
    match = re.search(pattern2, content)
    return match.group(1) if match else ""

nav_links = """const NAV_LINKS = [
  { label: "The Resort", href: "/#resort" },
  { label: "Stays", href: "/#cottages" },
  { label: "Experiences", href: "/#experiences" },
  { label: "Packages", href: "/#packages" },
  { label: "Beyond Bhuj", href: "/#explore" },
  { label: "Rann Utsav", href: "/#rann-utsav" },
  { label: "Contact", href: "/contact" },
];"""

logo_block = extract_function("LogoBlock", home_content)
weather_bar = extract_function("WeatherBar", home_content)
navbar = extract_function("Navbar", home_content)
footer = extract_function("Footer", home_content)

# Adjust navbar to be solid bg-background/95 instead of transparent
navbar = navbar.replace("""className={`fixed inset-x-0 top-0 z-50 transition-all duration-300 ${
        scrolled || open
          ? "bg-background/95 backdrop-blur-sm shadow-[0_1px_0_0_oklch(0.88_0.03_75/0.8)] py-3"
          : "bg-transparent py-5"
      }`}""", 'className="fixed inset-x-0 top-0 z-50 transition-all duration-300 bg-background/95 backdrop-blur-md shadow-[0_1px_0_0_oklch(0.88_0.03_75/0.8)] py-3"')
# Remove setScrolled stuff
navbar = re.sub(r'const \[scrolled, setScrolled\] = useState\(false\);\n.*?\[\]\);\n\n', '', navbar, flags=re.DOTALL)
navbar = navbar.replace("scrolled || open", "true")
navbar = navbar.replace('light={!scrolled && !open}', 'light={false}')
navbar = navbar.replace('text-white', 'text-foreground/80 hover:text-foreground')
navbar = navbar.replace('!scrolled && !open ? "text-white/90 hover:text-white" : "text-foreground/80 hover:text-foreground"', '"text-foreground/80 hover:text-foreground"')


with open(contact_path, "r", encoding="utf-8") as f:
    contact_content = f.read()

# Replace the minimalistic header and footer with the integrated ones
# Find the header
header_pattern = r'\{/\* Header \*/\}[\s\S]*?</header>'
contact_content = re.sub(header_pattern, '<Navbar />', contact_content)

footer_pattern = r'\{/\* Footer minimal \*/\}[\s\S]*?</footer>'
contact_content = re.sub(footer_pattern, '<Footer />', contact_content)

# Import Menu, X, Facebook, Instagram, Sun, Cloud
imports = """import { ArrowLeft, MapPin, Phone, Mail, Clock, Send, CheckCircle2, User, PhoneCall, Building, Menu, X, Facebook, Instagram, Sun, Cloud } from "lucide-react";"""
contact_content = re.sub(r'import \{ ArrowLeft[\s\S]*?\} from "lucide-react";', imports, contact_content)

# Inject the components before export default function Contact
inject = f"\n{nav_links}\n\n{logo_block}\n\n{weather_bar}\n\n{navbar}\n\n{footer}\n\nexport default function Contact"
contact_content = contact_content.replace("export default function Contact", inject)

with open(contact_path, "w", encoding="utf-8") as f:
    f.write(contact_content)

print("Integrated Contact page with main website layout.")
