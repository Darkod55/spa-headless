# Design tokens

The public stylesheet is `astro/src/styles/global.css`, ported from [Darkod55/spa](https://github.com/Darkod55/spa) `src/styles/global.css`. That site does not use Tailwind. Do not add a second palette.

Oswald and Montserrat (weights 400–700) are self-hosted woff2 files in `wordpress/themes/spa-sage/resources/fonts` (OFL). The theme tokens are `resources/css/theme/variables.css` and `fonts.css`. Astro still loads `@fontsource` for the public site.

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

Brand utility classes live in `wordpress/themes/spa-sage/resources/css/components/spa-brand-colours.css`, which Astro imports from `Base.astro`:

- `.has-text-{navy|red|royal|black|white}`
- `.has-bg-{navy|red|royal|black|white}` for later

Hex is only in the token files. Fields store `var(--color-*)` plus a slug.

## Add a colour

1. Add the hex token to `astro/src/styles/global.css` and to `wordpress/themes/spa-sage/resources/css/theme/variables.css`.
2. Add one row to `textColours` and `bgColours` in `app/Traits/blocks/trait-blocks.php`: `value` `var(--color-…)`, `name`, `slug`.
3. Add `.has-text-{slug}` and `.has-bg-{slug}` to `resources/css/components/spa-brand-colours.css`.
4. If the hero should accept it as a background, add `hero--bg-{slug}` with `--hero-bg`, `--hero-shape-1`, `--hero-shape-2`, and `--hero-text` in `resources/css/components/spa-hero-banner.css`, and add the slug to `brandColourSlugs` in `astro/src/lib/blocks/color.ts` and `spa_sage_hero_modifier()`.
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
