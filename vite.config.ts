import tailwindcss from "@tailwindcss/vite";
import react from "@vitejs/plugin-react";
import path from "node:path";
import { defineConfig } from "vite";

/** Where `pnpm dev:book` serves the PHP booking engine (backend/booking-engine/). */
const BOOKING_ENGINE_DEV = "http://127.0.0.1:8080";

export default defineConfig({
  plugins: [react(), tailwindcss()],
  resolve: {
    alias: {
      "@": path.resolve(import.meta.dirname, "frontend", "src"),
    },
  },
  envDir: path.resolve(import.meta.dirname),
  root: path.resolve(import.meta.dirname, "frontend"),
  build: {
    outDir: path.resolve(import.meta.dirname, "dist/public"),
    emptyOutDir: true,
  },
  server: {
    port: 3000,
    strictPort: false, // Will find next available port if 3000 is busy
    host: true,
    fs: {
      strict: true,
      deny: ["**/.*"],
    },
    // The engine uses relative paths (assets/…, api/…), so it runs unchanged under /book/.
    proxy: {
      "/book/": {
        target: BOOKING_ENGINE_DEV,
        rewrite: (p) => p.replace(/^\/book/, ""),
      },
    },
  },
});
