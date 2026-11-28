# Fields and extensions

BlockStudio 7.7.x. Patterns follow reusable fields plus a populate dataset, not a copied option list in every block.

## Dataset

`wordpress/themes/spa-sage/app/Traits/blocks/trait-blocks.php` hooks `blockstudio/blocks/attributes/populate` and registers `textColours`, `tagBgColours`, and `bgColours`. Each option is:

```php
array(
    'value' => 'var(--color-navy)',
    'name'  => 'Navy',
    'slug'  => 'navy',
)
```

Hex stays in CSS. See [design-tokens.md](design-tokens.md).

## Reusable fields

| Field | File | Id | Notes |
| --- | --- | --- | --- |
| `custom/background-colour` | `spa-sage/app/blocks/fields/background-colour/field.json` | `bg_colour` | Dataset `bgColours`. Heroes override the id to `background` so the Astro contract stays `background`. |
| `custom/text-colour` | `spa-sage/app/blocks/fields/text-colour/field.json` | `text_colour` | Colour swatches save `{ value, name, slug }`. `set` writes class `has-text-{slug}` and `color: {value}`. In the editor those land on the block wrapper, not the inner RichText element. |
| `custom/typography` | `spa-sage/app/blocks/fields/typography/field.json` | `text_colour`, `text_size`, `text_weight` | Heading and paragraph. Size classes are the live scale (`fs-display` … `fs-meta`). Weight classes are `fw-400` … `fw-700`. The editor hero lets the inner heading inherit the wrapper's colour, size, and weight. |
| `custom/section` | `blockstudio/fields/section/field.json` | `removeBottomPadding`, `accentBg` | Shared section toggles |

Use one from a block:

```json
{
  "type": "custom/background-colour",
  "overrides": {
    "bg_colour": { "id": "background", "default": "var(--color-navy)" }
  }
}
```

Overrides replace that property. A colour `default` is the option value string (`var(--color-navy)`), which is what BlockStudio's colour field expects. The generator still types the attribute as `string | SpaColorValue`, because older heroes stored `"navy"`.

`colorSlug()` in `astro/src/lib/blocks/color.ts` accepts:

- `"navy"`
- `"var(--color-navy)"`
- `{ slug: "navy", value, name }`

Unknown values are null. The hero then falls back to navy.

## Extensions

`app/blocks/extensions/button.json` extends `core/button` with button type (`primary`, `light`, `dark`, `outline-light`, `text`) and an Arrow toggle. `app/blocks/extensions/heading.json` and `paragraph.json` extend `core/heading` and `core/paragraph` with `custom/typography` (text colour, font size, font weight). `set` paints the editor and the WordPress preview. It is not saved into `innerHTML`. The values live at `attrs.blockstudio.attributes`. `spa_headless_normalize_blocks()` copies them onto `attrs`. `Heading.astro` and `Paragraph.astro` add the same classes from those attributes.

| Control | Saved shape | Editor output | Astro class |
| --- | --- | --- | --- |
| Text colour | `{ value: "var(--color-primary)", name, slug }` or a legacy slug / `var(--color-…)` string | inline `color: var(--color-primary)` | `has-text-primary` |
| Font size | `{ value: "fs-h2", label }` or the string `fs-h2` | class `fs-h2` | `fs-h2` |
| Font weight | `{ value: "fw-700", label }` or the string `fw-700` | class `fw-700` | `fw-700` |

Size and weight CSS is `resources/css/components/spa-typography.css`, imported by the theme and by Astro. Tokens are `--fs-*` (the live type scale). A chosen size or weight overrides the hero's default type; a heading with neither still uses the hero size.

There is no PHP template for an extension. The file is not named `block.json`.

## The `set` gotcha

`set` is applied in `render_block` with `WP_HTML_Tag_Processor`. It is not written into the saved `innerHTML`. The chosen colour is stored at `attrs.blockstudio.attributes`.

`spa_headless_normalize_blocks()` copies those keys onto `attrs` and leaves the nest in place. Hero image normalisation looks at the top-level `image` and, if that is missing, at the nested one.

Astro does not read `innerHTML` for these controls. `typographyClasses()` turns the flattened attributes into classes, and `addClassToRoot()` puts them on the heading or paragraph. A colour may be a slug, `var(--color-…)`, or `{ slug, value, name }`. A select may be the class string or `{ value, label }`.

## Checklist

- A new shared control is a `fields/<name>/field.json`, not a paste into every block.
- The populate key matches `populate.custom`.
- Editor CSS defines the variable the option's `value` points at.
- A fixture includes both a legacy string and a `{ slug }` object if both can be saved.
- The normalizer test covers a nested extension attribute.

## Common mistakes

- Expecting `has-text-navy` inside `innerHTML` from the REST response. Add it in the Astro component.
- Setting a colour `default` to the slug `navy`. The option value is `var(--color-navy)`. Old content that already says `"navy"` still renders; the picker will not show it as selected until the block is saved again.
- Letting a top-level `text_colour: ""` hide the object in `blockstudio.attributes` on the spa/v1 payload. An empty value loses there. The editor reads `content.raw` as saved and does not get a rewritten copy.
- Registering the dataset in a plugin the theme does not load. It lives in the theme and is required from `functions.php`.
