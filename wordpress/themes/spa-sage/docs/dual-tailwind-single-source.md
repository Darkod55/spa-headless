# Dual Tailwind → single frontend CSS (Sage + Vite + Blockstudio)

Agent playbook for eliminating duplicate CSS on the public site when a Sage theme uses **Vite/Tailwind** for `app.css` and **Blockstudio** with `tailwind.enabled: true` for the block editor.

Use this doc when an AI agent is asked to “stop CSS loading twice” or “remove `#blockstudio-tailwind` on the frontend while keeping editor-added utilities working”.

---

## Problem

With Blockstudio Tailwind enabled, two pipelines run in parallel:

| Pipeline | When | Output |
| --- | --- | --- |
| **Vite** | `npm run build` / dev | Linked `app.css` — preflight, `@theme`, components, utilities scanned from Blade/PHP/JS |
| **Blockstudio** | Every frontend request | Inline `<style id="blockstudio-tailwind">` — compiles utilities (and, if bridged, full theme CSS) from **entire rendered HTML** |

Symptoms:

- Same rules appear in `app.css` **and** inline `#blockstudio-tailwind`
- Inline block feels large (header, footer, nav utilities duplicated)
- If frontend Blockstudio is disabled entirely, editor-added classes in **post content** (e.g. `mt-12` on `core/paragraph`) stop working — Vite never saw them at build time

---

## Target architecture

| Context | CSS source |
| --- | --- |
| **Frontend** | Vite `app.css` only (+ Blockstudio per-block `style.css` inlined when a block is present — separate, on-demand, not Tailwind duplication) |
| **Block editor** | Vite `editor.css` + Blockstudio CDN + `#blockstudio-tailwind-editor` (keep `tailwind/enabled: true` in admin) |

**Do not** set `blockstudio/buffer/enabled` to `false` — that buffer also hoists block `style.css` / `script.js` into `<head>` / footer.

---

## Files involved (this theme)

| File | Role |
| --- | --- |
| `app/Traits/blocks/trait-blocks.php` | `disableFrontendBlockstudioTailwind()`, `bridgeTailwindConfig()` |
| `app/Classes/MpxBlocks.php` | Wires the two methods in the constructor |
| `resources/css/theme.css` | Hub: imports design system + safelist + `@source inline(...)` for one-off post-content classes |
| `resources/css/theme/editor-safelist.css` | **New** — `@source inline(...)` for editor spacing utilities |
| `resources/css/components/mpx-subheading.css` | Example of Vite-only CSS **outside** `theme/` so the editor bridge does not re-emit it |
| `blockstudio.json` | `tailwind.enabled: true` (unchanged — still needed for editor) |
| `resources/css/app.css` | Vite entry — `@import "./theme.css"` + `@source` globs for Blade/PHP |

---

## Step-by-step (agent checklist)

### 1. Confirm the duplication

On a frontend page, inspect `<head>`:

- Linked stylesheet: `public/build/assets/app-*.css` (Vite)
- Inline: `<style id="blockstudio-tailwind">…</style>` (Blockstudio)

If both exist and share utilities (`flex`, `mt-8`, `text-primary`, etc.), proceed.

### 2. Disable Blockstudio frontend Tailwind (keep editor)

In the Blockstudio traits file (here: `app/Traits/blocks/trait-blocks.php`), add:

```php
/**
 * Frontend CSS is owned entirely by Vite (app.css). Editor-added spacing
 * utilities (mt-12, py-4, etc.) are safelisted in
 * resources/css/theme/editor-safelist.css so they compile at build time
 * without Blockstudio injecting a second inline stylesheet on every request.
 *
 * Keep tailwind/enabled in wp-admin for the block editor CDN +
 * compile_editor_css(). Do not disable blockstudio/buffer/enabled — that
 * buffer also hoists per-block assets.
 */
public function disableFrontendBlockstudioTailwind() {
    add_filter('blockstudio/settings/tailwind/enabled', function ($enabled) {
        if (! is_admin()) {
            return false;
        }

        return $enabled;
    });
}
```

Register it from the Blockstudio bootstrap class constructor **before** `bridgeTailwindConfig()`:

```php
$this->disableFrontendBlockstudioTailwind();
$this->bridgeTailwindConfig();
```

