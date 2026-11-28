# Local setup

WordPress runs in [Local WP](https://localwp.com/). Astro runs at http://localhost:4321 and reads `WP_URL` from `astro/.env`. Open this repo in PhpStorm, not a copy inside Local, so edits hit the symlinks.

You need PHP 8.2+, WordPress 6.5+, Node 22+, and pnpm 10. BlockStudio on the Local site is 7.7.x (7.7.15 is the known-good version).

## Plugins

- BlockStudio — required
- Rank Math Pro — Headless CMS Support on (General Settings → Others, advanced mode)
- Fluent Forms — only if messages should stay in WordPress
- LiteSpeed Cache — installed for production parity, **left off** locally

## Symlinks

```bash
LOCAL_WP="$HOME/Local Sites/squarepeg-cms/app/public"
REPO="$HOME/code/spa-headless"

ln -s "$REPO/wordpress/plugins/spa-headless-core" \
  "$LOCAL_WP/wp-content/plugins/spa-headless-core"

ln -s "$REPO/wordpress/themes/spa-headless" \
  "$LOCAL_WP/wp-content/themes/spa-headless"
```

Then in wp-admin: activate **Square Peg Headless Core** and the **Square Peg Headless** theme. Settings → Permalinks → **Post name**. Create a page with slug `blog` and set it as the posts page so posts are `/blog/{slug}`.

Paste `wordpress/wp-config.snippet.php` into the site's `wp-config.php` and set a long random `SPA_PREVIEW_TOKEN`. Do not commit that value.

## Env

```bash
pnpm install
cp astro/.env.example astro/.env
pnpm dev
```

| Variable | Purpose |
| --- | --- |
| `WP_URL` | Local origin, no trailing slash, for example `http://squarepeg-cms.local` |
| `WP_FIXTURES` | `auto` (default), `1` to skip WordPress, `0` to fail when WordPress is down |
| `PUBLIC_SITE_URL` | Astro origin. Rank Math canonicals are rewritten to this |
| `PUBLIC_GA_MEASUREMENT_ID` | GA4 id. Empty disables the tag. The live id is `G-GQXT7PFHES`; put it in `.env`, never in source |
| `PUBLIC_TURNSTILE_SITE_KEY` / `TURNSTILE_SECRET_KEY` | Both empty skips the check |
| `SPA_PREVIEW_TOKEN` | Same value as `wp-config.php` |

`astro/.env` is gitignored.

## Commands

```bash
pnpm test     # unit tests and the PHP normalizer
pnpm check    # generate types, then astro check
pnpm build    # generate types, then astro build
pnpm dev      # Astro at localhost:4321
```

With no `.env`, `WP_URL` is unset and the build uses fixtures. That is what CI should do.

Draft preview is http://localhost:4321/preview/{postId} while `pnpm dev` is running. It sends `X-SPA-Preview-Token`. An empty token disables preview on both sides. Preview never uses fixtures.

## Try a hero in Local WP

1. Edit the homepage. Insert **Hero Banner Main**. The background defaults to Navy (`var(--color-navy)`).
2. Set the image in the sidebar. Edit the H1 and paragraph in the canvas. On a heading or paragraph, the styles tab has **Text colour**.
3. On any other page, insert **Hero Banner Default**.
4. Publish and reload http://localhost:4321.

A hero already saved as `"background": "navy"` still renders. You do not have to re-save it. The new picker stores `var(--color-navy)` (and a slug). Until you pick Navy again and update, the control may not show that old string as the selected swatch.

## Checklist

- Theme and plugin are symlinks, not copies.
- BlockStudio and the Square Peg theme are active.
- Permalinks are Post name, and the posts page slug is `blog`.
- `SPA_PREVIEW_TOKEN` matches on both sides and is not in git.
- `pnpm test`, `pnpm check`, and `pnpm build` pass before you push.

## If the editor is empty but Astro still shows the hero

Do not press Update. That would save the empty canvas over the published page.

The grey bar on http://localhost:4321 means Astro is not reading WordPress. No bar means the published page still has the hero, and the editor is showing an autosave or a browser backup.

In Local’s site shell (read-only):

```bash
wp post list --post_type=page --fields=ID,post_title,post_name,post_status
wp option get page_on_front
wp post get <homepage ID> --field=post_content | head -50
wp post list --post_type=revision --post_parent=<homepage ID> --fields=ID,post_date,post_name,post_title
```

Autosaves are named like `123-autosave-v1`. Also open the browser console on the editor and look for a red error before touching the page. `curl -s http://squarepeg-cms.local/wp-json/spa/v1/content?path=/` shows what Astro reads. `wp-content/debug.log` shows a PHP fatal if block registration failed.

To put a revision back, after you have identified the one that still contains `wp:spa/hero-banner-main`:

```bash
wp post restore <revision ID>
```

## Common mistakes

- Committing `.env`, `node_modules`, `vendor`, `dist`, or uploads.
- Running Astro against a `WP_URL` that has a trailing slash. The client strips one, but the env example does not use one.
- Turning LiteSpeed on locally and then debugging a stale REST response.
- Editing files inside `~/Local Sites/.../wp-content` instead of this repo. The symlink should point here.
