# BlockStudio blocks

`block.json` is the source of truth. The active theme is `wordpress/themes/spa-sage`. BlockStudio registers every `block.json` under `app/blocks` (the `blockstudio/path` filter). Shared fields are `app/blocks/fields`. Core extensions are `app/blocks/extensions` and are not Astro blocks. `wordpress/themes/spa-headless` is deprecated: leave it in the repo, do not add blocks there. PHP templates render the editor. Astro renders the public site. Both must emit the same class names and share one CSS file.

BlockStudio on this project is 7.7.x (Local WP is 7.7.15).

## Create a block

1. Add `wordpress/themes/spa-sage/app/blocks/<slug>/block.json`.
   - `$schema`: `https://blockstudio.dev/schema/block`
   - `name`: `spa/<slug>` (one slash)
   - Fields live in `blockstudio.attributes` with `id` and `type`.
   - Tabs and groups without an `id` are layout only. A group with an `id` prefixes child ids (`presentation` + `align` → `presentation_align`).
   - Reuse a field with `{ "type": "custom/<name>" }`. Overrides change the default. See [blockstudio-fields-and-extensions.md](blockstudio-fields-and-extensions.md).
   - `style` and `editorStyle` should point at the same CSS file the Astro component imports.
2. Add `index.php` beside it. Attributes are `$a`. Escape output. One `<InnerBlocks />` per block. The public markup is not this file's job, but the class structure must match the Astro component.
3. Put the CSS in `resources/css/components/` so the block `style` / `editorStyle` and the Astro import are the same file. `scripts/generate-block-types.ts` reads sage `app/blocks` for new blocks and still reads the deprecated theme for `spa/hero`, `spa/section`, and `spa/card-grid`. It skips `fields`, `extensions`, and the shared `hero-banner` template folder.
4. Add `astro/src/components/blocks/spa/<PascalSlug>.astro` with a `<slot />`. Use the generated attribute type. The file must mention every field id.
5. Add `astro/src/components/blocks/spa/<slug>.contract.ts`:

   ```ts
   export const exampleFields = {
     heading: true,
     cards: { title: true, text: true },
   } as const satisfies SpaExampleFieldMap;
   ```

   Import that contract from the component.
6. Register `'spa/<slug>'` in `astro/src/components/blocks/BlockRenderer.astro` and pass inner blocks through the slot.
7. Add a fixture in `astro/src/lib/wp/fixtures.ts` so `astro build` renders the block without WordPress.
8. Run `pnpm generate`, then `pnpm test`, `pnpm check`, and `pnpm build`.

`pnpm generate` reads every `block.json`, writes `astro/src/generated/blocks.ts`, and exits non-zero when:

- a BlockStudio block has no Astro component
- the component does not import its contract or never mentions a field id
- the contract does not match `block.json`, including repeater rows
- `BlockRenderer.astro` does not register the block name

`astro check` also fails when the contract does not `satisfy` the generated field map.

## Hero banners

Two blocks, not a variant field. The height is the block.

| Block | Height |
| --- | --- |
| `spa/hero-banner-main` | `100vh`, then `100svh`, then `100dvh`, minus `--header-height` |
| `spa/hero-banner-default` | `540px` on desktop, the live inner-page hero |

They share `app/blocks/hero-banner/template.php` and `resources/css/components/spa-hero-banner.css`. Background is `custom/background-colour` (default `var(--color-navy)`). Shapes follow `hero--bg-{slug}`. A saved string `"navy"` still renders. The image field is a single BlockStudio `files` field. REST returns `{ id, url, alt, width, height }`. Inner blocks are `spa/badge`, `core/heading`, `core/paragraph`, and `core/buttons`. A new hero starts badge, then H1, then paragraph, then two buttons: primary with an arrow, and light without one. A hero that already has inner blocks is left as saved. The space between those blocks is `--hero-copy-gap` (`22px`, `18px` on a tablet) on `.hero__copy` and on the editor's inner block list.

## Badge

`spa/badge` is the live eyebrow (`Label.astro`, default size). It is a parallelogram from `clip-path`, slant `12px`, padding `8px 26px`, Oswald `1rem` / weight `500`, letter-spacing `0.12em`, uppercase. Default background is secondary (`#c8102e`) and default text is white. White text on a white or gray background becomes navy. Text is RichText, edited in the canvas. BlockStudio hides `richtext` fields from the sidebar. Background and text colour reuse `custom/background-colour` and `custom/text-colour`. The `name` inside those `field.json` files must match the `custom/…` type, or the reference is not expanded and the sidebar skips it. A RichText value that was never edited is not stored; Astro uses `.badge__text` in `innerHTML`, then the default. The block is not limited to the hero. Its shape CSS is imported from `theme.css` so the editor bundle includes it.

The live hero (`Label` with `animate`) staggers the letters on load. The pill is already there. Each character starts at `opacity: 0` and `translateY(0.35em)`, then runs `label-char-in` for `0.55s` `ease-out`, delayed by `0.1s + index * 0.032s`. There is no scroll trigger. `prefers-reduced-motion: reduce` skips the animation and leaves the letters visible. The sidebar toggle is `animate`, default on, including when the value was never saved. Astro adds `badge--animate` and splits the characters only when that is on. The editor template does not, and `badge-animate.css` is not in the editor bundle.

## Buttons

`app/blocks/extensions/button.json` extends `core/button` the same way the heading extension does. Editors type the label and set the link with the core button controls. Button type and Arrow are BlockStudio fields. `set` writes `cta cta--{type}` on the block wrapper. The Arrow toggle defaults to on. A saved `false` does not go through `set`, because BlockStudio skips a false value, so the editor script adds `cta--no-arrow` on the wrapper. Missing means on. `spa_headless_value_is_empty()` treats `false` as empty and does not copy it to the top level, so Astro reads `attrs.blockstudio.attributes.arrow` as well.

The types match live `Button.astro` at the default size: primary (red, white type), light (white, navy type, red arrow), dark (navy, white type), outline (white shell, navy fill), and text (red underlined link). The arrow is the 20×12 stroke. `resources/css/components/spa-button.css` paints the inner `.wp-block-button__link` in the editor and the `<a class="cta">` on the public site. A new hero's buttons row uses the live `14px` gap.

`spa/hero` is the old scaffold. `inserter` is false. Leave it registered so existing content still renders.

## Checklist

- `block.json`, PHP template, shared CSS, Astro component, contract, and `BlockRenderer` land in one change.
- Editor and front end use the same classes.
- A fixture covers the block, including a legacy attribute shape if one exists.
- `pnpm generate` was run. Do not hand-edit `astro/src/generated/blocks.ts`.

## Common mistakes

- Editing only the Astro component when a field is added. The generator will fail, and it should.
- A second stylesheet that drifts from the editor.
- Nesting two `<InnerBlocks />`. WordPress allows one.
- Assuming `set` classes are in `innerHTML`. Apply them from `attrs`.
- Putting `display: flex` on `.block-editor-block-list__layout` except the hero copy list, which uses that flex gap on purpose. Shapes need `pointer-events: none`. In the editor the inner heading inherits the block wrapper's colour, because BlockStudio's `set` lands on the wrapper. A badge sets its own colour so that inheritance does not turn it white.
- Putting blocks in the plugin. BlockStudio will not register them.
