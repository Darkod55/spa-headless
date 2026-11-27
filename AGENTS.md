# Agent notes

This is the headless rebuild of [thesquarepeg.agency](https://thesquarepeg.agency). WordPress is the CMS. Astro is the public site. The current live site is a different repository and is not part of this one.

Deployment (cms subdomain, xCloud, rebuild webhook) is out of scope.

## Where things live

- `wordpress/themes/spa-headless/` — BlockStudio blocks. BlockStudio only auto-registers a `blockstudio/` directory on the **active theme**, and `blockstudio.json` belongs to the theme. That is why blocks are not in the plugin.
- `wordpress/plugins/spa-headless-core/` — REST `blocks` field (`parse_blocks()`), `spa/v1` routes, CORS allowlist for `http://localhost:4321`, preview token, and `register_post_meta` (`show_in_rest`, not ACF).
- `astro/` — public front end. `WP_URL` in `astro/.env` (gitignored). `WP_FIXTURES=auto` uses checked-in fixtures when WordPress is down, so `astro check` and `astro build` work in CI.
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

## Fixtures

With `WP_URL` unset, or `WP_FIXTURES=1`, or `WP_FIXTURES=auto` after a failed request, pages render `astro/src/lib/wp/fixtures.ts`. `WP_FIXTURES=0` makes a failed WordPress request fail the build. Preview (`/preview/:id`) never uses fixtures; it needs `SPA_PREVIEW_TOKEN` on both sides.
