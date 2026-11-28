# Design tokens

The public stylesheet is `astro/src/styles/global.css`, ported from [Darkod55/spa](https://github.com/Darkod55/spa) `src/styles/global.css`. That site does not use Tailwind. `DESIGN.md` in that repo names this file as the brand source. `tokens.css` and `theme.css` there are an older EmDash template and are not the live palette.

The theme copy is `wordpress/themes/spa-sage/resources/css/theme/variables.css`. Hex lives in those two files. The BlockStudio picker stores `var(--color-…)` plus a slug. Astro and the editor turn the slug into `.has-text-*` / `.has-bg-*`.

Oswald and Montserrat (weights 400–700) are self-hosted woff2 files in `wordpress/themes/spa-sage/resources/fonts` (OFL). Astro still loads `@fontsource` for the public site.

`--header-height` is `5.5rem`, and `4.5rem` under 640px. The homepage hero subtracts it. It does not subtract the announcement bar. `--container-max` is `1280px`. `--gutter` is `24px`.

## Colours

Audited against `Darkod55/spa` `src/styles/global.css`, `DESIGN.md`, `src/design/surfaces.json`, and component CSS. Legacy names (`navy`, `red`, `royal`) are aliases of `primary`, `secondary`, and `tertiary`. Saved heroes that say `navy` still render.

| Name | Slug | Hex | Where it is used on the live site | Picker |
| --- | --- | --- | --- | --- |
| Primary | `primary` | `#031433` | Heroes, footer, dark sections (`.bg-primary`). Alias `navy` | Yes, background and text |
| Secondary | `secondary` | `#C8102E` | Buttons, labels, accents on light, links. Alias `red` | Yes, background and text |
| Tertiary | `tertiary` | `#012169` | Diagonal bands, secondary dark sections. Alias `royal` | Yes, background and text |
| White | `white` | `#FFFFFF` | Page canvas, light sections | Yes, background and text |
| Gray | `gray` | `#F4F5F7` | Light section surface (`.bg-gray`). Alias `grey-50` | Yes, background and text |
| Black | `black` | `#000000` | Black sections | Yes, background and text |
| Secondary deep | `secondary-deep` | `#970C23` | Closing CTAs (`.bg-red-deep`), link hover. Alias `red-deep` | Yes, background and text |
| Red on navy | `secondary-on-primary` | `#E8324C` | Text and marks on primary. Never use `#C8102E` there. Alias `red-on-navy` | Text only |
| Red on royal | `secondary-on-tertiary` | `#FF8A9A` | Text and marks on tertiary. Alias `red-on-royal` | Text only |
| Grey 100 | `grey-100` | `#E8EBF0` | Code and quote panels, style-guide rules | No |
| Grey 200 | `grey-200` | `#E3E6EC` | Hairline borders in posts and legal pages | No |
| Grey 400 | `grey-400` | `#9AA3B5` | Form borders, muted text utility, hero photo placeholder | No |
| Text body | `text-body` | `#3A4560` | Body copy on light surfaces | No |
| Text on dark | `text-on-dark` | `#D2D8E3` | Body copy on primary and black | No |
| Text on royal | `text-on-royal` | `#D6DCE8` | Body copy on tertiary. Alias `text-on-tertiary` | No |
| Text muted dark | `text-muted-dark` | `#AEB7C7` | Meta text on dark surfaces | No |
| Text on red | `text-on-red` | `#FBE7EA` | Body copy on secondary and secondary deep. Alias `text-on-secondary` | No |
| Success | `success` | `#0F5132` | Contact form success text | No |
| Success background | `success-bg` | `#E8F5EC` | Contact form success panel | No |
| Danger background | `danger-bg` | `#FDE8EC` | Contact form error panel | No |
| Team name | `team-name` | `#31394A` | Team card name | No |
| Team rule | `team-rule` | `#C9CFDA` | Team card divider | No |

Hero backgrounds accept primary/navy, secondary/red, tertiary/royal, white, gray, black, and secondary deep. Red on navy and red on royal are text colours, so they are not hero backgrounds. An unknown hero background still falls back to navy.

White text on a white background is not allowed. The white and gray heroes use primary text (`--hero-text: #031433`).

## Not part of the live brand

`tokens.css` and `theme.css` in Darkod55/spa belong to an older EmDash template (`DESIGN.md` says to ignore them). They include a warm ink palette (`#141312`, `#fafaf9`, `#d97706`, `#22c55e`, and the rest of that file) and a theme.css override that sets `--color-brand` to royal `#012169`. The live marketing site uses `global.css` instead. Those template colours were not added.

Sage still has utility names the button CSS references (`gold`, `navy-light`, `coral-dark`, `light`, `dark-blue`, `white-75`). They alias real brand tokens so the theme builds. They are not live colours and they are not in the picker.

Component alphas (white at 6–30%, navy at 5%, red at 50% on the hero squares) stay as one-off alpha values. They are not separate swatches.

## Add a colour

1. Add the hex to `astro/src/styles/global.css` and the same variable to `wordpress/themes/spa-sage/resources/css/theme/variables.css`.
2. If it is a brand surface or an accessible text tint, add a row in `populateTextColours` / `populateBGColours` in `app/Traits/blocks/trait-blocks.php`. Utility shades stay tokens only.
3. Add `.has-text-{slug}` and, for a surface, `.has-bg-{slug}` in `resources/css/components/spa-brand-colours.css`.
4. If a hero should accept it, add `hero--bg-{slug}` and map the slug in `heroBackgroundClass` and `spa_sage_hero_modifier()`.
5. Extend the colour tests. Run `pnpm test`, then `npm run build` inside the theme so `theme.json` picks up the new `--color-*`.

## Checklist

- The new variable exists in the theme tokens and in `resources/css/editor-canvas.css`. Swatches render in the wp-admin document, not the editor iframe.
- The dataset `value` is a `var(--color-…)`, not a hex string.
- `colorSlug()` accepts the slug, the `var(--color-…)` string, and `{ slug }`, including hyphenated slugs.

## Common mistakes

- Hard-coding a hex in a block stylesheet.
- Adding the colour only to `global.css`, then wondering why the editor swatch is blank.
- Using secondary `#C8102E` for text on primary or tertiary. Use red on navy or red on royal.
- Forgetting `.hero__copy .has-text-*`. The hero heading colour is more specific than a single class on its own.
