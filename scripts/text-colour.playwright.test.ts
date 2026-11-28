import assert from "node:assert/strict";
import { spawn, type ChildProcess } from "node:child_process";
import { mkdirSync, readdirSync, readFileSync } from "node:fs";
import { createServer, type Server } from "node:http";
import { dirname, join } from "node:path";
import test from "node:test";
import { fileURLToPath } from "node:url";
import { chromium, type Page } from "playwright";

const root = join(dirname(fileURLToPath(import.meta.url)), "..");
const astroUrl = "http://127.0.0.1:4321/text-colour";

type AstroBadge = {
  hero: { background: string; color: string; size: string; weight: string; spacing: string; padding: string; transform: string; clip: string };
  light: { background: string; color: string };
  gap: string;
  heroText: string;
  lightText: string;
  markupText: string[];
  heading: string;
  motion: { on: boolean; name: string; duration: string; delay: string; second: string; easing: string };
  still: { on: boolean; chars: number; text: string };
};

type EditorBadge = {
  background: string;
  color: string;
  size: string;
  weight: string;
  spacing: string;
  padding: string;
  transform: string;
  animation: string;
  animateClass: boolean;
  clip: string;
  gap: string;
};

type EditorColours = {
  heading: string;
  mark: string;
  paragraph: string;
  plain: string;
  headingSize: string;
  wrapSize: string;
  headingWeight: string;
  wrapWeight: string;
  plainSize: string;
  plainWrapSize: string;
};

function hex(color: string): string {
  const match = color.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
  if (!match) {
    return color;
  }
  return (
    "#" +
    [match[1], match[2], match[3]]
      .map((part) => Number(part).toString(16).padStart(2, "0"))
      .join("")
  );
}

async function colorOf(page: Page, selector: string): Promise<string> {
  const value = await page.locator(selector).evaluate((el) => getComputedStyle(el).color);
  return hex(value);
}

function badgeCss(): string {
  return readFileSync(
    join(root, "wordpress/themes/spa-sage/resources/css/components/spa-badge.css"),
    "utf8",
  ).replace(/@import[^;]+;\s*/, "");
}

function builtEditorCss(): string {
  const dir = join(root, "wordpress/themes/spa-sage/public/build/assets");
  const files = readdirSync(dir).filter((name) => name.startsWith("editor-") && name.endsWith(".css"));
  const ranked = files
    .map((name) => readFileSync(join(dir, name), "utf8"))
    .sort((a, b) => b.length - a.length);
  const css = ranked[0];
  if (!css || !css.includes("--color-secondary:#c8102e") && !css.includes("--color-secondary: #c8102e")) {
    throw new Error("built editor CSS is missing --color-secondary");
  }
  return css;
}

function listen(server: Server): Promise<number> {
  return new Promise((resolve) => {
    server.listen(0, "127.0.0.1", () => {
      const address = server.address();
      if (address && typeof address === "object") {
        resolve(address.port);
      }
    });
  });
}

async function waitFor(url: string, child: ChildProcess | null): Promise<void> {
  const deadline = Date.now() + 30_000;
  let last = "";
  child?.stdout?.on("data", (chunk: Buffer) => {
    last = chunk.toString();
  });
  child?.stderr?.on("data", (chunk: Buffer) => {
    last = chunk.toString();
  });
  while (Date.now() < deadline) {
    if (child && child.exitCode !== null) {
      throw new Error(`Astro exited ${child.exitCode}: ${last}`);
    }
    try {
      const response = await fetch(url);
      if (response.ok) {
        return;
      }
    } catch {
      // Server is still booting.
    }
    await new Promise((resolve) => setTimeout(resolve, 200));
  }
  throw new Error(`Timed out waiting for ${url}. ${last}`);
}

