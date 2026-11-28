# Blockstudio 7

This theme uses **Blockstudio 7** (currently **7.6.6**) to build custom blocks. The plugin is installed and activated directly in `wp-content/plugins/blockstudio` (there is no root-level `composer.json` managing it in this project; plugins are gitignored — see repo `AGENTS.md`).

## Theme configuration (already done)

- **Block path**: theme blocks are loaded from `app/blocks` via the `blockstudio/path` filter in `mpxBlockstudioPath()` (`app/Traits/blocks/trait-blocks.php`). This filter is read by the plugin's own default `Build::init()` call (see `Blockstudio\Build::get_build_dir()`), so it replaces the default `{theme}/blockstudio` scan root rather than adding a second instance. (An earlier version of this method called `\Blockstudio\Build::init(['dir' => ...])` directly, gated behind `defined('BLOCKSTUDIO')` — that constant doesn't actually exist in this plugin version, so the call silently never ran and blocks in `app/blocks` were never discovered.)
- **User access**: only users with the **administrator** role can use the Blockstudio editor, configured via `blockstudio.json`'s `users.roles`.
- **Populate options**: colours, padding, button types, sprite icons, etc. are provided via the `blockstudio/blocks/attributes/populate` filter (plain single-array signature). Values are aligned with the theme design tokens in `resources/css/theme/` (CSS variables + `btn-*` / `py-clamp*` class names).
- **Build cache disabled** (`blockstudio.json` → `cache.enabled: false`): Blockstudio has a file-backed "block discovery" cache (`Blockstudio\Build_Cache`, on by default upstream) that caches the *expanded* attribute schema for every block — including `populate`-resolved options for `select`/`color`/etc fields (e.g. `populateTextColours()` in `trait-blocks.php`). Its cache key only fingerprints files under the blocks/fields scan roots (`app/blocks/**`) plus a handful of WP lifecycle hooks — it has no way to know that an arbitrary theme PHP file like `trait-blocks.php` changed, so editing a `populate*()` function silently serves stale data until something else busts the cache. Disabled here so populate changes always show up immediately; re-enable for production only with a bust plan (touch a file under `app/blocks/**` after changing populate PHP).
- **Inner blocks**: `mpxRemoveInnerBlocksWrapper()` turns off the frontend InnerBlocks wrapper for all current `mpx/*` section and partial blocks (hero, columns, one-column, partners-slider, and every `mpx/partial-*`). When adding a new `mpx/*` block, add its name to that allowlist. The installed plugin fires both the legacy `blockstudio/blocks/components/innerblocks/frontend/wrap` hook and the newer `inner_blocks` one — either works.
- **Tailwind bridge**: `blockstudio.json` has `tailwind.enabled: true`, which runs Blockstudio's own server-side Tailwind compiler (TailwindPHP) for the block editor preview and as a frontend safety net for dynamically-built class names. `bridgeTailwindConfig()` hooks `blockstudio/tailwind/css` and globs `resources/css/theme/*.css` (not the `@import` hub `theme.css`), stripping `@font-face` so Vite-fingerprinted fonts aren't double-registered / 404 in Blockstudio's CSS context. Rules that must not be re-emitted into the inline `<style id="blockstudio-tailwind">` live outside `theme/` and are imported from `theme.css` (e.g. `resources/css/components/mpx-subheading.css`).

## Block JSON schema

Blocks use `"$schema": "https://blockstudio.dev/schema/block"`; extensions use `"$schema": "https://blockstudio.dev/schema/extend"` for editor IDE support.

## Requirements

- PHP 8.3+ (theme `style.css` / Composer)
- WordPress 6.6+ (theme `Requires at least`)

