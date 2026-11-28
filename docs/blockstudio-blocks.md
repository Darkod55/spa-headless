# BlockStudio blocks

`block.json` is the source of truth. BlockStudio registers every file under `wordpress/themes/spa-headless/blockstudio/` that has a `blockstudio` key. `blockstudio.json` lives in the theme. PHP templates render the editor and the WordPress preview. Astro renders the public site. Both must emit the same class names and share one CSS file.

BlockStudio on this project is 7.7.x (Local WP is 7.7.15).

## Create a block

1. Add `wordpress/themes/spa-headless/blockstudio/sections/<slug>/block.json`.
   - `$schema`: `https://blockstudio.dev/schema/block`
   - `name`: `spa/<slug>` (one slash)
   - Fields live in `blockstudio.attributes` with `id` and `type`.
   - Tabs and groups without an `id` are layout only. A group with an `id` prefixes child ids (`presentation` + `align` → `presentation_align`).
   - Reuse a field with `{ "type": "custom/<name>" }`. Overrides change the default. See [blockstudio-fields-and-extensions.md](blockstudio-fields-and-extensions.md).
   - `style` and `editorStyle` should point at the same CSS file the Astro component imports.
2. Add `index.php` beside it. Attributes are `$a`. Escape output. One `<InnerBlocks />` per block. The public markup is not this file's job, but the class structure must match the Astro component.
3. Put the CSS in the theme (`assets/`) so the editor can enqueue it, and import that same file from Astro.
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

They share `blockstudio/sections/hero-banner/template.php` and `assets/hero-banner.css`. Background is `custom/background-colour` (default `var(--color-navy)`). Shapes follow `hero--bg-{slug}`. A saved string `"navy"` still renders. The image field is a single BlockStudio `files` field. REST returns `{ id, url, alt, width, height }`. Inner blocks are only `core/heading` and `core/paragraph`, starting as one H1 and one paragraph.

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
- Putting `display: flex` on `.block-editor-block-list__layout`, or leaving `.hero { overflow: hidden }` to clip the editor toolbar. Shapes need `pointer-events: none`. Editor heading colour must be `var(--hero-text)` at a higher specificity than `.editor-styles-wrapper h1`, or the type disappears on navy.
- Putting blocks in the plugin. BlockStudio will not register them.
