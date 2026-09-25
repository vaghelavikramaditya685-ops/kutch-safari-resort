import os

with open("client/src/App.tsx", "r", encoding="utf-8") as f:
    content = f.read()

# Replace or add the white-rann-camp route
content = content.replace('<Route path={"/rann-utsav-package"} component={RannUtsavPackage} />', '<Route path={"/rann-utsav-package"} component={RannUtsavPackage} />\n      <Route path={"/white-rann-camp"} component={RannUtsavPackage} />')

with open("client/src/App.tsx", "w", encoding="utf-8") as f:
    f.write(content)

print("Added /white-rann-camp route to App.tsx")