Docs: [blockstudio.dev/docs](https://blockstudio.dev/docs). The plugin also ships its own docs corpus for coding agents at `wp-content/plugins/blockstudio/includes/llm/` — `blockstudio-llm.txt` is an index (route + purpose + owned hooks/php/cli per page), `blockstudio-llm-full.txt` is every doc page concatenated. Grep the full file for a heading before reading the docs site; it's faster and always matches the installed version.

> **Version drift warning**: the public docs occasionally describe an older/newer API shape than what's installed. Where the two disagreed, this file documents what we verified against the actual plugin source in `wp-content/plugins/blockstudio/includes/classes/`, not just the docs. Always double check filter signatures against the source when something doesn't behave as documented (the `blockstudio/blocks/attributes/populate` filter and the `blockstudio/blocks/render` filter below are both examples of this).

## Philosophy, in short

Blockstudio is a thin, server-first layer on top of core WordPress block registration: you write a `block.json` plus a template file (PHP, Twig, or Blade), and get editor affordances (inspector fields, `<RichText>`, `<InnerBlocks>`, media pickers) without writing any block JS/React yourself. Everything is file-system based — a block is a folder, full stop. JSX-like pseudo-tags (`<RichText>`, `<InnerBlocks>`, `<MediaPlaceholder>`, the `useBlockProps` attribute) are string-replaced by Blockstudio *after* your template engine renders plain HTML, which is exactly what makes bolting on Blade possible even though Blockstudio has no native Blade compiler (see below).

## Template engines

Three engines are supported by filename, in this priority order when more than one exists in a block folder: `index.php` > `index.blade.php` > `index.twig`.

### Blade (what we use)

The public docs set up Blade via a **second, separate** `jenssegers/blade` compiler/cache with its own `blockstudio/blocks/render` filter hookup. We don't do that — this theme already runs real Laravel Blade through **Acorn** (Sage's framework layer), complete with the `@svgIcon` directive and all `resources/views` layouts/components. Installing a second Blade engine would mean two compilers, two caches, and no access to the theme's existing directives/components from inside a block.

Instead, `renderBladeBlockTemplates()` in [`app/Traits/blocks/trait-blocks.php`](app/Traits/blocks/trait-blocks.php) hooks the *same* `blockstudio/blocks/render` filter and renders `.blade.php` block templates with Acorn's own view factory (`view()->file($path, $data)`), registered in `MpxBlocks`'s constructor.

Why this works, mechanically (verified against `includes/classes/block.php`):

- The installed 7.6.6 render pipeline has no dedicated Blade branch. For anything that isn't `.twig`, it just does `include $path` — meaning a `.blade.php` file is quietly `include`d as raw PHP. Since Blade syntax (`{{ }}`, `@if`, etc.) isn't valid PHP tags, none of it executes; it's captured into an (unused, garbled) output buffer.
- Right after that, Blockstudio calls `apply_filters('blockstudio/blocks/render', $rendered_block, $filter_data, $is_editor, $is_preview)`. **This is a 4-argument filter in the installed version**, not the 2-argument `($value, $block)` shown in the public docs. Our filter callback detects the `.blade.php` path (from `$filter_data->blockstudio['data']['path']`) and returns real Blade output instead, discarding the garbled include.
- Critically, Blockstudio then checks if the filter's return value differs from the original *and* still contains `<RichText`, `<InnerBlocks`, `<MediaPlaceholder`, or `useBlockProps` — if so, it re-runs its own component-replacement pass on our Blade output. This is explicitly designed in core to "support external template engines like Blade", so `<RichText>`, `<InnerBlocks>`, and `useBlockProps` all work normally inside `.blade.php` templates, exactly as in PHP/Twig.

Template variables passed to Blade views: `$a`/`$attributes`, `$b`/`$block`, `$c`/`$context`, `$isEditor`, `$isPreview` — same names/aliases as PHP templates use natively.

Practical notes:

- Anything already HTML-escaped by a WordPress function (`esc_attr()`, `esc_html()`, `wp_kses_post()`, `wp_json_encode()` for a tag attribute, etc.) must be echoed with Blade's raw `{!! !!}`, not `{{ }}` — Blade's `{{ }}` also escapes, so `{{ esc_attr($x) }}` double-encodes quotes into `&amp;quot;`. This matters for things like `<InnerBlocks allowedBlocks="...">`, which needs `esc_attr(wp_json_encode([...]))`.
- `view()->file()` renders a template file by absolute path (no dot-notation registration needed), so a block template doesn't need to live under `resources/views`. `@include('some.dot.path')`/`@extends(...)` inside a block template would still resolve against the theme's normal registered view paths if ever needed.
- No `jenssegers/blade` Composer dependency, no second compiled-view cache directory — one Blade engine for the whole theme.

