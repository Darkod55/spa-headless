# Architecture

WordPress authors the content. Astro renders the public site. Nothing in this repo deploys.

```
wordpress/themes/spa-sage/             active Sage theme (blocks in app/blocks)
wordpress/themes/spa-headless/          deprecated theme, kept in the repo
wordpress/plugins/spa-headless-core/    REST, CORS, preview, meta, block normalizer
astro/                                  public site
scripts/generate-block-types.ts         block.json → astro/src/generated/blocks.ts
```

The active theme is spa-sage. BlockStudio's scan root is `app/blocks` (`blockstudio/path` in `app/Traits/blocks/trait-blocks.php`). `spa/hero-banner-main` and `spa/hero-banner-default` live there. Older blocks (`spa/hero`, `spa/section`, `spa/card-grid`) still live in the deprecated spa-headless theme and are still generated so the Astro components keep typechecking. Do not add new blocks to spa-headless.

The plugin does not register blocks. It turns `parse_blocks()` into the JSON Astro renders. It reads theme mods (`custom_logo`, `spa_alt_logo`, `spa_alt_logo_in_footer`, `spa_announcement_*`) and the menu locations `primary`, `footer`, `legal`, `footer-social` from whichever theme is active.

## Request flow

1. Astro asks `GET /wp-json/spa/v1/routes` for every public path.
2. Each path is fetched with `GET /wp-json/spa/v1/content?path=/about`.
3. The layout also fetches `GET /wp-json/spa/v1/site`, `GET /wp-json/spa/v1/menus/{location}`, and Rank Math `getHead`.
4. `BlockRenderer.astro` walks `blocks`. A known block gets an Astro component. Anything else renders saved `innerHTML`, with inner blocks spliced in from `innerContent`.

`blocks` is only on `spa/v1` (`content` and `preview`). Astro does not read `wp/v2` for page content. `spa/v1/content` is the path resolver so Astro does not guess permalinks. Paths have no trailing slash. Posts live under `/blog/{slug}`. Pages are `/{slug}`. Keep those slugs identical to the live site.

## Endpoints

| Route | What it returns |
| --- | --- |
| `GET /wp-json/spa/v1/routes` | `{ routes: [{ path, id, type, slug }] }` |
| `GET /wp-json/spa/v1/content?path=/about` | One page or post: title, blocks, meta |
| `GET /wp-json/spa/v1/menus/{location}?path=/about` | Nested menu. Unknown location is 404. A registered location with no menu is `{ items: [] }` |
| `GET /wp-json/spa/v1/site` | Title, description, `logo`, `altLogo`, `footerLogo`, `siteIcon`, `announcement` |
| `GET /wp-json/spa/v1/preview/{id}` | Draft content. Requires header `X-SPA-Preview-Token` |
| `GET /wp-json/rankmath/v1/getHead?url={absolute WP URL}` | `{ success, head }` HTML tags. Needs Rank Math Headless CMS Support |

Menu URLs on the WordPress host become root-relative paths. Logo and icon URLs stay on the CMS, because the files are in the media library. Rank Math `head` is rewritten from `WP_URL` to `PUBLIC_SITE_URL`. When a site icon is set, icon tags already in that `head` are removed.

Each block is `blockName`, `attrs`, `innerBlocks`, `innerHTML`, `innerContent`. Before that JSON is returned, `spa_headless_normalize_blocks()`:

- drops whitespace-only freeform blocks
- copies `attrs.blockstudio.attributes` onto `attrs` and keeps the nest
- rewrites a hero `image` (top-level or nested) to `{ id, url, alt, width, height }`

`set` classes are not in `innerHTML`. Astro adds them from `attrs`. See [blockstudio-fields-and-extensions.md](blockstudio-fields-and-extensions.md).

CORS allows `http://localhost:4321` and `http://127.0.0.1:4321`, plus origins in `SPA_FRONTEND_ORIGINS`.

## Fixtures

`astro/src/lib/wp/client.ts` reads `WP_FIXTURES`:

- unset or `auto` — use WordPress, and fall back to `astro/src/lib/wp/fixtures.ts` if it is unreachable or `WP_URL` is empty
- `1` / `true` / `force` — always fixtures
- `0` / `false` / `off` — fail the build when WordPress is down

`/preview/:id` never uses fixtures.

An empty WordPress page is not replaced with the fixture hero. If localhost:4321 is showing sample content, a grey bar at the top says so. No bar means the HTML came from the published WordPress page.

The block editor loads a post from `wp/v2` and parses `content.raw`. It also treats a `blocks` property on that record as the already-parsed editor blocks (`name`, `attributes`, `clientId`). Do not register a REST field with that name. A `parse_blocks()` array (`blockName`, `attrs`) in that slot replaces `content.raw`, and the canvas opens empty for every post, on every theme. Saves still write `post_content` from the in-session blocks. `spa/v1` keeps the name `blocks` because the editor never loads those routes.

## Checklist

- New public data goes through `spa/v1`, not a field on `wp/v2`, and not a one-off fetch in a component.
- A new menu location is registered in the theme and read with `getMenu`. An empty assignment is an empty list.
- Slugs match the live site.
- Fixture mode still builds with `WP_URL` unset.

## Common mistakes

- Treating `innerHTML` as the place extension classes are saved. They are not.
- Pointing Rank Math `getHead` at a path. The `url` argument is an absolute WordPress URL.
- Adding a block renderer that ignores `attrs.blockstudio.attributes` after a WordPress upgrade of the normalizer. Read the flattened `attrs`.
- Fetching the live site's repo or host as if it were this codebase.
- Registering a wp/v2 REST field named `blocks` on posts or pages. That key belongs to the block editor. Put the parsed tree on `spa/v1` only, and leave `content.raw` untouched.
