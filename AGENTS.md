# Agent notes

This is the headless rebuild of [thesquarepeg.agency](https://thesquarepeg.agency). WordPress is the CMS. Astro is the public site. The current live site is a different repository and is not part of this one.

Deployment (cms subdomain, xCloud, rebuild webhook) is out of scope.

## Where things live

- `wordpress/themes/spa-headless/` — BlockStudio blocks. BlockStudio only auto-registers a `blockstudio/` directory on the **active theme**, and `blockstudio.json` belongs to the theme. That is why blocks are not in the plugin.
- `wordpress/plugins/spa-headless-core/` — REST `blocks` field (`parse_blocks()`), `spa/v1` routes (content, `menus/primary`, `site`), CORS allowlist for `http://localhost:4321`, preview token, and `register_post_meta` (`show_in_rest`, not ACF).
- `astro/` — public front end. `WP_URL` in `astro/.env` (gitignored). `WP_FIXTURES=auto` uses checked-in fixtures when WordPress is down, so `astro check` and `astro build` work in CI.
- `astro/src/styles/global.css` — the live site's design system, ported from `Darkod55/spa` `src/styles/global.css`. That site does not use Tailwind. Tokens, the type scale, Oswald and Montserrat (self-hosted through `@fontsource`, the same packages the live site imports), reset, `.container`, form fields, and surface utilities (`bg-primary`, `.type-h2`, …) live there. Block and header CSS should use those variables, not a second palette. `content.css` only places scaffold blocks. `header.css` is the header chrome.
- `scripts/generate-block-types.ts` — reads every BlockStudio `block.json` and writes `astro/src/generated/blocks.ts`.

Do not commit `.env`, uploads, `node_modules`, `vendor`, or `dist`. Commit `astro/.env.example` only.

## Block contract

Adding or changing a block touches both trees. `pnpm check` and `pnpm build` run the generator first (`astro`'s `precheck` / `prebuild` call `pnpm -w generate`).

1. Add `wordpress/themes/spa-headless/blockstudio/sections/<slug>/block.json`.
   - `$schema`: `https://blockstudio.dev/schema/block`
   - `name`: `spa/<slug>` (one slash)
   - Fields live in `blockstudio.attributes` with `id` and `type`. `block.json` is the source of truth, not a PHP register call.
   - Tabs and groups **without** an `id` are layout only. A group **with** an `id` prefixes child ids (`presentation` + `align` → `presentation_align`).
   - `{"type": "custom/section"}` inlines `blockstudio/fields/section/field.json` (the `removeBottomPadding` and `accentBg` toggles).
   - Repeater row fields stay nested on the row. They are still part of the contract.
2. Add `index.php` beside `block.json`. Attributes are `$a`. Escape output. One `<InnerBlocks />` per block if editors should nest core blocks. The PHP template is the editor and WordPress preview, not the public site.
3. Add `astro/src/components/blocks/spa/<PascalSlug>.astro` with a `<slot />` for inner blocks. Use the generated attribute type. Mention every field id in that file (the generator checks).
4. Add `astro/src/components/blocks/spa/<slug>.contract.ts`:

   ```ts
   export const exampleFields = {
     heading: true,
     cards: { title: true, text: true },
   } as const satisfies SpaExampleFieldMap;
   ```

   Import that contract from the component.
5. Register `'spa/<slug>'` in `astro/src/components/blocks/BlockRenderer.astro` and pass inner blocks through the slot.
6. Run `pnpm generate` (or `pnpm check`).

The generator exits non-zero when:

- a BlockStudio block has no Astro component
- the component does not import its contract or never mentions a field id
- the contract object does not match `block.json` (added, renamed, removed, including repeater rows)
- `BlockRenderer.astro` does not register the block name

`astro check` also fails when the contract does not `satisfy` the generated `*FieldMap`, so a field change cannot be silenced by skipping the generator and hand-editing the component only.

Core blocks (`core/paragraph`, `core/heading`, `core/image`, `core/list`, `core/buttons`, `core/group`, `core/columns`, plus `list-item`, `button`, and `column`) are hand-written. Anything else, including `blockName: null` freeform HTML, renders saved `innerHTML` / `innerContent`.

## Content and SEO

- Public paths come from WordPress permalinks (`spa/v1/routes` and `spa/v1/content?path=`). Keep slugs identical to the live site. Posts use `/blog/%postname%/`.
- Block JSON is `parse_blocks()`, exposed as `blocks` on `wp/v2` posts and pages and inside the `spa/v1` payload: `blockName`, `attrs`, `innerBlocks`, `innerHTML`, `innerContent`.
- Rank Math head comes from `GET /wp-json/rankmath/v1/getHead?url={absolute WordPress URL}` (`success` + `head`). Enable Headless CMS Support under Rank Math → General Settings → Others. The Astro layout injects `head` and rewrites the WordPress origin to `PUBLIC_SITE_URL`.
- `spa_eyebrow` (string) and `spa_hide_title` (boolean) are the pattern for custom meta. `auth_callback` returns true because WordPress checks it before anonymous REST reads. Do not copy that for private data.
- GA4 loads only when `PUBLIC_GA_MEASUREMENT_ID` matches `G-…`. Leave it empty in git. The live measurement id is documented in `astro/.env.example`, not in source.
- Contact: `POST /api/contact` with optional Turnstile, matching the current site. Fluent Forms is the alternative when submissions should stay in WordPress. Neither sends mail yet.

## Local commands

```bash
pnpm install
pnpm test
pnpm check
pnpm build
pnpm dev
```

`pnpm dev` serves Astro at `http://localhost:4321`. WordPress runs in Local WP, not in this repo. Symlink steps are in `README.md`.

## Header

The theme registers the `primary` menu location and `custom-logo`. The plugin exposes:

- `GET /wp-json/spa/v1/menus/primary?path=/about` — nested items (`id`, `title`, `url`, `target`, `classes`, `parent`, `children`, `current`, `currentAncestor`). Same-host URLs are root-relative. Optional `path` sets the current flags; Astro also recomputes them for the page it renders.
- `GET /wp-json/spa/v1/site` — `title`, `description`, `logo`, `siteIcon`, and `announcement`. SVG logos use the viewBox when WordPress has no pixel size. The image URL stays on WordPress. The announcement is the red bar above the header. A newline in `message` is hidden on desktop and shown on small screens. There is no dismiss control; the bar sticks with the header until Customize turns it off.
- `siteIcon` is Appearance → Customize → Site Identity → Site Icon. `sizes` are `get_site_icon_url()` at 32, 180, 192, and 270, plus `mime`. The layout prints the same tags as `wp_site_icon()` (32 and 192 icons, apple-touch 180, tile image 270). An SVG is one `rel="icon"` because every size is the same file. Icon tags already present in Rank Math's `head` are removed when this icon is set, so they are not doubled. WordPress does not supply a web manifest or `theme-color` for the site icon, so those are not invented.

A top-level item with the CSS class `cta` (or `button`) is the “Let’s talk” button, not a text link. Items with children are a desktop dropdown and an indented group in the mobile panel. Both follow `WP_FIXTURES` the same way page content does.

Menu locations: `primary`, `footer` (Menu Footer), `legal` (Menu Legal), `footer-social` (Footer Menu Social). A registered location with no menu assigned returns `{ items: [] }`, not a 404. Social icons are chosen from each item’s URL host (facebook.com/fb.com, instagram.com, linkedin.com, x.com/twitter.com, youtube.com/youtu.be); anything else gets a generic link icon. They always open in a new tab.

In wp-admin: Appearance → Menus, create a menu, assign it under Menu Settings, and enable CSS classes under Screen Options if the CTA class field is hidden. Logo: Appearance → Customize → Site Identity → Logo. Site title is the alt text when the logo has none. Announcement: Appearance → Customize → Announcement bar (show, message, link label, URL, same tab or new tab). The footer wordmark is the white logo file, matching the live footer, not the colour Site Identity logo.

## Fixtures

With `WP_URL` unset, or `WP_FIXTURES=1`, or `WP_FIXTURES=auto` after a failed request, pages render `astro/src/lib/wp/fixtures.ts`. `WP_FIXTURES=0` makes a failed WordPress request fail the build. Preview (`/preview/:id`) never uses fixtures; it needs `SPA_PREVIEW_TOKEN` on both sides.
