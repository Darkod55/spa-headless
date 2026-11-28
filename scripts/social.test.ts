import assert from "node:assert/strict";
import test from "node:test";
import { socialAriaLabel, socialPlatformFromUrl } from "../astro/src/lib/wp/social.ts";

test("social hosts map to platforms, including subdomains", () => {
  assert.equal(socialPlatformFromUrl("https://www.facebook.com/thesquarepegagency"), "facebook");
  assert.equal(socialPlatformFromUrl("https://m.facebook.com/thesquarepegagency"), "facebook");
  assert.equal(socialPlatformFromUrl("https://fb.com/thesquarepegagency"), "facebook");
  assert.equal(socialPlatformFromUrl("https://www.instagram.com/thesquarepegagency/"), "instagram");
  assert.equal(socialPlatformFromUrl("https://uk.linkedin.com/company/94275355/"), "linkedin");
  assert.equal(socialPlatformFromUrl("https://x.com/thesquarepegagency"), "x");
  assert.equal(socialPlatformFromUrl("https://twitter.com/thesquarepegagency"), "x");
  assert.equal(socialPlatformFromUrl("https://www.youtube.com/@thesquarepegagency"), "youtube");
  assert.equal(socialPlatformFromUrl("https://youtu.be/abc"), "youtube");
});

test("unknown and broken URLs use the generic link icon", () => {
  assert.equal(socialPlatformFromUrl("https://example.com/hello"), "link");
  assert.equal(socialPlatformFromUrl("not a url"), "link");
  assert.equal(socialPlatformFromUrl("/contact"), "link");
});

test("aria-label prefers the menu title", () => {
  assert.equal(socialAriaLabel("Square Peg on LinkedIn", "linkedin"), "Square Peg on LinkedIn");
  assert.equal(socialAriaLabel("  ", "youtube"), "YouTube");
});
