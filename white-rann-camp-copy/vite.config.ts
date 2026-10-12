import tailwindcss from "@tailwindcss/vite";
import react from "@vitejs/plugin-react";
import path from "node:path";
import { defineConfig } from "vite";

/*
 * White Rann Camp website: a separate site from Kutch Safari Resort (../frontend).
 * It has no node_modules of its own yet: packages resolve from the parent folder's
 * node_modules (same versions as package.json). Run `pnpm install` here to make it
 * stand alone, e.g. when it moves to its own repository.
 */
export default defineConfig({
  root: import.meta.dirname,
  plugins: [react(), tailwindcss()],
  resolve: {
    alias: { "@": path.resolve(import.meta.dirname, "src") },
  },
  build: {
    outDir: path.resolve(import.meta.dirname, "dist"),
    emptyOutDir: true,
  },
  server: {
    port: 4401,
    strictPort: true,
    // Allow the shared node_modules one folder up.
    fs: { allow: [path.resolve(import.meta.dirname, "..")] },
  },
});