test("a chosen text colour beats the hero and editor defaults", { timeout: 60_000 }, async () => {
  const build = spawn("npm", ["run", "build"], {
    cwd: join(root, "wordpress/themes/spa-sage"),
    stdio: "ignore",
  });
  const built = await new Promise<number>((resolve) => build.on("exit", resolve));
  assert.equal(built, 0, "spa-sage build");

  let astro: ChildProcess | null = null;
  try {
    const existing = await fetch(astroUrl);
    if (!existing.ok) {
      throw new Error("fixture page was not ready");
    }
  } catch {
    astro = spawn(
      "pnpm",
      ["--filter", "@spa/astro", "exec", "astro", "dev", "--host", "127.0.0.1", "--port", "4321"],
      {
        cwd: root,
        env: { ...process.env, WP_FIXTURES: "force", BROWSER: "none" },
        stdio: ["ignore", "pipe", "pipe"],
      },
    );
  }

  const browser = await chromium.launch();
  try {
    await waitFor(astroUrl, astro);
    const page = await browser.newPage();
    await page.goto(astroUrl, { waitUntil: "networkidle" });

    const heading = await page.locator(".hero__copy h1").evaluate((el) => el.outerHTML);
    assert.match(heading, /class="wp-block-heading has-text-secondary-on-tertiary"/);
    assert.doesNotMatch(heading, /<h1[^>]*style=/);
    assert.equal(await colorOf(page, ".hero__copy h1"), "#ff8a9a");
    assert.equal(await colorOf(page, ".hero__copy h1 mark"), "#e8324c");
    assert.equal(await colorOf(page, ".hero__copy p"), "#c8102e");
    assert.equal(await colorOf(page, "article h2"), "#c8102e");
    assert.equal(await colorOf(page, ".hero"), "#ffffff");
    const astroBadge = await page.evaluate<AstroBadge | null>(`(() => {
      const paint = (el) => {
        const style = getComputedStyle(el);
        const match = style.backgroundColor.match(/rgba?\\((\\d+),\\s*(\\d+),\\s*(\\d+)/);
        const color = style.color.match(/rgba?\\((\\d+),\\s*(\\d+),\\s*(\\d+)/);
        const hex = (parts) => parts ? "#" + parts.slice(1, 4).map((part) => Number(part).toString(16).padStart(2, "0")).join("") : "";
        return {
          background: hex(match),
          color: hex(color),
          size: style.fontSize,
          weight: style.fontWeight,
          spacing: style.letterSpacing,
          padding: style.padding,
          transform: style.textTransform,
          clip: style.clipPath,
        };
      };
      const labelOf = (el) => {
        const hidden = el.querySelector(".visually-hidden");
        return (hidden ? hidden.textContent : el.textContent).replace(/\\u00a0/g, " ").trim();
      };
      const hero = document.querySelector(".hero__copy .badge");
      const light = document.querySelector(".badge--bg-gray");
      const copy = document.querySelector(".hero__copy");
      const heading = document.querySelector(".hero__copy h1");
      const still = [...document.querySelectorAll(".badge")].find((el) => el.textContent.includes("Still label"));
      const first = hero && hero.querySelector(".badge__char");
      const second = first && first.nextElementSibling;
      if (!hero || !light || !copy || !heading || !still || !first || !second) return null;
      const firstStyle = getComputedStyle(first);
      const secondStyle = getComputedStyle(second);
      return {
        hero: paint(hero),
        light: paint(light),
        gap: getComputedStyle(copy).rowGap,
        heroText: labelOf(hero),
        lightText: labelOf(light),
        markupText: [...document.querySelectorAll(".badge")].map((el) => labelOf(el)),
        heading: heading.textContent.trim(),
        motion: {
          on: hero.classList.contains("badge--animate"),
          name: firstStyle.animationName,
          duration: firstStyle.animationDuration,
          delay: firstStyle.animationDelay,
          second: secondStyle.animationDelay,
          easing: firstStyle.animationTimingFunction,
        },
        still: { on: still.classList.contains("badge--animate"), chars: still.querySelectorAll(".badge__char").length, text: labelOf(still) },
      };
    })()`);
    if (!astroBadge) {
      throw new Error("the fixture page is missing a badge");
    }
    assert.equal(astroBadge.hero.background, "#c8102e");
    assert.equal(astroBadge.hero.color, "#ffffff");
    assert.equal(astroBadge.hero.size, "16px");
    assert.equal(astroBadge.hero.weight, "500");
    assert.equal(astroBadge.hero.spacing, "1.92px");
    assert.equal(astroBadge.hero.padding, "8px 26px");
    assert.equal(astroBadge.hero.transform, "uppercase");
    assert.notEqual(astroBadge.hero.clip, "none");
    assert.equal(astroBadge.gap, "22px");
    assert.equal(astroBadge.light.background, "#f4f5f7");
    assert.equal(astroBadge.light.color, "#031433");
    assert.equal(astroBadge.heroText, "Different by design");
    assert.equal(astroBadge.lightText, "Different by design");
    assert.ok(astroBadge.markupText.includes("From the saved markup"));
    assert.ok(astroBadge.heading.includes("stand out?"));
    assert.equal(astroBadge.motion.on, true);
    assert.equal(astroBadge.motion.name, "label-char-in");
    assert.equal(astroBadge.motion.duration, "0.55s");
    assert.equal(astroBadge.motion.delay, "0.1s");
    assert.equal(Number(astroBadge.motion.second.replace("s", "")).toFixed(3), "0.132");
    assert.ok(astroBadge.motion.easing === "ease-out" || astroBadge.motion.easing.includes("cubic-bezier"));
    assert.equal(astroBadge.still.on, false);
    assert.equal(astroBadge.still.chars, 0);
    assert.equal(astroBadge.still.text, "Still label");
    await page.emulateMedia({ reducedMotion: "reduce" });
    const reduced = await page.evaluate<{ name: string; opacity: string } | null>(`(() => {
      const ch = document.querySelector(".hero__copy .badge .badge__char");
      if (!ch) return null;
      const style = getComputedStyle(ch);
      return { name: style.animationName, opacity: style.opacity };
    })()`);
    if (!reduced) {
      throw new Error("the animated badge lost its characters");
    }
    assert.equal(reduced.name, "none");
    assert.equal(reduced.opacity, "1");
    await page.emulateMedia({ reducedMotion: "no-preference" });
    const buttons = await page.evaluate<{
      primary: { background: string; color: string; arrow: boolean; target: string; rel: string; text: string };
      light: { background: string; color: string; arrow: boolean };
      dark: { background: string; color: string; arrow: string };
      darkOff: { arrow: boolean };
      outline: { background: string; color: string; shell: string; arrow: boolean };
      outlineOff: { arrow: boolean };
      text: { color: string; clip: string; border: string; arrow: boolean };
      textOff: { arrow: boolean };
      saved: { text: string; arrow: boolean; href: string };
      gap: string;
    } | null>(`(() => {
      const hex = (value) => {
        const match = value.match(/rgba?\\((\\d+),\\s*(\\d+),\\s*(\\d+)/);
        return match ? "#" + [match[1], match[2], match[3]].map((part) => Number(part).toString(16).padStart(2, "0")).join("") : value;
      };
      const paint = (el) => {
        const style = getComputedStyle(el);
        const arrow = el.querySelector(".cta__arrow");
        return {
          background: hex(style.backgroundColor),
          color: hex(style.color),
          clip: style.clipPath,
          border: style.borderBottomWidth,
          arrow: Boolean(arrow),
          arrowColor: arrow ? hex(getComputedStyle(arrow).color) : "",
        };
      };
      const primary = document.querySelector('a.cta--primary[href="/contact"]');
      const light = document.querySelector("a.cta--light");
      const dark = document.querySelector('a.cta--dark[href="#dark"]');
      const darkOff = document.querySelector('a.cta--dark[href="#dark-off"]');
      const outline = document.querySelector('a.cta--outline-light[href="#outline"]');
      const outlineOff = document.querySelector('a.cta--outline-light[href="#outline-off"]');
      const text = document.querySelector('a.cta--text[href="#text"]');
      const textOff = document.querySelector('a.cta--text[href="#text-off"]');
      const saved = document.querySelector('a.cta[href="/saved"]');
      const row = document.querySelector(".hero__copy .wp-block-buttons");
      if (!primary || !light || !dark || !darkOff || !outline || !outlineOff || !text || !textOff || !saved || !row || !outline.parentElement) return null;
      return {
        primary: { ...paint(primary), target: primary.target, rel: primary.rel, text: primary.textContent.trim() },
        light: paint(light),
        dark: { ...paint(dark), arrow: paint(dark).arrowColor },
        darkOff: { arrow: paint(darkOff).arrow },
        outline: { ...paint(outline), shell: hex(getComputedStyle(outline.parentElement).backgroundColor) },
        outlineOff: { arrow: paint(outlineOff).arrow },
        text: paint(text),
        textOff: { arrow: paint(textOff).arrow },
        saved: { text: saved.textContent.trim(), arrow: paint(saved).arrow, href: saved.getAttribute("href") },
        gap: getComputedStyle(row).columnGap || getComputedStyle(row).gap,
      };
    })()`);
    if (!buttons) {
      throw new Error("the fixture page is missing a hero button");
    }
    assert.equal(buttons.primary.background, "#c8102e");
    assert.equal(buttons.primary.color, "#ffffff");
    assert.equal(buttons.primary.arrow, true);
    assert.equal(buttons.primary.target, "_blank");
    assert.equal(buttons.primary.rel, "noopener noreferrer");
    assert.equal(buttons.primary.text, "Book a discovery call");
    assert.equal(buttons.light.background, "#ffffff");
    assert.equal(buttons.light.color, "#031433");
    assert.equal(buttons.light.arrow, false);
    assert.equal(buttons.dark.background, "#031433");
    assert.equal(buttons.dark.color, "#ffffff");
    assert.equal(buttons.dark.arrow, "#ffffff");
    assert.equal(buttons.darkOff.arrow, false);
    assert.equal(buttons.outline.background, "#031433");
    assert.equal(buttons.outline.color, "#ffffff");
    assert.equal(buttons.outline.shell, "#ffffff");
    assert.equal(buttons.outline.arrow, true);
    assert.equal(buttons.outlineOff.arrow, false);
    assert.equal(buttons.text.color, "#c8102e");
    assert.equal(buttons.text.clip, "none");
    assert.equal(buttons.text.border, "2px");
    assert.equal(buttons.text.arrow, true);
    assert.equal(buttons.textOff.arrow, false);
    assert.equal(buttons.saved.text, "Saved markup");
    assert.equal(buttons.saved.arrow, true);
    assert.equal(buttons.saved.href, "/saved");
    assert.equal(buttons.gap, "14px");
    mkdirSync("/opt/cursor/artifacts", { recursive: true });
    await page.locator('a.cta--primary[href="/contact"]').scrollIntoViewIfNeeded();
    const frontPair = await page.evaluate<{ x: number; y: number; width: number; height: number }>(`(() => {
      const a = document.querySelector('a.cta--primary[href="/contact"]').getBoundingClientRect();
      const b = document.querySelector("a.cta--light").getBoundingClientRect();
      const paragraph = document.querySelector(".hero__copy p");
      const left = Math.max(0, (paragraph ? paragraph.getBoundingClientRect().left : Math.min(a.left, b.left)) - 8);
      const top = Math.max(0, Math.min(a.top, b.top) - 16);
      const right = Math.max(a.right, b.right) + 24;
      const bottom = Math.max(a.bottom, b.bottom) + 16;
      return { x: left, y: top, width: Math.min(right, window.innerWidth) - left, height: bottom - top };
    })()`);
    await page.screenshot({ path: "/opt/cursor/artifacts/buttons-front-row.png", clip: frontPair });
    await page.hover('a.cta--primary[href="/contact"]');
    await page.waitForFunction(`(() => {
      const el = document.querySelector('a.cta--primary[href="/contact"]');
      const match = getComputedStyle(el).backgroundColor.match(/rgba?\\((\\d+),\\s*(\\d+),\\s*(\\d+)/);
      const hex = match ? "#" + [match[1], match[2], match[3]].map((part) => Number(part).toString(16).padStart(2, "0")).join("") : "";
      return hex === "#970c23";
    })()`);
    const primaryHover = await page.evaluate<{ background: string; color: string; arrow: string; arrowBg: string; arrowWidth: string; arrowHeight: string }>(`(() => {
      const el = document.querySelector('a.cta--primary[href="/contact"]');
      const arrow = el.querySelector(".cta__arrow");
      const hex = (value) => {
        const match = value.match(/rgba?\\((\\d+),\\s*(\\d+),\\s*(\\d+)/);
        return match ? "#" + [match[1], match[2], match[3]].map((part) => Number(part).toString(16).padStart(2, "0")).join("") : value;
      };
      const style = getComputedStyle(el);
      const arrowStyle = getComputedStyle(arrow);
      return {
        background: hex(style.backgroundColor),
        color: hex(style.color),
        arrow: hex(arrowStyle.color),
        arrowBg: arrowStyle.backgroundColor,
        arrowWidth: arrowStyle.width,
        arrowHeight: arrowStyle.height,
      };
    })()`);
    assert.equal(primaryHover.background, "#970c23");
    assert.equal(primaryHover.color, "#ffffff");
    assert.equal(primaryHover.arrow, "#ffffff");
    assert.equal(primaryHover.arrowBg, "rgba(0, 0, 0, 0)");
    assert.equal(primaryHover.arrowWidth, "20px");
    assert.equal(primaryHover.arrowHeight, "12px");
    const frontHover = await page.evaluate<{ x: number; y: number; width: number; height: number }>(`(() => {
      const a = document.querySelector('a.cta--primary[href="/contact"]').getBoundingClientRect();
      const left = Math.max(0, a.left - 16);
      const top = Math.max(0, a.top - 16);
      return { x: left, y: top, width: Math.min(window.innerWidth, a.right + 24) - left, height: a.bottom + 16 - top };
    })()`);
    await page.screenshot({ path: "/opt/cursor/artifacts/buttons-front-hover.png", clip: frontHover });
    await page.hover("a.cta--light");
    await page.waitForFunction(`(() => {
      const el = document.querySelector("a.cta--light");
      const match = getComputedStyle(el).backgroundColor.match(/rgba?\\((\\d+),\\s*(\\d+),\\s*(\\d+)/);
      const hex = match ? "#" + [match[1], match[2], match[3]].map((part) => Number(part).toString(16).padStart(2, "0")).join("") : "";
      return hex === "#f4f5f7";
    })()`);
    const lightHover = await page.evaluate<{ background: string; color: string }>(`(() => {
      const el = document.querySelector("a.cta--light");
      const hex = (value) => {
        const match = value.match(/rgba?\\((\\d+),\\s*(\\d+),\\s*(\\d+)/);
        return match ? "#" + [match[1], match[2], match[3]].map((part) => Number(part).toString(16).padStart(2, "0")).join("") : value;
      };
      const style = getComputedStyle(el);
      return { background: hex(style.backgroundColor), color: hex(style.color) };
    })()`);
    assert.equal(lightHover.background, "#f4f5f7");
    assert.equal(lightHover.color, "#031433");

    const editorCss = builtEditorCss();
    const badgeSource = badgeCss();
    /*
      BlockStudio 7.7.15 editor.BlockListBlock (extensions.tsx): style set
      rules become a stylesheet on [data-block], and class set rules become
      the wrapper className. The inner RichText h1 receives neither.
    */
    const editorHtml = `<!doctype html>
      <style>
        a:hover, a:focus, a:active, .wp-element-button:hover { color: #970c23; }
        .block-editor-block-list__layout > .wp-block + .wp-block { margin-left: 90px; }
        .wp-block-button__link, .wp-element-button {
          display: inline-block;
          border-radius: 9999px;
          background: #32373c;
          color: #fff;
          padding: 0.667em 1.333em;
          font-size: 1.125em;
          font-family: sans-serif;
          text-decoration: underline;
        }
        .editor-styles-wrapper .hero__copy .block-editor-block-list__layout {
          display: flex;
          flex-direction: column;
          gap: 22px;
        }
        .editor-styles-wrapper .hero__copy .block-editor-rich-text__editable { color: inherit; }
      </style>
      <style>${editorCss}</style>
      <style>${badgeSource}</style>
      <style>.editor-styles-wrapper [data-block="heading-1"] { color: var(--color-secondary-on-tertiary) }</style>
      <style>.editor-styles-wrapper [data-block="para-1"] { color: var(--color-secondary) }</style>
      <div class="editor-styles-wrapper">
        <section class="hero hero--main hero--bg-navy">
          <div class="hero__copy">
            <div class="block-editor-block-list__layout">
              <div class="wp-block" data-block="badge-1" data-type="spa/badge">
                <span class="badge badge--bg-secondary badge--text-white"><span class="badge__text block-editor-rich-text__editable">Different by design</span></span>
              </div>
              <div class="wp-block has-text-secondary-on-tertiary fs-h2 fw-700" data-block="heading-1" data-type="core/heading">
                <h1 class="wp-block-heading block-editor-rich-text__editable">Why fit in when you can <mark style="background-color:rgba(0, 0, 0, 0)" class="has-inline-color has-secondary-on-primary-color">stand out?</mark></h1>
              </div>
              <div class="wp-block has-text-secondary" data-block="para-1" data-type="core/paragraph">
                <p class="wp-block-paragraph block-editor-rich-text__editable">A paragraph with text colour secondary.</p>
              </div>
              <div class="wp-block wp-block-buttons" data-type="core/buttons">
                <div class="block-editor-inner-blocks">
                  <div class="block-editor-block-list__layout">
                    <div class="wp-block cta cta--primary cta--arrow" data-type="core/button" data-button="primary">
                      <div class="wp-block-button"><a class="wp-block-button__link wp-element-button block-editor-rich-text__editable" href="/contact">Book a discovery call</a></div>
                    </div>
                    <div class="wp-block cta cta--light cta--no-arrow" data-type="core/button" data-button="light-off">
                      <div class="wp-block-button"><a class="wp-block-button__link wp-element-button block-editor-rich-text__editable" href="/services">How we help</a></div>
                    </div>
                    <div class="wp-block cta cta--light cta--arrow" data-type="core/button" data-button="light">
                      <div class="wp-block-button"><a class="wp-block-button__link wp-element-button block-editor-rich-text__editable">Light</a></div>
                    </div>
                    <div class="wp-block cta cta--dark cta--arrow" data-type="core/button" data-button="dark">
                      <div class="wp-block-button"><a class="wp-block-button__link wp-element-button block-editor-rich-text__editable">Dark</a></div>
                    </div>
                    <div class="wp-block cta cta--outline-light cta--arrow" data-type="core/button" data-button="outline">
                      <div class="wp-block-button"><a class="wp-block-button__link wp-element-button block-editor-rich-text__editable">Outline</a></div>
                    </div>
                    <div class="wp-block cta cta--text cta--no-arrow" data-type="core/button" data-button="text">
                      <div class="wp-block-button"><a class="wp-block-button__link wp-element-button block-editor-rich-text__editable">Text</a></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="wp-block" data-block="heading-2" data-type="core/heading">
                <h2 class="wp-block-heading block-editor-rich-text__editable">Uncoloured hero heading</h2>
              </div>
            </div>
          </div>
        </section>
      </div>`;
    const server = createServer((_req, res) => {
      res.setHeader("content-type", "text/html; charset=utf-8");
      res.end(editorHtml);
    });
    const port = await listen(server);
    try {
      await page.goto(`http://127.0.0.1:${port}/`);
      const editorColours = await page.evaluate<EditorColours | null>(`(() => {
        const read = (el) => {
          const style = getComputedStyle(el);
          const match = style.color.match(/rgba?\\((\\d+),\\s*(\\d+),\\s*(\\d+)/);
          const color = match
            ? "#" + [match[1], match[2], match[3]].map((part) => Number(part).toString(16).padStart(2, "0")).join("")
            : style.color;
          return { color, size: style.fontSize, weight: style.fontWeight };
        };
        const heading = document.querySelector("[data-block='heading-1'] h1");
        const paragraph = document.querySelector("[data-block='para-1'] p");
        const plain = document.querySelector("[data-block='heading-2'] h2");
        const mark = heading && heading.querySelector("mark");
        if (!heading || !heading.parentElement || !paragraph || !plain || !plain.parentElement || !mark) {
          return null;
        }
        const headingStyle = read(heading);
        const wrapStyle = read(heading.parentElement);
        const plainStyle = read(plain);
        const plainWrap = read(plain.parentElement);
        const markStyle = read(mark);
        return {
          heading: headingStyle.color,
          mark: markStyle.color,
          paragraph: read(paragraph).color,
          plain: plainStyle.color,
          headingSize: headingStyle.size,
          wrapSize: wrapStyle.size,
          headingWeight: headingStyle.weight,
          wrapWeight: wrapStyle.weight,
          plainSize: plainStyle.size,
          plainWrapSize: plainWrap.size,
        };
      })()`);
      if (!editorColours) {
        throw new Error("editor fixture is missing a block");
      }
      assert.equal(editorColours.heading, "#ff8a9a");
      assert.equal(editorColours.mark, "#e8324c");
      assert.equal(editorColours.paragraph, "#c8102e");
      assert.equal(editorColours.plain, "#ffffff");
      assert.equal(editorColours.headingSize, editorColours.wrapSize);
      assert.equal(editorColours.headingWeight, editorColours.wrapWeight);
      assert.notEqual(editorColours.plainSize, editorColours.plainWrapSize);
      const editorBadge = await page.evaluate<EditorBadge | null>(`(() => {
        const hex = (value) => {
          const match = value.match(/rgba?\\((\\d+),\\s*(\\d+),\\s*(\\d+)/);
          return match ? "#" + [match[1], match[2], match[3]].map((part) => Number(part).toString(16).padStart(2, "0")).join("") : value;
        };
        const badge = document.querySelector("[data-block='badge-1'] .badge");
        const label = badge && badge.querySelector(".block-editor-rich-text__editable");
        const list = document.querySelector(".hero__copy .block-editor-block-list__layout");
        if (!badge || !label || !list) return null;
        const shell = getComputedStyle(badge);
        const type = getComputedStyle(label);
        return {
          background: hex(shell.backgroundColor),
          color: hex(type.color),
          size: type.fontSize,
          weight: type.fontWeight,
          spacing: type.letterSpacing,
          padding: shell.padding,
          transform: type.textTransform,
          clip: shell.clipPath,
          gap: getComputedStyle(list).rowGap,
          animation: type.animationName,
          animateClass: badge.classList.contains("badge--animate"),
        };
      })()`);
      if (!editorBadge) {
        throw new Error("the editor fixture is missing a badge");
      }
      assert.equal(editorBadge.background, "#c8102e");
      assert.equal(editorBadge.color, "#ffffff");
      assert.equal(editorBadge.size, "16px");
      assert.equal(editorBadge.weight, "500");
      assert.equal(editorBadge.spacing, "1.92px");
      assert.equal(editorBadge.padding, "8px 26px");
      assert.equal(editorBadge.transform, "uppercase");
      assert.notEqual(editorBadge.clip, "none");
      assert.equal(editorBadge.gap, "22px");
      assert.equal(editorBadge.animation, "none");
      assert.equal(editorBadge.animateClass, false);
      assert.equal(editorCss.includes("label-char-in"), false);
      const editorButtons = await page.evaluate<{
        primary: { background: string; color: string; arrow: string };
        light: { background: string; color: string; arrow: string };
        lightOff: { arrow: string };
        dark: { background: string; color: string };
        outline: { background: string; color: string; shell: string };
        text: { color: string; clip: string };
      } | null>(`(() => {
        const hex = (value) => {
          const match = value.match(/rgba?\\((\\d+),\\s*(\\d+),\\s*(\\d+)/);
          return match ? "#" + [match[1], match[2], match[3]].map((part) => Number(part).toString(16).padStart(2, "0")).join("") : value;
        };
        const read = (name) => {
          const wrap = document.querySelector('[data-button="' + name + '"]');
          const link = wrap && wrap.querySelector(".wp-block-button__link");
          if (!wrap || !link) return null;
          const style = getComputedStyle(link);
          const after = getComputedStyle(link, "::after");
          return {
            background: hex(style.backgroundColor),
            color: hex(style.color),
            clip: style.clipPath,
            shell: hex(getComputedStyle(wrap).backgroundColor),
            arrow: after.content,
            arrowColor: hex(after.backgroundColor),
          };
        };
        const primary = read("primary");
        const light = read("light");
        const lightOff = read("light-off");
        const dark = read("dark");
        const outline = read("outline");
        const text = read("text");
        if (!primary || !light || !lightOff || !dark || !outline || !text) return null;
        return {
          primary: { background: primary.background, color: primary.color, arrow: primary.arrowColor },
          light: { background: light.background, color: light.color, arrow: light.arrowColor },
          lightOff: { arrow: lightOff.arrow },
          dark: { background: dark.background, color: dark.color },
          outline: { background: outline.background, color: outline.color, shell: outline.shell },
          text: { color: text.color, clip: text.clip },
        };
      })()`);
      if (!editorButtons) {
        throw new Error("the editor fixture is missing a button");
      }
      assert.equal(editorButtons.primary.background, "#c8102e");
      assert.equal(editorButtons.primary.color, "#ffffff");
      assert.equal(editorButtons.primary.arrow, "#ffffff");
      assert.equal(editorButtons.light.background, "#ffffff");
      assert.equal(editorButtons.light.color, "#031433");
      assert.equal(editorButtons.light.arrow, "#c8102e");
      assert.equal(editorButtons.lightOff.arrow, "none");
      assert.equal(editorButtons.dark.background, "#031433");
      assert.equal(editorButtons.dark.color, "#ffffff");
      assert.equal(editorButtons.outline.background, "#031433");
      assert.equal(editorButtons.outline.color, "#ffffff");
      assert.equal(editorButtons.outline.shell, "#ffffff");
      assert.equal(editorButtons.text.color, "#c8102e");
      assert.equal(editorButtons.text.clip, "none");
      const editorShape = await page.evaluate<{
        padding: string;
        font: string;
        size: string;
        weight: string;
        clip: boolean;
        direction: string;
        gap: string;
        wrap: string;
        primaryWidth: number;
        lightWidth: number;
        rowWidth: number;
        sameRow: boolean;
        offset: number;
      }>(`(() => {
        const link = document.querySelector('[data-button="primary"] .wp-block-button__link');
        const help = document.querySelector('[data-button="light-off"] .wp-block-button__link');
        const light = document.querySelector('[data-button="light"] .wp-block-button__link');
        const row = document.querySelector('[data-type="core/buttons"] .block-editor-block-list__layout');
        const style = getComputedStyle(link);
        const rowStyle = getComputedStyle(link.closest('[data-type="core/button"]').parentElement);
        const a = link.getBoundingClientRect();
        const b = help.getBoundingClientRect();
        return {
          padding: style.padding,
          font: style.fontFamily,
          size: style.fontSize,
          weight: String(style.fontWeight),
          clip: style.clipPath !== "none",
          direction: rowStyle.flexDirection,
          gap: rowStyle.gap,
          wrap: rowStyle.flexWrap,
          primaryWidth: a.width,
          lightWidth: light.getBoundingClientRect().width,
          rowWidth: row.getBoundingClientRect().width,
          sameRow: Math.abs(a.top - b.top) < 2,
          offset: Math.round(b.left - a.right),
        };
      })()`);
      assert.equal(editorShape.padding, "0px 34px");
      assert.match(editorShape.font, /Oswald/i);
      assert.equal(editorShape.size, "16px");
      assert.equal(editorShape.weight, "500");
      assert.equal(editorShape.clip, true);
      assert.equal(editorShape.direction, "row");
      assert.equal(editorShape.wrap, "nowrap");
      assert.equal(editorShape.gap, "14px");
      assert.equal(editorShape.sameRow, true);
      assert.ok(editorShape.offset >= 10 && editorShape.offset <= 20);
      await page.locator('[data-button="primary"]').scrollIntoViewIfNeeded();
      const editorPair = await page.evaluate<{ x: number; y: number; width: number; height: number }>(`(() => {
        const a = document.querySelector('[data-button="primary"]').getBoundingClientRect();
        const b = document.querySelector('[data-button="light-off"]').getBoundingClientRect();
        const left = Math.max(0, Math.min(a.left, b.left) - 16);
        const top = Math.max(0, Math.min(a.top, b.top) - 16);
        const right = Math.min(window.innerWidth, Math.max(a.right, b.right) + 24);
        const bottom = Math.max(a.bottom, b.bottom) + 16;
        return { x: left, y: top, width: right - left, height: bottom - top };
      })()`);
      await page.screenshot({ path: "/opt/cursor/artifacts/buttons-editor-row.png", clip: editorPair });
      assert.ok(editorShape.primaryWidth < editorShape.rowWidth * 0.8);
      assert.ok(editorShape.lightWidth < editorShape.primaryWidth);
      await page.hover('[data-button="primary"] .wp-block-button__link');
      await page.waitForFunction(`(() => {
        const link = document.querySelector('[data-button="primary"] .wp-block-button__link');
        const match = getComputedStyle(link).backgroundColor.match(/rgba?\\((\\d+),\\s*(\\d+),\\s*(\\d+)/);
        const hex = match ? "#" + [match[1], match[2], match[3]].map((part) => Number(part).toString(16).padStart(2, "0")).join("") : "";
        return hex === "#970c23";
      })()`);
      const editorHover = await page.evaluate<{ background: string; color: string; arrow: string; arrowWidth: string; arrowHeight: string; mask: string }>(`(() => {
        const link = document.querySelector('[data-button="primary"] .wp-block-button__link');
        const after = getComputedStyle(link, "::after");
        const hex = (value) => {
          const match = value.match(/rgba?\\((\\d+),\\s*(\\d+),\\s*(\\d+)/);
          return match ? "#" + [match[1], match[2], match[3]].map((part) => Number(part).toString(16).padStart(2, "0")).join("") : value;
        };
        const style = getComputedStyle(link);
        return {
          background: hex(style.backgroundColor),
          color: hex(style.color),
          arrow: hex(after.backgroundColor),
          arrowWidth: after.width,
          arrowHeight: after.height,
          mask: after.webkitMaskImage || after.maskImage,
        };
      })()`);
      assert.equal(editorHover.background, "#970c23");
      assert.equal(editorHover.color, "#ffffff");
      assert.equal(editorHover.arrow, "#ffffff");
      assert.equal(editorHover.arrowWidth, "20px");
      assert.equal(editorHover.arrowHeight, "12px");
      assert.notEqual(editorHover.mask, "none");
      const editorHoverShot = await page.evaluate<{ x: number; y: number; width: number; height: number }>(`(() => {
        const a = document.querySelector('[data-button="primary"]').getBoundingClientRect();
        const left = Math.max(0, a.left - 16);
        const top = Math.max(0, a.top - 16);
        return { x: left, y: top, width: Math.min(window.innerWidth, a.right + 24) - left, height: a.bottom + 16 - top };
      })()`);
      await page.screenshot({ path: "/opt/cursor/artifacts/buttons-editor-hover.png", clip: editorHoverShot });
    } finally {
      server.close();
    }
  } finally {
    await browser.close();
    if (astro?.pid) {
      astro.kill("SIGTERM");
    }
  }
});
