# Design tokens

Runtime tokens are `resources/css/theme/variables.css` and `resources/css/theme/fonts.css`.

| Token | Value |
| --- | --- |
| `--color-navy` / `--color-primary` | `#031433` |
| `--color-red` / `--color-secondary` | `#c8102e` |
| `--color-royal` / `--color-tertiary` | `#012169` |
| `--color-black` | `#000` |
| `--color-white` | `#fff` |
| `--font-heading` | Oswald |
| `--font-sans` | Montserrat |
| `--width-container` | `1280px` |

Colour datasets (`textColours`, `bgColours`, `tagBgColours`) use navy, red, royal, black, and white. Slugs match the Astro `colorSlug` set.

Oswald and Montserrat latin woff2 files in `resources/fonts` are OFL (see `OFL.txt`).

The repo-level notes are in `/docs/design-tokens.md`.
