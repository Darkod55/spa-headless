import assert from "node:assert/strict";
import test from "node:test";
import { badgeAnimates, badgeChars, badgeClasses, badgeText } from "../astro/src/lib/blocks/badge.ts";

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

test("a badge with no saved text uses the markup, then the default", () => {
  assert.equal(badgeText({}), "Different by design");
  assert.equal(badgeText({ text: "" }), "Different by design");
  assert.equal(badgeText({ text: "Saved label" }), "Saved label");
  assert.equal(
    badgeText(
      {},
      '<span class="badge badge--bg-secondary"><span class="badge__text">From the saved markup</span></span>',
    ),
    "From the saved markup",
  );
});

test("an unsaved animate toggle stays on, and an explicit off stays off", () => {
  assert.equal(badgeAnimates(undefined), true);
  assert.equal(badgeAnimates(true), true);
  assert.equal(badgeAnimates(false), false);
  assert.equal(badgeAnimates("false"), false);
  assert.deepEqual(badgeChars("Different by design"), Array.from("Different by design"));
  assert.deepEqual(badgeChars("<span>Hi</span>"), ["H", "i"]);
});

test("an explicit text colour is kept, including on a light badge", () => {
  assert.deepEqual(badgeClasses("gray", "secondary"), ["badge", "badge--bg-gray", "badge--text-secondary"]);
  assert.deepEqual(badgeClasses("var(--color-primary)", "var(--color-secondary-on-primary)"), [
    "badge",
    "badge--bg-primary",
    "badge--text-secondary-on-primary",
  ]);
});
