import assert from "node:assert/strict";
import { execSync } from "node:child_process";
import { readdirSync, readFileSync } from "node:fs";
import { join } from "node:path";
import test from "node:test";

const root = join(import.meta.dirname, "..");

function readCss(dir: string): string {
  return readdirSync(dir)
    .filter((name) => name.endsWith(".css"))
    .map((name) => readFileSync(join(dir, name), "utf8"))
    .join("\n");
}

test("built Astro and editor CSS define the brand colour variables", () => {
  execSync("pnpm --filter @spa/astro build", { cwd: root, stdio: "inherit" });
  execSync("npm run build --prefix wordpress/themes/spa-sage", { cwd: root, stdio: "inherit" });

  const astroCss = readCss(join(root, "astro/dist/client/_astro"));
  const editorCss = readCss(join(root, "wordpress/themes/spa-sage/public/build/assets"));

  for (const css of [astroCss, editorCss]) {
    assert.match(css, /--color-secondary:\s*#c8102e/i);
    assert.match(css, /--color-primary:\s*#031433/i);
    assert.match(css, /--color-secondary-on-primary:\s*#e8324c/i);
    assert.match(css, /--color-navy:\s*var\(--color-primary\)/);
    assert.match(css, /\.has-text-secondary[^{]*\{[^}]*color:\s*var\(--color-secondary\)/);
    assert.match(css, /\.fs-h2[^{]*\{[^}]*font-size:\s*var\(--fs-h2\)/);
    assert.match(css, /\.fw-700[^{]*\{[^}]*font-weight:\s*700/);
  }

  const secondary = /--color-secondary:\s*(#[0-9a-f]{6})/i.exec(astroCss);
  assert.equal(secondary?.[1]?.toLowerCase(), "#c8102e");
  assert.match(
    astroCss,
    /\.has-text-secondary[^{]*\{[^}]*color:\s*var\(--color-secondary\)/,
    "a heading with text colour secondary uses the red token",
  );
});
