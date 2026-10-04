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

type Enquiry = { name: string; phone: string; email: string; message: string };

/** The fields of a contact-form enquiry, checked; or what is wrong with it. */
export function checkEnquiry(body: unknown): Enquiry | { error: string } {
  const b = (body && typeof body === "object" ? body : {}) as Record<string, unknown>;
  const text = (k: string, max: number) => (typeof b[k] === "string" ? (b[k] as string).trim().slice(0, max) : "");
  const e: Enquiry = { name: text("name", 120), phone: text("phone", 20), email: text("email", 160), message: text("message", 3000) };
  if (!e.name) return { error: "Please give your name." };
  if (!e.phone && !e.email) return { error: "Please give a phone number or an email address." };
  if (e.phone && !/^\+?\d{7,15}$/.test(e.phone.replace(/[\s\-().]/g, ""))) return { error: "That phone number does not look right." };
  if (e.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e.email)) return { error: "That email address does not look right." };
  if (!e.message) return { error: "Please write a message." };
  return e;
}

async function startServer() {
  const app = express();
  const server = createServer(app);

  // Serve static files from dist/public in production
  const staticPath =
    process.env.NODE_ENV === "production"
      ? path.resolve(__dirname, "public")
      : path.resolve(projectRoot, "dist", "public");

  app.use(express.static(staticPath));
  app.use(express.json({ limit: "20kb" }));

  // Contact Form API Endpoint
  app.post("/api/contact", async (req, res) => {
    // Only a real enquiry is stored, and only its known fields: an empty or junk post
    // used to be saved and answered "Enquiry saved successfully".
    const enquiry = checkEnquiry(req.body);
    if ("error" in enquiry) {
      res.status(400).json({ success: false, message: enquiry.error });
      return;
    }
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

      enquiries.push({ ...enquiry, submittedAt: new Date().toISOString() });

      // Save back to file
      await fs.writeFile(filePath, JSON.stringify(enquiries, null, 2));
      // No name, phone, email or message in the server log: that is personal data.
      console.log(`New enquiry saved (${enquiries.length} in data/enquiries.json).`);

      res.status(200).json({ success: true, message: "Enquiry saved successfully" });
    } catch (error) {
      console.error("Error saving enquiry:", error instanceof Error ? error.message : error);
      res.status(500).json({ success: false, message: "Server error" });
    }
  });
  app.all("/api/contact", (_req, res) => {
    res.status(405).set("Allow", "POST").json({ success: false, message: "POST required." });
  });

  // Handle client-side routing - serve index.html for all routes. A path that names
  // a file (/assets/photo.jpg) and was not found above is a real 404: answering with
  // the page made a missing image "load" as HTML.
  app.get("*", (req, res) => {
    if (path.extname(req.path) !== "") {
      res.status(404).type("text/plain").send("Not found");
      return;
    }
    res.sendFile(path.join(staticPath, "index.html"));
  });

  // A request body that is not valid JSON (or too large): a short JSON answer, and no
  // stack trace in the server log for what is only a bad request.
  app.use((err: { type?: string; status?: number; message?: string }, _req: express.Request, res: express.Response, next: express.NextFunction) => {
    if (res.headersSent) return next(err);
    const status = err.status && err.status >= 400 && err.status < 500 ? err.status : 500;
    if (status === 500) console.error("Server error:", err.message);
    res.status(status).json({ success: false, message: status === 500 ? "Server error" : "That request could not be read." });
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