Filter used: `blockstudio/settings/tailwind/enabled`  
Effect: `Tailwind::compile()` on the frontend returns early → no `#blockstudio-tailwind`. Editor (`is_admin()`) unchanged.

### 3. Scope the theme bridge to the editor only

If the project already has `bridgeTailwindConfig()` feeding `resources/css/theme/*.css` into `blockstudio/tailwind/css`, add an admin guard so the **public site** never re-emits the design system into inline CSS:

```php
/**
 * Bridge our theme's design tokens into Blockstudio's editor Tailwind
 * compiler so the canvas preview stays in sync with the theme's real
 * @theme config instead of only using Tailwind defaults.
 *
 * Editor-only: the public site loads the same tokens via Vite app.css.
 *
 * Globs resources/css/theme/*.css directly (variables, fonts,
 * sections, buttons, typography, ...) rather than reading theme.css
 * itself, since theme.css is just a set of @imports that Blockstudio's
 * compiler has no reason to resolve.
 *
 * CSS that must ship only via Vite (not re-emitted into Blockstudio's
 * inline <style id="blockstudio-tailwind-editor">) should live outside
 * theme/ and be @import'ed from theme.css — see
 * resources/css/components/mpx-subheading.css.
 *
 * @font-face rules are stripped: font files are fingerprinted into
 * public/build by Vite; relative font urls would 404 in Blockstudio's
 * compiled CSS context.
 */
public function bridgeTailwindConfig() {
    add_filter('blockstudio/tailwind/css', function ($css) {
        if (! is_admin()) {
            return $css;
        }

        $theme_files = glob(get_stylesheet_directory() . '/resources/css/theme/*.css') ?: [];

        foreach ($theme_files as $theme_file) {
            $contents = file_get_contents($theme_file);
            $contents = preg_replace('/@font-face\s*\{[^{}]*\}/s', '', $contents) ?: $contents;
            $css .= "\n" . $contents;
        }

        return $css;
    });
}
```

Filter: `blockstudio/tailwind/css`  
Effect: Editor canvas gets tokens; frontend never receives bridged theme files via Blockstudio.

### 4. Safelist editor spacing utilities in Vite

Create `resources/css/theme/editor-safelist.css` with Tailwind v4 `@source inline(...)` entries for utilities editors add in post content (margin/padding controls, Additional CSS class(es)).

This theme safelists **spacing only** — prefixes `mt`, `mb`, `ml`, `mr`, `mx`, `my`, `pt`, `pb`, `pl`, `pr`, `px`, `py` × default Tailwind spacing scale (`0`–`96`, `px`, `auto`, `0.5`, `1.5`, `2.5`, `3.5`).

Example (truncated — full file is in the repo):

```css
/**
 * Spacing utilities for block editor margin/padding controls and
 * Additional CSS class(es). Not visible to Vite source scans.
 */

@source inline("mt-0 mt-1 mt-2 … mt-12 … mt-96 mt-px mt-auto mt-0.5 mt-1.5 mt-2.5 mt-3.5");
@source inline("mb-0 mb-1 …");
/* … ml, mr, mx, my, pt, pb, pl, pr, px, py … */
```

**Regenerate** (optional helper — run from theme root):

```bash
php -r '
$prefixes = ["mt","mb","ml","mr","mx","my","pt","pb","pl","pr","px","py"];
$sizes = ["0","1","2","3","4","5","6","7","8","9","10","11","12","14","16","20","24","28","32","36","40","44","48","52","56","60","64","72","80","96","px","auto","0.5","1.5","2.5","3.5"];
$classes = [];
foreach ($prefixes as $p) {
  foreach ($sizes as $s) {
    $classes[] = $p . "-" . $s;
  }
}
$chunks = array_chunk($classes, 36);
$out = "/** Spacing safelist for editor post-content utilities. */\n\n";
foreach ($chunks as $chunk) {
  $out .= "@source inline(\"" . implode(" ", $chunk) . "\");\n";
}
file_put_contents("resources/css/theme/editor-safelist.css", $out);
'
```

Import it from `resources/css/theme.css`:

```css
@import "./theme/editor-safelist.css";
```

Update the comment block that previously referenced Blockstudio HTML-scan as the frontend fallback — it now points at the safelist.

