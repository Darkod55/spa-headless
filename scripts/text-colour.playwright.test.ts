import assert from "node:assert/strict";
import { spawn, type ChildProcess } from "node:child_process";
import { readdirSync, readFileSync } from "node:fs";
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
};

type EditorBadge = {
  background: string;
  color: string;
  size: string;
  weight: string;
  spacing: string;
  padding: string;
  transform: string;
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
      const hero = document.querySelector(".hero__copy .badge");
      const light = document.querySelector(".badge--bg-gray");
      const copy = document.querySelector(".hero__copy");
      const heading = document.querySelector(".hero__copy h1");
      if (!hero || !light || !copy || !heading) return null;
      return {
        hero: paint(hero),
        light: paint(light),
        gap: getComputedStyle(copy).rowGap,
        heroText: hero.textContent.trim(),
        lightText: light.textContent.trim(),
        markupText: [...document.querySelectorAll(".badge")].map((el) => el.textContent.trim()),
        heading: heading.textContent.trim(),
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

    const editorCss = builtEditorCss();
    const badgeSource = badgeCss();
    /*
      BlockStudio 7.7.15 editor.BlockListBlock (extensions.tsx): style set
      rules become a stylesheet on [data-block], and class set rules become
      the wrapper className. The inner RichText h1 receives neither.
    */
    const editorHtml = `<!doctype html>
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
