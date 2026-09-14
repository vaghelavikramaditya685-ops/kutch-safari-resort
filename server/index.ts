import express from "express";
import { createServer } from "http";
import path from "path";
import { fileURLToPath } from "url";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function startServer() {
  const app = express();
  const server = createServer(app);

  // Serve static files from dist/public in production
  const staticPath =
    process.env.NODE_ENV === "production"
      ? path.resolve(__dirname, "public")
      : path.resolve(__dirname, "..", "dist", "public");

  app.use(express.static(staticPath));
  app.use(express.json());

  // Contact Form API Endpoint
  app.post("/api/contact", async (req, res) => {
    try {
      const fs = await import("fs/promises");
      const dataDir = path.resolve(__dirname, "..", "data");
      const filePath = path.join(dataDir, "enquiries.json");
      
      // Ensure data directory exists
      await fs.mkdir(dataDir, { recursive: true });
      
      // Read existing or create new array
      let enquiries = [];
      try {
        const fileContent = await fs.readFile(filePath, "utf-8");
        enquiries = JSON.parse(fileContent);
      } catch (err) {
        // file might not exist yet, that's ok
      }
      
      // Add new enquiry
      const newEnquiry = {
        ...req.body,
        submittedAt: new Date().toISOString(),
      };
      enquiries.push(newEnquiry);
      
      // Save back to file
      await fs.writeFile(filePath, JSON.stringify(enquiries, null, 2));
      console.log("New enquiry received:", newEnquiry);
      
      res.status(200).json({ success: true, message: "Enquiry saved successfully" });
    } catch (error) {
      console.error("Error saving enquiry:", error);
      res.status(500).json({ success: false, message: "Server error" });
    }
  });

  // Handle client-side routing - serve index.html for all routes
  app.get("*", (_req, res) => {
    res.sendFile(path.join(staticPath, "index.html"));
  });

  const port = process.env.PORT || 3000;

  server.listen(port, () => {
    console.log(`Server running on http://localhost:${port}/`);
  });
}

startServer().catch(console.error);
