# Square Peg blocks

Namespace `spa/*`. Scan root: `app/blocks` (`blockstudio/path`). Shared fields: `app/blocks/fields`. Core extensions: `app/blocks/extensions` (not Astro blocks).

Edy Nathan section blocks were not copied. The blocks in this theme are:

| Block | Purpose | Children |
| --- | --- | --- |
| `spa/hero-banner-main` | Homepage hero, viewport height minus `--header-height` | `core/heading`, `core/paragraph` |
| `spa/hero-banner-default` | Inner-page hero, 540px | `core/heading`, `core/paragraph` |

Both reuse `custom/background-colour` and store the attribute as `background` (the shared field id is `bg_colour`; the block overrides it). Default is `var(--color-navy)`. Markup is `app/blocks/hero-banner/template.php`. CSS is `resources/css/components/spa-hero-banner.css`, also imported by the Astro hero.

`spa/hero`, `spa/section`, and `spa/card-grid` still live in the deprecated `spa-headless` theme so existing pages and the type generator keep working. New blocks go in spa-sage.
