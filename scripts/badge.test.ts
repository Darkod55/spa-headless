import assert from "node:assert/strict";
import test from "node:test";
import { badgeClasses } from "../astro/src/lib/blocks/badge.ts";

test("a badge defaults to the red eyebrow with white type", () => {
  assert.deepEqual(badgeClasses(undefined, undefined), ["badge", "badge--bg-secondary", "badge--text-white"]);
});

test("white type on a white or gray badge becomes navy", () => {
  assert.deepEqual(badgeClasses("white", "white"), ["badge", "badge--bg-white", "badge--text-primary"]);
  assert.deepEqual(badgeClasses({ slug: "gray" }, { value: "var(--color-white)", slug: "white" }), [
    "badge",
    "badge--bg-gray",
    "badge--text-primary",
  ]);
});

test("an explicit text colour is kept, including on a light badge", () => {
  assert.deepEqual(badgeClasses("gray", "secondary"), ["badge", "badge--bg-gray", "badge--text-secondary"]);
  assert.deepEqual(badgeClasses("var(--color-primary)", "var(--color-secondary-on-primary)"), [
    "badge",
    "badge--bg-primary",
    "badge--text-secondary-on-primary",
  ]);
});
