import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import test from "node:test";
import { heroBackgroundClass, heroBackgrounds } from "../astro/src/lib/blocks/hero-banner.ts";

const css = readFileSync(
  new URL("../wordpress/themes/spa-headless/assets/hero-banner.css", import.meta.url),
  "utf8",
);

const colours: Record<(typeof heroBackgrounds)[number], Record<string, string>> = {
  navy: { "--hero-bg": "#031433", "--hero-shape-1": "#012169", "--hero-shape-2": "#c8102e", "--hero-text": "#ffffff" },
  red: { "--hero-bg": "#c8102e", "--hero-shape-1": "#031433", "--hero-shape-2": "#012169", "--hero-text": "#ffffff" },
  royal: { "--hero-bg": "#012169", "--hero-shape-1": "#031433", "--hero-shape-2": "#c8102e", "--hero-text": "#ffffff" },
  black: { "--hero-bg": "#000000", "--hero-shape-1": "#012169", "--hero-shape-2": "#c8102e", "--hero-text": "#ffffff" },
  white: { "--hero-bg": "#ffffff", "--hero-shape-1": "#012169", "--hero-shape-2": "#c8102e", "--hero-text": "#031433" },
};

test("background values map to modifier classes", () => {
  assert.equal(heroBackgroundClass("navy"), "hero--bg-navy");
  assert.equal(heroBackgroundClass("red"), "hero--bg-red");
  assert.equal(heroBackgroundClass("royal"), "hero--bg-royal");
  assert.equal(heroBackgroundClass("black"), "hero--bg-black");
  assert.equal(heroBackgroundClass("white"), "hero--bg-white");
  assert.equal(heroBackgroundClass("var(--color-black)"), "hero--bg-black");
  assert.equal(heroBackgroundClass({ slug: "white", value: "var(--color-white)" }), "hero--bg-white");
  assert.equal(heroBackgroundClass(undefined), "hero--bg-navy");
  assert.equal(heroBackgroundClass("lime"), "hero--bg-navy");
});

test("each background class sets bg, both shapes, and readable text", () => {
  for (const name of heroBackgrounds) {
    const start = css.indexOf(`.hero--bg-${name} {`);
    assert.ok(start >= 0, `missing .hero--bg-${name}`);
    const block = css.slice(start, css.indexOf("}", start));
    for (const [prop, value] of Object.entries(colours[name])) {
      assert.ok(block.includes(`${prop}: ${value}`), `${name} should set ${prop} to ${value}`);
    }
  }
  assert.ok(css.includes("--hero-text: #031433"), "white backgrounds keep dark text");
  assert.equal(colours.white["--hero-text"], "#031433");
});
