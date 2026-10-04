import express from "express";
import { createServer } from "http";
import path from "path";
import { fileURLToPath } from "url";

// This server only ever runs the built site, so it is production unless told
// otherwise. Set here rather than as "NODE_ENV=production node …" in package.json,
// which Windows' command shell cannot run. Production also stops Express showing
// stack traces to visitors.
process.env.NODE_ENV ??= "production";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
// The project root: one level up from the bundle (dist/index.js), two levels up
// from the source file (backend/server/index.ts).
const projectRoot = path.basename(__dirname) === "dist"
  ? path.resolve(__dirname, "..")
  : path.resolve(__dirname, "..", "..");

async function startServer() {
  const app = express();
  const server = createServer(app);

  // Serve static files from dist/public in production
  const staticPath =
    process.env.NODE_ENV === "production"
      ? path.resolve(__dirname, "public")
      : path.resolve(projectRoot, "dist", "public");

  app.use(express.static(staticPath));
  app.use(express.json());

  // Contact Form API Endpoint
  app.post("/api/contact", async (req, res) => {
    try {
      const fs = await import("fs/promises");
      const dataDir = path.resolve(projectRoot, "data");
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

  // Without this, a busy port ends the process with Node's raw "Unhandled 'error'
  // event" stack trace instead of saying what is wrong.
  server.on("error", (err: NodeJS.ErrnoException) => {
    console.error(err.code === "EADDRINUSE"
      ? `Port ${port} is already in use. Stop whatever is using it, or set the PORT environment variable to a free port and start again.`
      : `Server could not start: ${err.message}`);
    process.exit(1);
  });

  server.listen(port, () => {
    console.log(`Server running on http://localhost:${port}/`);
  });
}

startServer().catch(console.error);
