import assert from "node:assert/strict";
import test from "node:test";
import { bundledFooterLogo, resolveFooterLogo } from "../astro/src/lib/wp/logo.ts";
import type { SiteLogo } from "../astro/src/lib/wp/menu.ts";

const white: SiteLogo = {
  url: "https://squarepeg-cms.local/wp-content/uploads/logo-white.svg",
  width: 232,
  height: 60,
  alt: "Square Peg white",
  mime: "image/svg+xml",
};

test("a live footer logo is used as returned", () => {
  const resolved = resolveFooterLogo(white, { fixtures: false, title: "The Square Peg Agency" });
  assert.equal(resolved.url, white.url);
  assert.equal(resolved.alt, "Square Peg white");
  assert.equal(resolved.width, 232);
});

test("fixtures mode keeps the bundled white wordmark even when a footer logo is set", () => {
  const resolved = resolveFooterLogo(white, { fixtures: true, title: "The Square Peg Agency" });
  assert.equal(resolved.url, bundledFooterLogo);
  assert.equal(resolved.alt, "The Square Peg Agency");
});

test("no footer logo falls back to the bundled wordmark", () => {
  assert.equal(resolveFooterLogo(null, { fixtures: false }).url, bundledFooterLogo);
  assert.equal(resolveFooterLogo(undefined, { fixtures: false, title: "  " }).alt, "The Square Peg Agency");
});
