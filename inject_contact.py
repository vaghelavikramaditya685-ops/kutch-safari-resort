import os
import re

contact_path = "client/src/pages/Contact.tsx"
home_path = "client/src/pages/Home.tsx"

with open(contact_path, "r", encoding="utf-8") as f:
    contact_content = f.read()

# Extract everything between "export default function Contact() {" and "return ("
logic_match = re.search(r'export default function Contact\(\) \{([\s\S]*?)return \(', contact_content)
logic = logic_match.group(1).replace("  useEffect(() => {\n    window.scrollTo(0, 0);\n  }, []);\n\n", "")

# Extract everything between "{/* Main Content */}" and "{/* Footer minimal */}"
ui_match = re.search(r'\{/\* Hero Section \*/\}([\s\S]*?)<Footer />', contact_content)
ui = ui_match.group(1)

# Modify ui to be a section
ui = '<section id="contact" className="relative">\n' + ui + '\n</section>'

new_component = f"""
function Contact() {{
{logic}
  return (
    {ui}
  );
}}
"""

with open(home_path, "r", encoding="utf-8") as f:
    home_content = f.read()

# Insert new_component before export default function Home() {
home_content = home_content.replace("export default function Home() {", new_component + "\nexport default function Home() {")

# Insert <Contact /> before <Footer />
home_content = home_content.replace("<Footer />", "<Contact />\n      <Footer />")

# Make sure imports are there: Send, CheckCircle2, User
imports_to_add = "import { Phone, Mail, MapPin, Clock, Send, CheckCircle2, User, PhoneCall } from 'lucide-react';"
home_content = imports_to_add + "\n" + home_content

# Fix NAV links back to #contact instead of /contact
home_content = home_content.replace('href: "/contact"', 'href: "#contact"')

with open(home_path, "w", encoding="utf-8") as f:
    f.write(home_content)

print("Injected Contact section into Home.tsx.")
