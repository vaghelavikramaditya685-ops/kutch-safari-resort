import os

filepath = "client/src/App.tsx"

with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

import_statement = 'import Contact from "./pages/Contact";\n'
route_statement = '      <Route path={"/contact"} component={Contact} />\n'

if import_statement not in content:
    content = content.replace('import RannUtsavPackage from "./pages/RannUtsavPackage";', 'import RannUtsavPackage from "./pages/RannUtsavPackage";\n' + import_statement)
    content = content.replace('<Route path={"/rann-utsav-package"} component={RannUtsavPackage} />', '<Route path={"/rann-utsav-package"} component={RannUtsavPackage} />\n' + route_statement)

    with open(filepath, "w", encoding="utf-8") as f:
        f.write(content)
    print("Updated App.tsx with Contact route.")
else:
    print("Contact route already exists in App.tsx")
