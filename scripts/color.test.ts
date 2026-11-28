import assert from "node:assert/strict";
import test from "node:test";
import { colorSlug } from "../astro/src/lib/blocks/color.ts";
import { addClassToRoot } from "../astro/src/lib/blocks/markup.ts";

test("colorSlug reads a slug, a CSS variable, and a colour object", () => {
  assert.equal(colorSlug("navy"), "navy");
  assert.equal(colorSlug("var(--color-red)"), "red");
  assert.equal(colorSlug({ value: "var(--color-royal)", name: "Royal", slug: "royal" }), "royal");
  assert.equal(colorSlug(null), null);
  assert.equal(colorSlug("lime"), null);
  assert.equal(colorSlug({ slug: "nope" }), null);
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
