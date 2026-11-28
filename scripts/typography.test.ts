import assert from "node:assert/strict";
import test from "node:test";
import { typographyClasses } from "../astro/src/lib/blocks/typography.ts";

test("colour, size, and weight become the same classes the editor sets", () => {
  assert.deepEqual(
    typographyClasses({
      text_colour: { value: "var(--color-secondary-on-primary)", name: "Red on navy", slug: "secondary-on-primary" },
      text_size: { value: "fs-h2", label: "Heading" },
      text_weight: { value: "fw-700", label: "Bold" },
    }),
    ["has-text-secondary-on-primary", "fs-h2", "fw-700"],
  );
});

test("a var(--color) string and a legacy slug still map to a text class", () => {
  assert.deepEqual(typographyClasses({ text_colour: "var(--color-primary)" }), ["has-text-primary"]);
  assert.deepEqual(typographyClasses({ text_colour: "navy" }), ["has-text-navy"]);
  assert.deepEqual(typographyClasses({ text_size: "fs-display", text_weight: "fw-500" }), [
    "fs-display",
    "fw-500",
  ]);
});

test("empty and unknown extension values add no class", () => {
  assert.deepEqual(typographyClasses({ text_colour: "", text_size: "", text_weight: { value: "" } }), []);
  assert.deepEqual(typographyClasses({ text_size: "font-bold", text_weight: "h2" }), []);
});
