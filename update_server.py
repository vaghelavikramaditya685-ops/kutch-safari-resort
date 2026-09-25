import os

with open("server/index.ts", "r", encoding="utf-8") as f:
    content = f.read()

booking_api_code = """  // Booking API Endpoints
  app.post("/api/booking/create", async (req, res) => {
    try {
      const fs = await import("fs/promises");
      const dataDir = path.resolve(__dirname, "..", "data");
      const filePath = path.join(dataDir, "bookings.json");
      
      await fs.mkdir(dataDir, { recursive: true });
      
      let bookings = [];
      try {
        const fileContent = await fs.readFile(filePath, "utf-8");
        bookings = JSON.parse(fileContent);
      } catch (err) {
        // file might not exist yet
      }
      
      const newBooking = {
        ...req.body,
        id: "KSR-2026-" + Math.floor(10000 + Math.random() * 90000),
        status: "CONFIRMED",
        createdAt: new Date().toISOString()
      };
      
      bookings.push(newBooking);
      await fs.writeFile(filePath, JSON.stringify(bookings, null, 2));
      console.log("New booking confirmed:", newBooking.id);
      
      res.status(200).json({ success: true, booking: newBooking });
    } catch (error) {
      console.error("Error creating booking:", error);
      res.status(500).json({ success: false, message: "Server error" });
    }
  });

  app.get("/api/booking/list", async (_req, res) => {
    try {
      const fs = await import("fs/promises");
      const filePath = path.resolve(__dirname, "..", "data", "bookings.json");
      try {
        const fileContent = await fs.readFile(filePath, "utf-8");
        res.status(200).json(JSON.parse(fileContent));
      } catch (err) {
        res.status(200).json([]);
      }
    } catch (error) {
      res.status(500).json({ error: "Failed to read bookings" });
    }
  });

"""

content = content.replace('  // Contact Form API Endpoint', booking_api_code + '  // Contact Form API Endpoint')

with open("server/index.ts", "w", encoding="utf-8") as f:
    f.write(content)

print("Updated server/index.ts with booking API endpoints.")
