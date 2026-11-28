# Square Peg headless

WordPress is the CMS. Astro is the public site. This is the rebuild of [thesquarepeg.agency](https://thesquarepeg.agency). Deployment is out of scope.

## Read before you change anything

1. [docs/architecture.md](docs/architecture.md) — how a page gets from WordPress to Astro.
2. [docs/blockstudio-blocks.md](docs/blockstudio-blocks.md) — add or change a block.
3. [docs/design-tokens.md](docs/design-tokens.md) — colours, type, header height.
4. [docs/blockstudio-fields-and-extensions.md](docs/blockstudio-fields-and-extensions.md) — shared fields, core block extensions, the `set` gotcha.
5. [docs/theme-customizer-and-menus.md](docs/theme-customizer-and-menus.md) — menus, logos, announcement, site icon.
6. [docs/local-setup.md](docs/local-setup.md) — Local WP, symlinks, env, commands.

## Golden rules

- `block.json` is the source of truth for fields. Do not invent a parallel list in PHP or TypeScript. Run `pnpm generate` after a field change. `pnpm check` and `pnpm build` fail when the Astro contract does not match.
- Never edit, branch, or push [Darkod55/spa](https://github.com/Darkod55/spa). Never change the live host. Read that repo only as a reference.
- No secrets in git. Commit `astro/.env.example` only. `.env`, preview tokens, Turnstile keys, and uploads stay out.
- The editor and the public page share one stylesheet and the same class names. Do not restyle a block only in Astro.
- BlockStudio `set` runs on `render_block` and is not saved into `innerHTML`. Extension values live at `attrs.blockstudio.attributes`. The REST normalizer copies them onto `attrs`. Astro applies the classes from `attrs`.
- Keep `pnpm test`, `pnpm check`, and `pnpm build` green. Builds with no `WP_URL` use fixtures.

## Common mistakes

- Registering a REST field named `blocks` on `wp/v2` posts or pages. The editor treats that property as its own block list and then ignores `content.raw`, so the canvas is empty. Parsed blocks belong on `spa/v1` only. Do not rewrite `content.raw` to compensate.

The active theme is `wordpress/themes/spa-sage` (Sage 11, Tailwind 4, Vite, Blade, Acorn). BlockStudio's root is `app/blocks` via the `blockstudio/path` filter. Shared fields are `app/blocks/fields`. Core extensions are `app/blocks/extensions`. Block namespace is `spa/*`. Blocks: `spa/hero-banner-main`, `spa/hero-banner-default` (inner `spa/badge`, `core/heading`, `core/paragraph`), and `spa/badge`. The deprecated theme still supplies `spa/hero`, `spa/section`, and `spa/card-grid`. Do not add blocks there.

Theme build (npm stays inside the theme; the repo root stays pnpm):

```bash
cd wordpress/themes/spa-sage
composer install
npm install
npm run build
```

Do not commit `vendor/`, `node_modules/`, or `public/build/`.

## Commands

```bash
pnpm test
pnpm check
pnpm build
pnpm dev
```

`pnpm dev` is Astro at http://localhost:4321. WordPress is Local WP, not this repo.
