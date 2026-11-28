# Edy Nathan blocks inventory

Namespace `mpx/*`. Sections: `metapixels-blocks`. Partials: `metapixels-partials`. Scan root: theme `app/blocks`. Deep decisions: [`BLOCKSTUDIO-7.md`](../BLOCKSTUDIO-7.md).

## Sections

| Block | Purpose | Typical children |
| --- | --- | --- |
| `mpx/hero` | Split hero (bg colour + right image + blend overlay) | `partial-subheading`, `core/heading`, `core/paragraph`, `core/list`, `partial-buttons` |
| `mpx/hero-v2` | Cinematic full-bleed photo + left brand scrim | `partial-subheading`, `core/heading`, `core/paragraph`, `mpx/partial-button` |
| `mpx/column-cover` | Full-bleed cover image + content box overlay | `core/heading`, `core/paragraph`, `mpx/partial-button` |
| `mpx/columns` | Two-column section; optional gradient bg; widths on parent | `partial-column` × 2 (optional card bg; checklist allowed) |
| `mpx/one-column` | Single content column | Heading, paragraph, list, image, video, embed, Custom HTML, button, subheading, icon, divider, box, cards, checklist, Rank Math FAQ, Fluent Form |
| `mpx/partners-slider` | Logo marquee | `core/gallery`, heading, paragraph |
| `mpx/keynote` | Sticky index number + keynote detail | subheading, heading, paragraph, list (tags), buttons |

## Partials

| Block | Purpose | Typical children |
| --- | --- | --- |
| `mpx/partial-column` | Grid cell; widths set on parent Columns; hidden `allowedBlocks` | heading, paragraph, list, image, html, button, subheading, box, checklist, Fluent Forms |
| `mpx/partial-button` | Theme button wrapper (single) | `core/button` / `core/buttons` |
| `mpx/partial-buttons` | Inline row of theme CTAs | `core/button` × N |
| `mpx/partial-subheading` | Accent bar + heading/paragraph(s); optional frosted panel | heading, paragraph |
| `mpx/partial-row` | Space-between row (label left, badges right) | subheading, list (tags), paragraph |
| `mpx/partial-icon` | Sprite icon; icon colour + size | — |
| `mpx/partial-divider` | Short horizontal rule; line colour | — |
| `mpx/partial-box` | Optional bg, frosted panel, padding | heading, paragraph, list, Fluent Forms |
| `mpx/partial-cards` | Card grid / optional slider; solid or gradient bg; Grid Controls | `partial-card`, `partial-card-icon`, `partial-card-image`, `partial-card-testimonial-simple`, `partial-button` |
| `mpx/partial-card` | Text card; optional check/icon (off = centered tile) | heading, paragraph, `partial-button` |
| `mpx/partial-card-icon` | Gold outline icon + heading + body (stacked; optional horizontal) | `partial-icon`, heading, paragraph, `partial-button` |
| `mpx/partial-card-image` | Image/logo card; optional edge-to-edge image | image, heading, paragraph, `partial-button` |
| `mpx/partial-card-testimonial-simple` | Quote + name / optional company | paragraph, heading |
| `mpx/partial-checklist` | Checkmark list | `partial-checklist-item` |
| `mpx/partial-checklist-item` | List row | `core/paragraph` |

## Parent → child matrix (defaults)

```
mpx/hero
  └─ mpx/partial-subheading | core/heading | core/paragraph | core/list | mpx/partial-buttons
     (+ hero image attribute on the block)

mpx/hero-v2
  └─ mpx/partial-subheading | core/heading | core/paragraph | mpx/partial-button
     (+ full-bleed background image + scrim colour)

mpx/column-cover
  └─ core/heading | core/paragraph | mpx/partial-button
     (+ full-bleed cover image + content box colour / left|right)

mpx/columns  (use_gradient_bg; vertical_align; sticky_column none|left|right desktop-only;
              col_1/col_2 widths; gaps; mobile = 100%)
  └─ mpx/partial-column × 2  (optional bg_colour card panel)
       └─ heading | paragraph | list | image | html | partial-button | partial-subheading | partial-box | partial-row | partial-checklist | fluentfom/guten-block

mpx/one-column  (use_gradient_bg — same primary → tertiary as Columns)
  └─ heading | paragraph | list | image | video | embed | partial-button | partial-subheading
     | partial-icon | partial-divider | partial-box | partial-row | partial-cards | partial-checklist | rank-math/faq-block | fluentfom/guten-block

mpx/partial-box  (optional bg + frosted panel + padding)
  └─ core/heading | core/paragraph | core/list | fluentfom/guten-block
     (list: Theme list → styled bullets + optional dividers; or Tag list → inline pills)

mpx/partial-row  (always space-between)
  └─ partial-subheading | core/list (Tag list + optional Tag background) | core/paragraph

mpx/keynote  (sticky gold index number; bg + section padding)
  └─ partial-subheading | partial-row | heading | paragraph | list | partial-buttons | partial-button
     (template: eyebrow + title + Ideal for + tag list + secondary-outline CTA)

mpx/partial-cards  (use_gradient_bg / enable_slider + optional autoplay ping-pong;
                   slides_desktop + slides_tablet; editor = wrapping grid;
                   frontend slider = exact-fit N visible, step by 1 + bullets)
  └─ partial-card | partial-card-icon | partial-card-image | partial-card-testimonial-simple | partial-button
     (media coverage: card-image + btn-white on gradient; section CTA btn-gradient;
      button row: 3× partial-button + cols ~33%; testimonials: quote + name/company)

mpx/partial-checklist
  └─ partial-checklist-item → core/paragraph

mpx/partners-slider
  └─ core/gallery | core/heading | core/paragraph
```

## Shared pieces

- **Reusable fields** (`app/blocks/fields/{name}/` only): `section_padding`, `background`, `background_colour`, `grid_controls`, `grid_gaps`, `column_widths`, `grid_item_width`, `text_colour`, `line_colour`, `icon_colour`, `typography`.
- **Extensions**: `core/button`, `core/heading`, `core/paragraph`, `core/list` (typography + heading font + styled bullets/dividers/tag pills + tag background), `core/image` (circular portrait), `rank-math/faq-block`.
- **New `mpx/*` block**: add name to `mpxRemoveInnerBlocksWrapper()` in `trait-blocks.php`.
- **Fluent Forms block id**: `fluentfom/guten-block` (plugin typo — keep it).
- **Patterns / pages**: not added yet (`patterns/`, `pages/` at theme root when needed). FSE site templates unused.