### 5. Keep one-off post-content classes in `theme.css`

Classes that are not spacing utilities (hero glass panel, layout flags, brand form wrapper, etc.) stay as explicit `@source inline("…")` in `theme.css`:

```css
@source inline("text-primary text-secondary …");
@source inline("bg-primary bg-secondary …");
@source inline("bg-white/10 backdrop-blur-sm … brand-form");
@source inline("is-v-align-top is-v-align-center …");
```

Add new one-offs here when editors use Additional CSS class(es) for non-spacing utilities.

### 6. Vite-only component CSS outside `theme/`

Rules that must **never** be re-emitted by Blockstudio’s editor bridge belong **outside** `resources/css/theme/` and are imported from `theme.css`:

```
resources/css/components/mpx-subheading.css  →  @import in theme.css
```

The `bridgeTailwindConfig()` glob is `theme/*.css` only — files in `components/` are Vite-only.

### 7. Rebuild and verify

```bash
cd app/public/wp-content/themes/<theme>
npm run build
```

Frontend checks:

1. View source — **no** `<style id="blockstudio-tailwind">`
2. **One** linked `app-*.css` from `public/build/assets/`
3. Search built CSS for a safelisted utility: e.g. `.mt-12{` present in `app-*.css`
4. Page with editor-added `mt-12` on a paragraph — margin applies after hard refresh

Editor checks:

1. Block canvas still styled (CDN + `#blockstudio-tailwind-editor`)
2. Spacing / colour controls still preview correctly

---

## What we explicitly did **not** do

| Avoid | Why |
| --- | --- |
| `blockstudio/buffer/enabled` → `false` | Breaks hoisting of per-block `style.css` / scripts |
| `tailwind.enabled: false` globally | Breaks editor CDN + `compile_editor_css()` |
| Re-enable full frontend Blockstudio + full `theme/*.css` bridge | Recreates massive inline duplication |
| Utilities-only frontend Blockstudio (intermediate attempt) | Still scans full page HTML; duplicates header/footer utilities in inline CSS |

---

## Tradeoffs

- **`app.css` grows** by the safelist (~432 spacing utilities in this theme). One cached file, gzip-friendly, no per-request inline compile.
- **Safelist is not infinite** — not covered unless added:
  - Responsive prefixes (`md:mt-12`)
  - `gap-*`, `m-*`, `p-*` shorthand (only axis-specific prefixes are safelisted here)
  - Arbitrary values (`mt-[40px]`)
  - Rare one-offs → add to `@source inline(...)` in `theme.css` or extend `editor-safelist.css`
- **Per-block `style.css`** (Blockstudio) may still appear as small inline blocks when those blocks are on the page — that is intentional and separate from Tailwind duplication.

---

## Related existing docs in this theme

- `BLOCKSTUDIO-7.md` — Tailwind bridge overview (update narrative if it still says frontend Blockstudio is the fallback)
- `docs/design-tokens.md` — when adding colours, also update `theme.css` `@source inline`
- `.cursor/rules/hrconsulting-design-system.mdc` — dual Tailwind note
- `resources/css/components/mpx-subheading.css` — Vite-only component pattern

---

## Copy-paste prompt for another project’s AI agent

Paste this into a new chat in the target Sage + Blockstudio project. Attach or point at this playbook file first (or paste the playbook into the same message).

### Prompt (read playbook → implement)

