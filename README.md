# Square Peg headless

WordPress is the CMS. Astro is the public site. This repository is the rebuild of [thesquarepeg.agency](https://thesquarepeg.agency). The current live site stays in its own repo.

Nothing here deploys. Hosting (WordPress on a `cms.` subdomain, Astro on the public host, a rebuild webhook) comes later.

```
wordpress/themes/spa-headless/     BlockStudio blocks (active theme)
wordpress/plugins/spa-headless-core/   REST, CORS, preview, meta
astro/                             public site
scripts/generate-block-types.ts    block.json → TypeScript
```

## Why the blocks are in the theme

BlockStudio registers every `block.json` that contains a `blockstudio` key under the active theme's `blockstudio/` directory. `blockstudio.json` also lives in the theme. That is the documented layout ([registration](https://blockstudio.dev/docs/blocks/registration/), [block library](https://blockstudio.dev/guides/block-library/)).

The `blockstudio/path` filter replaces that scan with a single directory, so putting the blocks only in a plugin would drop the theme convention. The plugin is still symlinked: it serves `parse_blocks()` to Astro, and it keeps working if the theme file is only there for the editor.

PHP block templates (`index.php` next to `block.json`) render the block editor and a WordPress preview. Astro components render the public site. They are two views of the same `block.json` fields.

## What you need

- [Local WP](https://localwp.com/), PHP 8.2+, WordPress 6.5+
- Node 22+ and pnpm 10
- PhpStorm, opened on **this** repo
- Plugins on the Local site:
  - BlockStudio (required for the blocks to appear)
  - Rank Math Pro, with Headless CMS Support turned on
  - Fluent Forms, only if you would rather store messages in WordPress
  - LiteSpeed Cache installed for production parity, **left deactivated** locally

## Local WP

Create a site whose domain is `squarepeg-cms.local` (any domain works if `WP_URL` matches it).

From the repo root, symlink the theme and the plugin into that site. Local's folder name is the site name you chose:

```bash
LOCAL_WP="$HOME/Local Sites/squarepeg-cms/app/public"
REPO="$HOME/code/spa-headless"   # wherever you cloned this repo

ln -s "$REPO/wordpress/plugins/spa-headless-core" \
  "$LOCAL_WP/wp-content/plugins/spa-headless-core"

ln -s "$REPO/wordpress/themes/spa-headless" \
  "$LOCAL_WP/wp-content/themes/spa-headless"
```

In wp-admin:

1. Activate **Square Peg Headless Core** and the **Square Peg Headless** theme.
2. Settings → Permalinks → **Post name**.
3. Create a page with slug `blog`. Settings → Reading → a static homepage, and that page as the posts page. Posts are then `/blog/{slug}/`, which is the live site's shape. Pages stay at `/{slug}`.
4. Rank Math → switch to Advanced mode if you need to, then General Settings → Others → **Headless CMS Support**. Without it, `rankmath/v1/getHead` returns "no route".
5. Paste `wordpress/wp-config.snippet.php` into the site's `wp-config.php` and set a long random `SPA_PREVIEW_TOKEN`. Leave the token out of git.

`block.json` files reference `https://blockstudio.dev/schema/block` (and the field / settings schemas). PhpStorm picks those up for autocomplete.

The CMS front end sends `noindex`. It is a preview shell with a link to the Astro app. Public pages are served by Astro.

## Astro

```bash
pnpm install
cp astro/.env.example astro/.env
pnpm dev
```

Open http://localhost:4321. `astro/.env` is gitignored.

| Variable | Purpose |
| --- | --- |
| `WP_URL` | Local WP origin, no trailing slash. Example: `http://squarepeg-cms.local` |
| `WP_FIXTURES` | `auto` (default), `1` to skip WordPress, `0` to fail when WordPress is down |
| `PUBLIC_SITE_URL` | Astro origin. Rank Math canonicals are rewritten from `WP_URL` to this |
| `PUBLIC_GA_MEASUREMENT_ID` | GA4 id. Empty disables the tag. The live site uses `G-GQXT7PFHES`; put it here if you want it, never in source |
| `PUBLIC_TURNSTILE_SITE_KEY` / `TURNSTILE_SECRET_KEY` | Cloudflare Turnstile. Both empty skips the check |
| `SPA_PREVIEW_TOKEN` | Same value as `SPA_PREVIEW_TOKEN` in `wp-config.php` |

`pnpm check` runs the block generator and `astro check`. `pnpm build` does the same, then `astro build`. With no `.env`, `WP_URL` is unset and the build uses fixtures, which is what CI should do. Set `WP_FIXTURES=1` when you want that even if `WP_URL` is present.

Draft preview is http://localhost:4321/preview/{postId} while `pnpm dev` is running (the route is not prerendered). It sends `X-SPA-Preview-Token`. An empty token disables preview on both sides.

## Design system

The public styles are the live site's global stylesheet, copied into `astro/src/styles/global.css` from [Darkod55/spa](https://github.com/Darkod55/spa) (`src/styles/global.css`, which `BaseLayout.astro` imports). That project does not use Tailwind. Colours, the type scale, spacing, breakpoints, and the 1280px container are CSS custom properties in that file. Oswald and Montserrat are the same `@fontsource` packages the live site loads (weights 400–700); Vite serves the font files from those packages, so they are not committed as binaries.

Blocks and the header use those tokens (`.container`, `.type-display`, `.type-lead`, `var(--color-navy)`, and so on). Do not add a parallel palette.

## Menu and logo

The header reads WordPress, and falls back with the rest of the site when `WP_FIXTURES` says to.

1. Appearance → Menus → create a menu. The live header is Home, About, and Services. Tick **Primary** under Menu Settings and save.
2. “Let’s talk” is a menu item with the CSS class `cta`. In the menu editor, open Screen Options (top of the screen) and enable **CSS Classes** if that field is hidden. Add the Contact page, set its class to `cta`, and save.
3. To nest a dropdown, drag an item to the right under its parent. Desktop shows a menu; the mobile panel indents the children.
4. Appearance → Customize → Site Identity → **Logo**. The site title is the alt text when the logo has none, and the text in the header when no logo is set. PNG, WebP, and JPEG work. An SVG is used as an image (not redrawn) when WordPress accepts the upload; if it has no pixel size, the viewBox supplies width and height.
5. On that same Site Identity screen, set **Site Icon**. Astro prints it as the favicon: 32 and 192 `rel="icon"` links, an apple-touch icon at 180, and the 270 tile image. An SVG site icon is a single icon link. If Rank Math's head HTML already contains icon tags, those are removed so the page does not list two favicons.

`GET /wp-json/spa/v1/menus/primary` returns the tree. Links on the WordPress host are paths such as `/about`. `GET /wp-json/spa/v1/site` returns the title, description, logo, site icon, and announcement bar. The logo URL stays on the CMS, because the file is in the media library.

The red strip above the header is Appearance → Customize → **Announcement bar**. It has a show toggle, the message, the link label, the link URL, and whether the link opens in the same tab or a new one. A line break in the message is hidden on desktop and shown on screens under 768px, which is how the live bar wraps. The bar does not dismiss; it stays until the toggle is off, and it sticks with the header. The fixture uses the live sentence and “Book your free website audit”.

## Slugs

Migration has to keep the live paths. Fixtures already use them: `/`, `/about`, `/services`, `/contact`, `/blog`, `/privacy-policy`, `/cookie-policy`, `/terms-and-conditions`, and the `/blog/…` posts currently on the site. The Astro route is the WordPress permalink, not a renamed slug.

## Blocks

Three examples ship with the theme:

| Block | What it is |
| --- | --- |
| `spa/hero` | Kicker, heading, text, two links, optional image |
| `spa/section` | Heading plus one `InnerBlocks` region (core paragraph, heading, image, list, buttons, group, columns, plus hero and card grid) |
| `spa/card-grid` | Heading and a repeater of cards |

Shared spacing toggles live in `blockstudio/fields/section/field.json` and are pulled in with `"type": "custom/section"`.

To add a block, follow [AGENTS.md](AGENTS.md). Short version: `block.json`, `index.php`, an Astro component with a slot, a `*.contract.ts` that lists every field (repeater rows included), register it in `BlockRenderer.astro`, run `pnpm generate`. The generator fails if a BlockStudio block has no component, or if a field was added, renamed, or removed without updating the contract and the component. `astro check` fails as well, because the contract must `satisfy` the generated field map.

Core blocks the renderer knows: paragraph, heading, image, list (and list item), buttons (and button), group, columns (and column). Any other block, including classic HTML, renders the saved `innerHTML`, with inner blocks spliced in from `innerContent`.

Block JSON on the REST API:

- `blocks` on `wp/v2/pages` and `wp/v2/posts`
- `GET /wp-json/spa/v1/routes`
- `GET /wp-json/spa/v1/content?path=/about`
- `GET /wp-json/spa/v1/preview/123` with header `X-SPA-Preview-Token`

Each block is `blockName`, `attrs`, `innerBlocks` (recursive), `innerHTML`, `innerContent`. CORS allows `http://localhost:4321` and `http://127.0.0.1:4321`, plus origins listed in `SPA_FRONTEND_ORIGINS`. That replaces WordPress's default of reflecting any origin.

Custom fields are post meta, not ACF:

- `spa_eyebrow` (string)
- `spa_hide_title` (boolean) — the layout skips the page title when this is true, and also when a hero block is present

Both are `show_in_rest`. Their auth callback returns true so anonymous reads include them. Use a stricter callback for anything that is not public.

## SEO and the contact form

Rank Math's headless endpoint is:

```text
GET {WP_URL}/wp-json/rankmath/v1/getHead?url={absolute WordPress URL}
```

The `url` parameter has to be a full URL, not a path. The response is JSON with `success` and `head` (the meta tags as HTML). The layout injects `head`. See [Rank Math's headless CMS guide](https://rankmath.com/kb/headless-cms-support/).

`POST /api/contact` checks Turnstile when `TURNSTILE_SECRET_KEY` is set, then returns 501 until a mail transport exists. That matches the current site's Astro endpoint. If you want the submission stored in WordPress instead, point the form at Fluent Forms and leave this route unused.

## Scripts

```bash
pnpm test     # block.json parser + the PHP block normalizer
pnpm check    # generate types, then astro check
pnpm build    # generate types, then astro build
pnpm dev      # Astro at localhost:4321
```

Package manager is pnpm workspaces. The only workspace package is `astro` (`@spa/astro`). The generator runs from the repo root with `tsx`.