### Twig

Requires `composer require timber/timber`; auto-detected. Not currently installed/used in this theme.

### PHP

The default/simplest option — an `index.php` that echoes HTML directly, with the same `$a`/`$b`/`$c` variables in scope.

## Registering blocks & attributes

- Block discovery root: `app/blocks` (set in `mpxBlockstudioPath()`). A block is a folder containing `block.json` + one template file (`index.php` / `index.blade.php` / `index.twig`) + optional `style.css`/`script.js`/etc, auto-enqueued only when the block is used on the page. Blocks, overrides, and extensions (`blockstudio.extend: true` JSON files, e.g. `app/blocks/extensions/heading.json`) can live **anywhere** under this root, at any nesting depth — Blockstudio reads the block/extend name from the JSON content itself, not the folder path, and discovery recurses fully (see `app/blocks/hero`, `app/blocks/partial/partial-column`).
- **Custom fields are the one exception to that**: `field.json` reusable-field discovery (`custom/{name}` references, e.g. `custom/typography`) is *not* recursive — it only ever looks at a hard-coded `{scan root}/fields/` path (`Build::discover_local_custom_fields()`: `$fields_path = $path . '/fields'`). Fields **must** live directly at `app/blocks/fields/{name}/field.json`; nesting them any deeper (e.g. under a namespace subfolder) means they're silently never found, with no warning. (This bit us once already — fields originally lived at `app/blocks/mpx/fields/`, one level too deep, and `custom/typography` resolved to nothing until they were moved to `app/blocks/fields/`.)
- Attributes live under `blockstudio.attributes` in `block.json`. Field type enum (installed schema, `wp-content/plugins/blockstudio/schemas/block.json`): `attributes, block, checkbox, classes, code, color, date, datetime, files, gradient, html-tag, icon, link, message, number, radio, range, select, tabs, text, textarea, toggle, repeater, richtext, unit, wysiwyg`. There is **no dedicated `image` type** — image/media pickers use `files` with `allowedTypes: ["image"]`, `multiple: false`, `returnFormat: "object"` (returns `['ID', 'title', 'alt', 'caption', 'description', 'href', 'url', 'sizes']`, verified against `Block::get_attachment_data()`).
- `populate` on `checkbox`/`radio`/`select` fields can source options from a WP query, an arbitrary function, an external fetch, or a `blockstudio/blocks/attributes/populate` custom dataset (`{"populate": {"type": "custom", "custom": "myKey"}}` ↔ `$options['myKey'] = [...]` in the filter — pattern behind `populateButtonTypes()`, `populateTextColours()`, `populateSpriteIcons()`, etc. in `trait-blocks.php`).
  - Populate datasets are reconciled with the design system: colours use `var(--color-*)` / `bg-*` slugs, buttons use `btn-primary` / `btn-secondary` / `btn-primary-alt` / `btn-white`, section padding uses `py-clamp*` utilities, icons come from `footer-icons.blade.php` symbol ids. Prefer reusable fields under `app/blocks/fields/` (`custom/section_padding`, `custom/background`, `custom/grid_controls`, …) over duplicating inline selects.
- `conditions` on a field (array of `[{id, operator, value}]` groups) drives simple show/hide logic in the inspector, e.g. hiding button fields until a "Show button" toggle is on.
- `<RichText attribute="fieldId" tag="h1" placeholder="..." />` binds inline-editable rich text to a `richtext`-typed attribute. `<InnerBlocks allowedBlocks="..." template="..." templateLock="..." />` embeds nested blocks (only one `<InnerBlocks>` per block template — a core WordPress limitation). `useBlockProps` on the template's single root element gives markup parity between editor and frontend.

