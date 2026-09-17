import os
import re

files = ["client/src/pages/Home.tsx", "client/src/pages/Rooms.tsx"]

for filepath in files:
    with open(filepath, "r", encoding="utf-8") as f:
        content = f.read()

    # The Contact function in Home and Rooms looks something like:
    # function Contact() {
    #   return (
    #     <section id="contact" className="bg-secondary py-20">
    #     ...
    #     </section>
    #   );
    # }
    
    # We will use regex to remove it.
    pattern = r'function Contact\(\) \{[\s\S]*?\n\}\n'
    content = re.sub(pattern, '', content)
    
    # Also remove the <Contact /> component call in the main return block
    content = content.replace('<Contact />', '')

    with open(filepath, "w", encoding="utf-8") as f:
        f.write(content)
print("Removed old Contact sections.")
