import { defineConfig } from "astro/config";
import node from "@astrojs/node";

const site = process.env.PUBLIC_SITE_URL || "http://localhost:4321";

/**
 * Pages are prerendered at build time from WordPress, or from fixtures when
 * WordPress is unreachable. The contact endpoint and draft preview opt out
 * of prerendering and need the Node adapter. Deployment (including a
 * Cloudflare adapter) is intentionally later.
 */
export default defineConfig({
  site,
  trailingSlash: "never",
  adapter: node({ mode: "standalone" }),
  server: {
    port: 4321,
    host: true,
  },
});
