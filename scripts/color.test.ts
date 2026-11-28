import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import test from "node:test";
import { colorSlug } from "../astro/src/lib/blocks/color.ts";
import { addClassToRoot } from "../astro/src/lib/blocks/markup.ts";

test("colorSlug reads a slug, a CSS variable, and a colour object", () => {
  assert.equal(colorSlug("navy"), "navy");
  assert.equal(colorSlug("var(--color-red)"), "red");
  assert.equal(colorSlug("var(--color-secondary-on-primary)"), "secondary-on-primary");
  assert.equal(colorSlug("primary"), "primary");
  assert.equal(colorSlug({ value: "var(--color-royal)", name: "Royal", slug: "royal" }), "royal");
  assert.equal(colorSlug(null), null);
  assert.equal(colorSlug("lime"), null);
  assert.equal(colorSlug({ slug: "nope" }), null);
});

test("the WordPress palette is only the curated brand colours", () => {
  const theme = JSON.parse(
    readFileSync(new URL("../wordpress/themes/spa-sage/theme.json", import.meta.url), "utf8"),
  ) as { settings: { color: { palette: Array<{ slug: string; color: string }> } } };
  const vite = readFileSync(
    new URL("../wordpress/themes/spa-sage/vite.config.js", import.meta.url),
    "utf8",
  );
  const astro = readFileSync(new URL("../astro/src/styles/global.css", import.meta.url), "utf8");
  const slugs = theme.settings.color.palette.map((entry) => entry.slug);
  assert.deepEqual(slugs, [
    "primary",
    "secondary",
    "tertiary",
    "secondary-deep",
    "secondary-on-primary",
    "secondary-on-tertiary",
    "gray",
    "white",
    "black",
  ]);
  assert.equal(new Set(slugs).size, slugs.length);
  assert.match(vite, /disableTailwindColors:\s*true/);
  for (const slug of slugs) {
    assert.match(astro, new RegExp(`--wp--preset--color--${slug}:`));
    assert.match(astro, new RegExp(`\\.has-${slug}-color`));
  }
  assert.doesNotMatch(astro, /--wp--preset--color--navy:/);
  assert.doesNotMatch(astro, /--wp--preset--color--grey-100:/);
});

test("admin chrome imports the shared colour tokens", () => {
  const canvas = readFileSync(
    new URL("../wordpress/themes/spa-sage/resources/css/editor-canvas.css", import.meta.url),
    "utf8",
  );
  const tokens = readFileSync(
    new URL("../wordpress/themes/spa-sage/resources/css/theme/color-tokens.css", import.meta.url),
    "utf8",
  );
  assert.match(canvas, /@import\s+"\.\/theme\/color-tokens\.css"/);
  assert.match(tokens, /:root,\s*\n\.editor-styles-wrapper/);
  assert.match(tokens, /--color-primary:\s*#031433/);
  assert.match(tokens, /--color-secondary:\s*#c8102e/);
  assert.match(tokens, /--color-secondary-on-primary:\s*#e8324c/);
  assert.doesNotMatch(tokens, /#3a31a7|#dd785c|#fcf7eb|#848585/i);
});

test("addClassToRoot adds a class without disturbing other attributes", () => {
  assert.equal(
    addClassToRoot('<h2 class="wp-block-heading">Title</h2>', "has-text-red"),
    '<h2 class="wp-block-heading has-text-red">Title</h2>',
  );
  assert.equal(
    addClassToRoot(`<p class='lead' data-note="class=keep">Copy</p>`, "has-text-navy"),
    `<p class='lead has-text-navy' data-note="class=keep">Copy</p>`,
  );
  assert.equal(addClassToRoot("<h1>Title</h1>", "has-text-white"), '<h1 class="has-text-white">Title</h1>');
  assert.equal(
    addClassToRoot('<h2 class="has-text-red">Title</h2>', "has-text-red"),
    '<h2 class="has-text-red">Title</h2>',
  );
});