```
Read and follow the playbook: docs/dual-tailwind-single-source.md
(If that path is not in this repo, use the attached / pasted dual-tailwind-single-source.md content as the source of truth.)

Goal
- Stop duplicate CSS on the public site: Vite app.css must be the only Tailwind source on the frontend.
- Remove <style id="blockstudio-tailwind"> from frontend HTML.
- Keep Blockstudio Tailwind working in wp-admin / the block editor.
- Keep editor-added spacing utilities (e.g. mt-12 on core/paragraph Additional CSS class(es)) working via a Vite safelist — do not rely on Blockstudio’s per-request frontend compile.

Context (expected stack)
- Sage theme + Vite + Tailwind CSS v4 (app.css / editor.css).
- Blockstudio with tailwind.enabled: true in blockstudio.json.
- Optional existing bridgeTailwindConfig() that globs resources/css/theme/*.css into blockstudio/tailwind/css.

Do this in order
1. Confirm duplication: frontend <head> has both linked app-*.css and inline #blockstudio-tailwind. If only one exists, report findings and stop.
2. Locate Blockstudio wiring (bootstrap class + traits). Adapt paths — this theme uses app/Classes/MpxBlocks.php + app/Traits/blocks/trait-blocks.php; other themes may differ.
3. Add disableFrontendBlockstudioTailwind():
   - Filter: blockstudio/settings/tailwind/enabled
   - Return false when ! is_admin(); otherwise return $enabled.
   - Wire it in the bootstrap constructor BEFORE bridgeTailwindConfig().
4. Update bridgeTailwindConfig() (or create it if missing):
   - Filter: blockstudio/tailwind/css
   - Early-return unchanged $css when ! is_admin().
   - In admin only: glob get_stylesheet_directory() . '/resources/css/theme/*.css', strip @font-face, append to $css.
5. Create resources/css/theme/editor-safelist.css with @source inline(...) for spacing utilities:
   prefixes mt, mb, ml, mr, mx, my, pt, pb, pl, pr, px, py
   × sizes 0–12, 14, 16, 20, 24, 28, 32, 36, 40, 44, 48, 52, 56, 60, 64, 72, 80, 96, px, auto, 0.5, 1.5, 2.5, 3.5
   (Use the regenerate PHP snippet in the playbook if helpful.)
6. @import "./theme/editor-safelist.css" from resources/css/theme.css (or the theme’s design-system hub that app.css imports).
7. Keep one-off non-spacing post-content classes as @source inline(...) in theme.css (brand-form, layout flags, etc.). Add any known project-specific ones you find.
8. Keep Vite-only component CSS outside theme/ (e.g. resources/css/components/) and @import from the hub — so the editor bridge glob does not re-emit it.
9. Do NOT set blockstudio/buffer/enabled to false.
10. Do NOT set tailwind.enabled: false globally in blockstudio.json.
11. Run npm run build from the theme directory.
12. Verify:
    - Frontend: no #blockstudio-tailwind; one linked app-*.css; .mt-12 (or similar) present in built app.css.
    - Editor: canvas still styled; Blockstudio Tailwind still available in admin.

Constraints
- Match existing code style; minimal diffs; no drive-by refactors.
- Do not invent new architecture — implement the playbook as written.
- If a step does not apply (e.g. no existing bridge), say so and implement the minimal equivalent.
- When done, summarize files changed and how to verify.
```

### Shorter prompt (if the playbook is already in-repo and attached)

```
We use Sage + Vite (app.css, Tailwind v4) and Blockstudio with tailwind.enabled: true.
CSS is duplicated: app.css plus inline <style id="blockstudio-tailwind"> on every frontend request.

Read docs/dual-tailwind-single-source.md and implement it end-to-end in this theme.

1. disableFrontendBlockstudioTailwind() — blockstudio/settings/tailwind/enabled → false when ! is_admin(); wire before bridgeTailwindConfig().
2. bridgeTailwindConfig() admin-only — blockstudio/tailwind/css early-return when ! is_admin(); glob theme/*.css only in admin.
3. Create resources/css/theme/editor-safelist.css (@source inline spacing utilities) and import it from theme.css.
4. Keep one-off post-content @source inline in theme.css; Vite-only CSS outside theme/.
5. Do NOT disable blockstudio/buffer/enabled or turn off tailwind.enabled globally.
6. npm run build; verify no frontend #blockstudio-tailwind, mt-12 in app.css, editor still works.

Adapt file paths to this theme’s Blockstudio bootstrap/traits. Minimal diffs only.
```

---

## Summary of functions (quick reference)

| Function | File | Filter | Frontend | Editor |
| --- | --- | --- | --- | --- |
| `disableFrontendBlockstudioTailwind()` | `trait-blocks.php` | `blockstudio/settings/tailwind/enabled` | Tailwind off | Tailwind on |
| `bridgeTailwindConfig()` | `trait-blocks.php` | `blockstudio/tailwind/css` | No theme glob | Globs `theme/*.css` into compiler input |

Both are called from `MpxBlocks::__construct()` (or equivalent Blockstudio wiring class).
