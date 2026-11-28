import assert from "node:assert/strict";
import test from "node:test";
import { renderIconTags, siteIconTags, stripHeadIconTags, type SiteIcon } from "../astro/src/lib/wp/icons.ts";

const svg: SiteIcon = {
  mime: "image/svg+xml",
  sizes: {
    "32": "/fixtures/favicon.svg",
    "180": "/fixtures/favicon.svg",
    "192": "/fixtures/favicon.svg",
    "270": "/fixtures/favicon.svg",
  },
};

const png: SiteIcon = {
  mime: "image/png",
  sizes: {
    "32": "https://squarepeg-cms.local/wp-content/uploads/2026/11/icon-32.png",
    "180": "https://squarepeg-cms.local/wp-content/uploads/2026/11/icon-180.png",
    "192": "https://squarepeg-cms.local/wp-content/uploads/2026/11/icon-192.png",
    "270": "https://squarepeg-cms.local/wp-content/uploads/2026/11/icon-270.png",
  },
};

test("an SVG site icon is one link, not four copies of the same file", () => {
  assert.equal(
    renderIconTags(siteIconTags(svg)),
    '<link rel="icon" href="/fixtures/favicon.svg" type="image/svg+xml" />',
  );
});

test("a PNG site icon uses WordPress's 32, 192, 180, and 270 sizes", () => {
  assert.equal(
    renderIconTags(siteIconTags(png)),
    [
      '<link rel="icon" href="https://squarepeg-cms.local/wp-content/uploads/2026/11/icon-32.png" type="image/png" sizes="32x32" />',
      '<link rel="icon" href="https://squarepeg-cms.local/wp-content/uploads/2026/11/icon-192.png" type="image/png" sizes="192x192" />',
      '<link rel="apple-touch-icon" href="https://squarepeg-cms.local/wp-content/uploads/2026/11/icon-180.png" />',
      '<meta name="msapplication-TileImage" content="https://squarepeg-cms.local/wp-content/uploads/2026/11/icon-270.png" />',
    ].join("\n"),
  );
});

test("Rank Math icon tags are removed and theme-color is left alone", () => {
  const head = [
    "<title>About</title>",
    '<link rel="icon" href="https://cms.example/old.png" sizes="32x32" />',
    '<link rel="apple-touch-icon" href="https://cms.example/old-180.png" />',
    '<meta name="theme-color" content="#031433" />',
    '<link rel="canonical" href="https://thesquarepeg.agency/about" />',
  ].join("\n");
  const stripped = stripHeadIconTags(head);
  assert.equal(stripped.includes("old.png"), false);
  assert.equal(stripped.includes("apple-touch-icon"), false);
  assert.equal(stripped.includes('name="theme-color"'), true);
  assert.equal(stripped.includes("canonical"), true);
});