## Client behaviour: Islands, Interactivity API, RPC

- **Interactivity API**: set `"blockstudio": {"interactivity": true}` in `block.json`, then use `data-wp-interactive`/`data-wp-context`/`data-wp-on--*`/`data-wp-bind--*` directives in the template. A `script.js` calling `store()` from `@wordpress/interactivity` is only needed for custom actions/callbacks — purely declarative directives need no JS at all. `wp_interactivity_state()` seeds server-side state (frontend-only; editor previews render via REST so use `data-wp-context` for anything that must also work in the editor).
- **Islands**: split a block into a cache-safe placeholder + a client-hydrated/fetched fragment (`"island": {"mode": "dynamic"|"hydrate", "cache": ...}`). For content that must be crawlable/SEO-relevant, do not use a dynamic island — it renders after the initial HTML. Reserve islands for genuinely personalized/deferred content (cart state, account UI).
- **RPC**: an `rpc.php` returning named PHP functions, called from the client via the auto-injected `bs.fn(name, params)` helper — a lightweight alternative to hand-rolling REST routes for simple block-scoped server actions (form submissions, small AJAX lookups).

None of these are used by current `mpx/*` blocks, but they're the right tool once we build anything with genuine client-side state (e.g. a filterable listing, a like button, a multi-step form beyond what Fluent Forms already covers).

## Pages, Patterns, and Site Templates — what applies here

This theme explicitly disables full-site editing (`remove_theme_support('block-templates')` in `app/setup.php`) and renders through classic Blade templates (`resources/views/*.blade.php`) via Acorn/Sage's own template hierarchy. That changes which of Blockstudio's three file-based content features are relevant:

| Feature | Creates | Relevant here? |
| --- | --- | --- |
| **Patterns** (`patterns/*/pattern.json` + template file) | In-memory block patterns, shown in the inserter | **Yes** — reusable content sections (e.g. a repeatable "hero" starting point, CTA sections) editors can insert into normal page/post content. No sync, no DB entries. |
| **Pages** (`pages/*/page.json` + template file, scanned via `blockstudio/pages/paths`) | Real `wp_posts` rows, auto-synced from files, with template locking | **Situational** — useful for content that should be authored/reviewed in code (e.g. legal pages, structured doc-style content) rather than free-form in the editor. Not used yet. |
| **Site Templates** (`templates/`/`parts/` + `template.json`/`part.json`) | `wp_template`/`wp_template_part` sources for the Site Editor template hierarchy | **No** — FSE is intentionally off for this theme, so WordPress never resolves these. Header/footer/layout stay owned by `resources/views/sections/*.blade.php` and `layouts/app.blade.php`. Revisit only if the theme ever adopts full-site editing. |

## Performance, caching, static prerendering

Not configured yet beyond the discovery-cache override (site is still in early local development); noting the adoption path here for when it's relevant:

- **`performance.profile: "speed"`** in `blockstudio.json` turns on a bundle of production frontend defaults in one switch (strips generator/emoji/feed/oEmbed/XML-RPC head noise, throttles Heartbeat, prefetches same-origin links on hover, etc.) — the docs' own recommended first step, but test thoroughly since "removes core frontend assets" can visibly break a plugin that depends on them (worth a dedicated check before enabling on a live site).
- **Discovery / build cache**: this theme sets `cache.enabled: false` in `blockstudio.json` (see Theme configuration above). Upstream defaults are `true` and would persist discovery/compiled-template/asset/Tailwind artifacts under `wp-content/blockstudio/cache/`. Do not assume caching is "already on" here — it is intentionally off while populate options live in PHP outside the scan root.
- **Static Prerendering** (`performance.staticPrerender.enabled`) is an opt-in anonymous-HTML cache (optionally served *before* WordPress boots via `earlyServe`) — worth turning on once the site has real, mostly-static marketing pages and traffic to justify it, not during active block-building. `wp bs prerender warm|status|purge` manage it.
- **Media**: prefer `wp_get_attachment_image()` for images coming from block "files" attributes (real srcset/width/height from the media library) — Blockstudio's own `bs_media_image()` helper is aimed at theme-relative *static* assets (e.g. `resources/images/...`), not media-library attachments. The hero sets `loading="eager"` + `fetchpriority="high"` on its background image (LCP); use lazy loading for below-the-fold images.

