import assert from "node:assert/strict";
import test from "node:test";
import { buttonLabel, buttonShowsArrow, buttonType, buttonUrl } from "../astro/src/lib/blocks/button.ts";

test("an unsaved button is primary with the arrow on", () => {
  assert.equal(buttonType(undefined), "primary");
  assert.equal(buttonType({ value: "light", label: "Light" }), "light");
  assert.equal(buttonShowsArrow({}), true);
  assert.equal(buttonShowsArrow({ arrow: false }), false);
  assert.equal(buttonShowsArrow({ blockstudio: { attributes: { arrow: false } } }), false);
  assert.equal(buttonShowsArrow({ arrow: true, blockstudio: { attributes: { arrow: false } } }), false);
});

test("a button label and url fall back to the saved link", () => {
  const html = '<div class="wp-block-button"><a class="wp-block-button__link" href="/saved">Saved markup</a></div>';
  assert.equal(buttonLabel({}, html), "Saved markup");
  assert.equal(buttonUrl({}, html), "/saved");
  assert.equal(buttonLabel({ text: "Typed" }, html), "Typed");
});
