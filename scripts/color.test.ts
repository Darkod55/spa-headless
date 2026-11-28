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

test("admin chrome defines the brand variables the sidebar swatches resolve", () => {
  const css = readFileSync(
    new URL("../wordpress/themes/spa-sage/resources/css/editor-canvas.css", import.meta.url),
    "utf8",
  );
  assert.match(css, /--color-primary:\s*#031433/);
  assert.match(css, /--color-secondary:\s*#c8102e/);
  assert.match(css, /--color-tertiary:\s*#012169/);
  assert.match(css, /--color-secondary-on-primary:\s*#e8324c/);
  assert.match(css, /--color-secondary-deep:\s*#970c23/);
  assert.match(css, /--color-gray:\s*#f4f5f7/);
  assert.doesNotMatch(css, /#3a31a7|#dd785c|#fcf7eb|#848585/i);
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