## Tailwind

- `blockstudio.json` → `tailwind.enabled: true` runs Blockstudio's own per-request server-side Tailwind compiler (TailwindPHP), used for (a) the block editor's live CDN preview and (b) a frontend safety net that recompiles CSS from the *actual rendered HTML* on every request — meaning even class names built dynamically in PHP (not statically greppable at build time) still get real compiled CSS. `bridgeTailwindConfig()` feeds it this theme's real design tokens (globs `resources/css/theme/*.css`) instead of stock Tailwind.
- Separately, the **main site bundle** (Vite, `resources/css/app.css`) already has explicit `@source` globs covering `../../app/**/*.php` and `../**/*.blade.php` — meaning `app/blocks/**/*.blade.php` templates are scanned by the normal Tailwind build too, no config changes needed per block. In practice this means Tailwind utility classes written in block templates "just work" through the regular build; the Blockstudio bridge is the fallback for anything that build can't see statically. Classes that only exist in saved post content (Additional CSS class(es)) may still need `@source inline(...)` in `resources/css/theme.css`.
- Design tokens live in `resources/css/theme/variables.css` (`--color-primary`, `--color-secondary`, `--color-tertiary`, …) and `resources/css/theme/buttons.css`. Button API: `.btn` + `.btn-primary` / `.btn-secondary` / `.btn-primary-alt` / `.btn-white` (plus `.btn-sm`, `.btn-full`, `.btn-has-icon`). `.btn-outline-primary` is a **legacy alias** of `.btn-white` — prefer `btn-white` in new work. Populate helpers and extensions already use these class names.

## Current composition

One-line inventory + parent→child matrix: [`docs/blocks.md`](docs/blocks.md).

### `mpx/hero`

[`app/blocks/hero/`](app/blocks/hero) — full-width hero with optional background image + fixed `hero-overlay`, wrapping `<InnerBlocks>` pre-templated with two [`mpx/partial-column`](app/blocks/partial/partial-column) children: left = `core/heading` / `core/paragraph` / `mpx/partial-button`; right = Fluent Forms (`fluentfom/guten-block` — typo in the Fluent Forms plugin itself, confirmed in its Gutenberg block registration).

Notable decisions:

- `.blade.php` via the Acorn bridge; section attrs come from reusable fields (`custom/section_padding`, `custom/background`, `custom/grid_controls`) plus a `flip_columns` toggle.
- Background image uses `wp_get_attachment_image()` (not `bs_media_image()`), eager + high fetch priority for LCP.
- Overlay is a fixed gradient (`hero-overlay` in `resources/css/theme/sections.css`) — not an editor field.
- Content is InnerBlocks of native / partial blocks, not custom richtext/button attributes. Top-level `allowedBlocks` is `["mpx/partial-column"]`; **no `templateLock`** (locks cascade and would block free editing inside columns).
- Layout uses the flex utility `grid-auto-cards` (+ `grid-auto-cards-gap` / `grid-auto-cards-nowrap`) with CSS vars from Grid Controls (`cols_*`, gaps) resolved via `\App\helpers()->resolveGridTrackMin()` — not CSS `auto-fill`/`minmax` and not `cardMinDesktop`/`cardMinMobile` attributes.

### `mpx/partial-column`

[`app/blocks/partial/partial-column/`](app/blocks/partial/partial-column) — grid cell: `useBlockProps` + `<InnerBlocks />`, frontend wrap removed. Attributes:

- `allowedBlocks` (hidden text) — comma-separated names set from the parent template, not edited by content editors.

Used by `mpx/columns` (two columns; **widths live on the parent** via `custom/column_widths` → `--col-1-*` / `--col-2-*` on the section). Mobile is always full width in CSS. Cards keep per-item `custom/grid_item_width` + parent `custom/grid_controls`.
