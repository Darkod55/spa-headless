# Design tokens

The public stylesheet is `astro/src/styles/global.css`, ported from [Darkod55/spa](https://github.com/Darkod55/spa) `src/styles/global.css`. That site does not use Tailwind. Do not add a second palette.

Oswald and Montserrat (weights 400–700) come from `@fontsource`. Vite serves those files. The editor cannot see `node_modules` through the theme symlink, so `assets/editor.css` loads four latin woff2 files copied into `assets/fonts/`.

## Tokens

| Token | Value | Where |
| --- | --- | --- |
| `--color-navy` | `#031433` (`--color-primary`) | `global.css`, `editor.css` |
| `--color-red` | `#c8102e` (`--color-secondary`) | same |
| `--color-royal` | `#012169` (`--color-tertiary`) | same |
| `--color-black` | `#000000` | same |
| `--color-white` | `#ffffff` | same |
| `--font-heading` | Oswald | same |
| `--font-body` | Montserrat | same |
| `--container-max` | `1280px` | `global.css` |
| `--gutter` | `24px` | `global.css` |
| `--header-height` | `5.5rem`, `4.5rem` under 640px | `header.css`, `hero-banner.css`, `editor.css` |

`--header-height` is the header bar at rest. The scrolled bar sets `min-height: 0`. The homepage hero subtracts this variable. It does not subtract the announcement bar.

Brand utility classes live in `wordpress/themes/spa-headless/assets/brand-colours.css`, which Astro imports from `Base.astro` and the editor enqueues:

- `.has-text-{navy|red|royal|black|white}`
- `.has-bg-{navy|red|royal|black|white}` for later

Hex is only in the token files. Fields store `var(--color-*)` plus a slug.

## Add a colour

1. Add the hex token to `astro/src/styles/global.css` and the same variable to `assets/editor.css`.
2. Add one row to `spa_brand_colours()` in `wordpress/themes/spa-headless/inc/colors.php`: `value` `var(--color-…)`, `name`, `slug`.
3. Add `.has-text-{slug}` and `.has-bg-{slug}` to `assets/brand-colours.css`.
4. If the hero should accept it as a background, add `hero--bg-{slug}` with `--hero-bg`, `--hero-shape-1`, `--hero-shape-2`, and `--hero-text` in `assets/hero-banner.css`, and add the slug to `brandColourSlugs` in `astro/src/lib/blocks/color.ts` and the PHP map in `inc/hero-banner.php`.
5. Extend the colour tests. Run `pnpm test`.

White text on a white background is not allowed. The white hero uses navy text (`--hero-text: #031433`).

## Checklist

- The new variable exists in the editor stylesheet, not only in Astro.
- The dataset `value` is a `var(--color-…)`, not a hex string.
- `colorSlug()` accepts the slug, the `var(--color-…)` string, and `{ slug }`.

## Common mistakes

- Hard-coding a hex in a block stylesheet.
- Adding the colour only to `global.css`, then wondering why the editor swatch is blank.
- Forgetting `.hero__copy .has-text-*`. The hero heading colour is more specific than a single class on its own.
