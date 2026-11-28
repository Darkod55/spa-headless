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

    const editorCss = builtEditorCss();
    const editorHtml = `<!doctype html>
      <style>${editorCss}</style>
      <div class="editor-styles-wrapper">
        <section class="hero hero--main hero--bg-navy">
          <div class="hero__copy">
            <div class="block-editor-block-list__layout">
              <h1 class="wp-block-heading block-editor-rich-text__editable" style="color: var(--color-secondary-on-tertiary);">Why fit in when you can <mark style="background-color:rgba(0, 0, 0, 0)" class="has-inline-color has-secondary-on-primary-color">stand out?</mark></h1>
              <p class="wp-block-paragraph has-text-secondary">A paragraph with text colour secondary.</p>
              <h2 class="wp-block-heading block-editor-rich-text__editable">Uncoloured hero heading</h2>
            </div>
          </div>
        </section>
        <h2 class="wp-block-heading has-text-secondary">Heading outside the hero</h2>
      </div>`;
    const server = createServer((_req, res) => {
      res.setHeader("content-type", "text/html; charset=utf-8");
      res.end(editorHtml);
    });
    const port = await listen(server);
    try {
      await page.goto(`http://127.0.0.1:${port}/`);
      assert.equal(
        await colorOf(page, ".editor-styles-wrapper > .hero.hero--bg-navy .hero__copy .block-editor-block-list__layout > h1"),
        "#ff8a9a",
      );
      assert.equal(await colorOf(page, ".editor-styles-wrapper h1 mark"), "#e8324c");
      assert.equal(await colorOf(page, ".editor-styles-wrapper .hero__copy p"), "#c8102e");
      assert.equal(await colorOf(page, ".editor-styles-wrapper .hero__copy h2"), "#ffffff");
      assert.equal(await colorOf(page, ".editor-styles-wrapper > h2"), "#c8102e");
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
