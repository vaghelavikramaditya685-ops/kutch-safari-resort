import json

data = {
  "rewrites": [
    { "source": "/(.*)", "destination": "/index.html" }
  ]
}

with open("client/public/vercel.json", "w", encoding="utf-8") as f:
    json.dump(data, f)
print("Saved clean vercel.json")
