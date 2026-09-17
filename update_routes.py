import os

app_path = "client/src/App.tsx"

with open(app_path, "r", encoding="utf-8") as f:
    content = f.read()

if "RannUtsavPackage" not in content:
    content = content.replace(
        'import Destination from "./pages/Destination";',
        'import Destination from "./pages/Destination";\nimport RannUtsavPackage from "./pages/RannUtsavPackage";'
    )
    content = content.replace(
        '<Route path={"/destination/:slug"} component={Destination} />',
        '<Route path={"/destination/:slug"} component={Destination} />\n      <Route path={"/rann-utsav-package"} component={RannUtsavPackage} />'
    )
    with open(app_path, "w", encoding="utf-8") as f:
        f.write(content)
    print("App.tsx updated.")
else:
    print("App.tsx already has RannUtsavPackage.")
