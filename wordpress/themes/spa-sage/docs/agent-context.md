# Agent context — bootstrap playbook

## Order on a new site

1. **This file** (`docs/agent-context.md`) — identity, `AGENTS.md`, Cursor rules, path retargets  
2. **Then** `docs/design-tokens.md` — colours, type, buttons → CSS/PHP  

Do not run the design-tokens prompt first; brand sync expects the theme slug / agent docs to already match the new project.

---

**Ships with the theme.** Copy the theme into a new Local site, open the **site root** in Cursor, fill **Active identity**, paste the agent prompt below.

Cursor only auto-loads agent context from the **workspace root**, not from inside the theme alone.

**Human input:** Active identity (+ optional flags) in this file.  
**Agent writes:** repo-root `AGENTS.md`, `.cursor/rules/*`, `docs/copy-theme.md`, plus theme `style.css` / `vite.config.js` / path retargets (see Apply order).

### Agent prompt (copy/paste)

```text
Read docs/agent-context.md in this theme. Active identity is filled in (project, slug, theme_name are not pending). Apply this playbook: create or overwrite root AGENTS.md, .cursor/rules (blockstudio.mdc + {slug}-theme.mdc, {slug}-design-system.mdc, {slug}-plugins.mdc), and docs/copy-theme.md from the templates; update theme style.css Theme Name and vite.config.js base; retarget old_slug/old_project strings in theme docs. Follow Apply order and Verify. Do not invent stack changes. Leave (pending) keys unchanged. Do not run design-tokens yet — remind me that design-tokens.md is step 2.
```

---

## Active identity

```text
# Required for apply
project: Edy Nathan
slug: edy-nathan
theme_name: Edy Nathan

# Optional
old_slug: cpj
old_project: CPJ
local_url: edy-nathan.local
text_domain: sage
blockstudio_version: 7.6.6
plugins_fluent_forms: yes
plugins_rank_math: yes
mu_plugins: mpx-admin-kit
```

| Key | Meaning |
| --- | --- |
| `project` | Short project label in titles (`AGENTS.md` H1, rule titles) — e.g. `Acme` |
| `slug` | Theme folder slug — e.g. `acme` → `themes/acme` |
| `theme_name` | WordPress `style.css` Theme Name — e.g. `Acme` |
| `old_slug` / `old_project` | Strings to replace when retargeting a copied starter (defaults match this starter) |
| `local_url` | Optional Local site URL for `APP_URL` notes |
| `plugins_*` | `yes` / `no` — trim plugins rule sections if `no` |
| `mu_plugins` | Comma list mentioned in `AGENTS.md` / gitignore notes |

**Derived (agent computes):**

| Token | Value |
| --- | --- |
| `{PROJECT}` | `project` |
| `{SLUG}` | `slug` |
| `{THEME_NAME}` | `theme_name` |
| `{THEME_PATH}` | `app/public/wp-content/themes/{SLUG}` |
| `{THEME_PATH_SHORT}` | `themes/{SLUG}` |
| `{VITE_BASE}` | `/wp-content/themes/{SLUG}/public/build/` |
| `{RULE_THEME}` | `{SLUG}-theme.mdc` |
| `{RULE_DS}` | `{SLUG}-design-system.mdc` |
| `{RULE_PLUGINS}` | `{SLUG}-plugins.mdc` |

---

## Outputs

| Output | Location |
| --- | --- |
| Orientation | `{workspace}/AGENTS.md` |
| Rules | `{workspace}/.cursor/rules/blockstudio.mdc` |
| | `{workspace}/.cursor/rules/{RULE_THEME}` |
| | `{workspace}/.cursor/rules/{RULE_DS}` |
| | `{workspace}/.cursor/rules/{RULE_PLUGINS}` |
| Copy checklist | `{workspace}/docs/copy-theme.md` |
| Theme identity | `{THEME_PATH}/style.css` → `Theme Name` |
| Vite base | `{THEME_PATH}/vite.config.js` → `base` |
| Playbooks in theme | Retarget `cpj` / old slug inside `docs/design-tokens.md`, `docs/blocks.md`, `docs/agent-context.md` body paths if needed |
| Root `.gitignore` | If present: whitelist `themes/{SLUG}`; if missing, create a minimal whitelist stub (optional — see template) |

Delete obsolete rule files named `{old_slug}-*.mdc` after writing the new names.

---

## Apply order (agent)

1. Read Active identity; abort apply if `project`, `slug`, or `theme_name` is `(pending)`.
2. Write `AGENTS.md` from template A.
3. Write `.cursor/rules/*` from templates B–E (substitute tokens; honour `plugins_*` flags).
4. Write `docs/copy-theme.md` from template F.
5. Update theme `style.css` Theme Name → `{THEME_NAME}`.
6. Update theme `vite.config.js` `base` → `{VITE_BASE}`.
7. Replace `{old_slug}` → `{slug}` and `{old_project}` → `{project}` in theme docs paths / titles where they refer to the site theme (not third-party code). Do **not** touch `node_modules/`, `vendor/`, lockfiles, or plugins.
8. If root `.gitignore` exists, retarget theme whitelist to `{SLUG}`; else optionally write template G.
9. Reminder for the human: fill `docs/design-tokens.md` Active blocks → run that file’s agent prompt; `composer install` / `npm install` / `npm run build` in the theme.

---

## Substitution rules

- Replace every `{PROJECT}`, `{SLUG}`, `{THEME_NAME}`, `{THEME_PATH}`, `{THEME_PATH_SHORT}`, `{VITE_BASE}`, `{RULE_*}` in templates.
- Rule frontmatter `globs` must use `themes/{SLUG}` (not the old slug) or rules will not attach.
- Keep stack facts (Sage 11.2.1, Acorn ^6, Vite 8, Tailwind v4, Alpine 3, Blockstudio version from Active) unless Active overrides `blockstudio_version`.
- Text domain stays `sage` unless Active `text_domain` says otherwise (do not mass-rename PHP gettext domain unless asked).

---

## Template A — `AGENTS.md`

Outer fence uses four backticks so the inner bash fence is preserved.

````markdown
# {PROJECT} — agent orientation

Local WP site. Almost all work happens in the Sage theme.

## Layout

| Path | Role |
| --- | --- |
| `app/public/` | WordPress root |
| `{THEME_PATH}/` | Theme (primary codebase) |
| `app/public/wp-content/plugins/` | Plugins (Local install; **not in git**) |
| `conf/`, `logs/` | Local WP config / logs (ignored) |

Theme-relative paths in docs (`app/blocks`, `resources/…`) mean **inside the theme**, not the repo `app/` folder.

## What’s tracked

Root `.gitignore` ignores most of WordPress and **whitelists** the theme plus a few mu-plugins (`{mu_plugins}`). Do not commit plugins, uploads, `vendor/`, `node_modules/`, `public/build/`, or block `_dist/` folders.

Blockstudio, Fluent Forms, Rank Math, etc. exist only on the Local machine. Prefer their on-disk sources / LLM docs over inventing APIs.

## Stack

- **Theme:** Sage 11.2.1 (text domain `{text_domain}`), PHP ≥ 8.3, WP ≥ 6.6
- **PHP:** Acorn ^6, PSR-4 `App\` → theme `app/`
- **Frontend:** Vite 8, Tailwind CSS v4, Alpine 3, Node 20 (`.nvmrc`)
- **Blocks:** Blockstudio **{blockstudio_version}** (plugin), namespace `mpx/*`, scan root theme `app/blocks`

## Boot order

1. Theme `functions.php` — Composer autoload, Acorn + `ThemeServiceProvider`
2. `app/setup.php`, `app/filters.php`, `app/includes.php`
3. `includes.php` globs traits (`helpers` → `filters` → `blocks`) then `Classes/*`
4. `App\Classes\MpxBlocks` wires Blockstudio hooks from `app/Traits/blocks/trait-blocks.php`

FSE / block templates are intentionally off; layouts live in Blade (`resources/views/`).

## Dev commands (from theme directory)

```bash
cd {THEME_PATH}
composer install   # vendor/ (gitignored)
npm install
npm run dev        # Vite HMR — set APP_URL to the Local site URL if needed
npm run build
```

## Where to look before inventing

- Blockstudio decisions: `{THEME_PATH}/BLOCKSTUDIO-7.md`
- Blockstudio framework index (if plugin present): `app/public/wp-content/plugins/blockstudio/includes/llm/blockstudio-llm.txt`
- Cursor rules: `.cursor/rules/blockstudio.mdc`, `{RULE_THEME}`, `{RULE_DS}`, `{RULE_PLUGINS}`
- Block inventory: `{THEME_PATH}/docs/blocks.md`
- Rebrand playbook: `{THEME_PATH}/docs/design-tokens.md`
- Agent context bootstrap (this flow): `{THEME_PATH}/docs/agent-context.md`
- Runtime tokens / utilities: theme `resources/css/theme/*.css` (entry `resources/css/theme.css`; `variables.css` written from the rebrand playbook)
- Site bootstrap checklist: `docs/copy-theme.md`
````

(Insert Active `mu_plugins`, `text_domain`, `blockstudio_version` into the placeholders above. If `local_url` is set, add a short note under Dev commands: `APP_URL={local_url}`.)

---

## Template B — `.cursor/rules/blockstudio.mdc`

```markdown
---
description: Blockstudio framework context and {PROJECT} block conventions
globs: "**/themes/{SLUG}/app/blocks/**,**/themes/{SLUG}/blockstudio.json,**/themes/{SLUG}/BLOCKSTUDIO-7.md,**/themes/{SLUG}/patterns/**,**/themes/{SLUG}/pages/**,**/plugins/blockstudio/**"
alwaysApply: false
---

# Blockstudio ({PROJECT})

Installed plugin version: **{blockstudio_version}**. Prefer local files over the public docs site.

1. **Framework index** — `app/public/wp-content/plugins/blockstudio/includes/llm/blockstudio-llm.txt` (or `blockstudio-llm-full.txt`). Grep before reading the docs site.
2. **HTTP mirrors** (`blockstudio.json` → `ai.enableContextGeneration`) — `/blockstudio-llm.txt` on the Local site.
3. **Theme decisions** — `{THEME_PATH}/BLOCKSTUDIO-7.md` (Acorn Blade bridge, path filters, dual Tailwind). Prefer this over inventing architecture. Some narrative sections may lag the live blocks — verify against `app/blocks` and `trait-blocks.php`.

## {PROJECT} conventions

- Blocks: theme `app/blocks` via `blockstudio/path`. Namespace `mpx/*`. Categories `metapixels-blocks` / `metapixels-partials`.
- Templates: prefer `index.blade.php`; Acorn renders via `renderBladeBlockTemplates()` (not a second Blade engine). `<InnerBlocks>`, `<RichText>`, `useBlockProps` still work after Blade.
- Escaping: if WP already escaped (`esc_attr`, `esc_html`, `wp_kses_post`, `wp_json_encode` for attrs), echo with `{!! !!}` — `{{ esc_attr(...) }}` double-encodes. Critical for `<InnerBlocks allowedBlocks="...">`.
- One `<InnerBlocks>` per block template (core limit). Avoid parent `templateLock` when nested children must stay freely editable.
- Reusable fields: **only** `app/blocks/fields/{name}/field.json` (discovery is not recursive). Extensions: `app/blocks/extensions/*.json` with `"extend": true`.
- New `mpx/*` blocks: add the name to `mpxRemoveInnerBlocksWrapper()` in `trait-blocks.php`.
- Media library images: `wp_get_attachment_image($ID)` — not `bs_media_image()` (that helper is for theme-relative static assets).
- Icons: block populate reads `resources/views/components/footer-icons.blade.php` (`#icon-{name}`). Add new symbols there; ensure the sprite is included on the page (layouts or block `@include`).
- Design tokens: `resources/css/theme/*.css`, bridged into Blockstudio Tailwind via `bridgeTailwindConfig()`. Use live utilities (`btn`, `btn-primary`, `py-clamp`, `bg-primary`, `grid-auto-cards`, …). Non-bridged Vite-only rules can live under `resources/css/components/` (imported from `theme.css`). Rebrand via `docs/design-tokens.md`.
- Dual Tailwind: Vite scans Blade/PHP; Blockstudio also compiles from rendered HTML. Dynamic / post-content classes may need `@source inline(...)` in `resources/css/theme.css`.
- `blockstudio.json` → `cache.enabled: false` on purpose (populate options live in PHP outside the scan root). Re-enable only with a bust plan.
- Composition: section blocks nest `mpx/partial-*` pieces. Fluent Forms Gutenberg id is intentionally `fluentfom/guten-block` (plugin typo) when that plugin is in use.
- Patterns/pages (if added): theme-root `patterns/` and `pages/` — not under `app/blocks` unless paths filters are added. FSE/site templates unused.
- File-based only (`block.json` + template). Do not invent React/JSX block builds.
```

---

## Template C — `.cursor/rules/{RULE_THEME}`

```markdown
---
description: {PROJECT} Sage theme entry points, Blade, icons, Alpine, helpers
globs: "**/themes/{SLUG}/**"
alwaysApply: false
---

# {PROJECT} theme

Paths below are theme-relative (`{THEME_PATH}/…`). Repo orientation: root `AGENTS.md`. Blocks: `.cursor/rules/blockstudio.mdc` + `BLOCKSTUDIO-7.md`.

## Entry points

| Path | Role |
| --- | --- |
| `functions.php` | Composer, Acorn + `ThemeServiceProvider` |
| `app/setup.php` | Editor Vite CSS/JS, `theme.json` → built asset, menus, FSE off, NavWalker default |
| `app/includes.php` | Globs helpers → filters → blocks traits, then `Classes/*` |
| `app/Classes/MpxBlocks.php` | Blockstudio hook wiring |
| `app/Traits/blocks/trait-blocks.php` | Blade bridge, populate, unwrap, Tailwind bridge, core button restyle |
| `vite.config.js` | Entries: `app.css`/`app.js`/`editor.css`/`editor.js`; base `{VITE_BASE}` |
| Runtime `theme.json` | Built at `public/build/assets/theme.json` (source in theme root) |

## Blade

- Layouts: `resources/views/layouts/app.blade.php` (default), `app-wide.blade.php` (front).
- Sections: `header`, `footer`, `sidebar`. Partials under `partials/`. Composers: `app/View/Composers/`.
- FSE / block templates intentionally off — do not add Blockstudio site templates for layout.
- Block templates use Acorn `view()->file()`; vars `$a`/`$attributes`, `$b`/`$block`, `$c`/`$context`, `$isEditor`, `$isPreview`. Prefer `{!! !!}` when WP already escaped.

## Icons (dual sprites)

- `components/header-icons.blade.php` — above-fold UI. Included from `sections/header`.
- `components/footer-icons.blade.php` — full set; **block populate** source (`getSpriteIconOptions()`).
- Usage: `@svgIcon('name'|w|h)` or `\App\helpers()->getSvg([...])` → `<use href="#icon-{name}">`. New block icons go in **footer-icons**.

## JS / Alpine

- `resources/js/app.js` — Alpine start, `scrolled` body class, partners marquee CSS vars.
- Header off-canvas + NavWalker dropdowns use Alpine. Walker: `app/Walkers/NavWalker.php` + `Traits/filters/trait-navs.php`.
- `resources/js/editor.js` — selection body flags for editor-canvas CSS.

## Helpers & ownership

- `\App\helpers()` → `Helpers` singleton.
- Core `button` / `image` look is owned by Blockstudio extensions + `setup.php` `block_type_metadata` stripping + `moveCoreButtonClassesToLink()`.
- Match existing patterns; prefer theme utilities. Rebrand via `docs/design-tokens.md`.
```

---

## Template D — `.cursor/rules/{RULE_DS}`

```markdown
---
description: {PROJECT} design tokens, utilities, buttons, dual Tailwind
globs: "**/themes/{SLUG}/resources/css/**,**/themes/{SLUG}/app/blocks/**,**/themes/{SLUG}/resources/views/**,**/themes/{SLUG}/theme.json,**/themes/{SLUG}/docs/design-tokens.md"
alwaysApply: false
---

# {PROJECT} design system

Stay on theme tokens. Do not invent purple/indigo gradients, cream+serif+terracotta defaults, or generic AI landing kits.

**Rebrand:** edit Active blocks in `docs/design-tokens.md` only, then run its agent prompt. Agent writes `variables.css`, type clamps, button sync. `(pending)` = leave that target alone. Do not invent button variants or tokens not listed in the playbook.

## Files

- Playbook (human input): `docs/design-tokens.md`
- Runtime tokens: `resources/css/theme/variables.css`
- Hub: `resources/css/theme.css`
- Modules: `resources/css/theme/{variables,fonts,sections,cards,faqs,buttons,forms,nav,typography,posts,global}.css`
- Site entry: `resources/css/app.css`
- Editor: `resources/css/editor.css` (+ `editor-canvas.css`)

## Tokens (runtime)

- Colours → `text-*` / `bg-*` (keep both in `theme.css` `@source inline` when adding a name).
- Spacing / widths / radius / padding / font-size — see playbook; do not hardcode in components.
- Type: single value → fixed; `min / max` → `clamp()` (never hand-tune middle term).
- Buttons: recipe `default / hover` in playbook.

Fonts: see `fonts.css` (`--font-sans`, `--font-heading`).

## Layout utilities (`sections.css`)

- `container`, `px-clamp` / `py-clamp*`, `grid-auto-cards`, `hero-overlay`, `mw-*`, `content-pos-*` — reuse existing.

## Dual Tailwind

1. **Vite** scans theme Blade/PHP/JS at build time.
2. **Blockstudio** compiles from rendered HTML (bridged tokens from `theme/*.css`).

Dynamic / post-content classes may need `@source inline("…")` in `theme.css`.
```

---

## Template E — `.cursor/rules/{RULE_PLUGINS}`

If `plugins_fluent_forms: no`, omit the Fluent Forms section. If `plugins_rank_math: no`, omit Rank Math.

```markdown
---
description: {PROJECT} plugin integrations (Blockstudio, Fluent Forms, Rank Math)
globs: "**/themes/{SLUG}/**,**/plugins/blockstudio/**,**/plugins/fluentform/**,**/plugins/seo-by-rank-math/**"
alwaysApply: false
---

# {PROJECT} plugins

Plugins live under `app/public/wp-content/plugins/` and are **not in git**. Prefer on-disk sources / LLM docs on the Local machine over inventing APIs. Typical installs: `blockstudio`, `fluentform`, `seo-by-rank-math`, `wp-migrate-db-pro`.

## Blockstudio

- Theme config: `{THEME_PATH_SHORT}/blockstudio.json` (`ai.enableContextGeneration: true`, `cache.enabled: false`, Tailwind on, admin-only editor).
- Agent docs (if present): `plugins/blockstudio/includes/llm/blockstudio-llm.txt` (+ `-full`). HTTP mirrors: `/blockstudio-llm.txt`, `/blockstudio-llm-full.txt`.
- Theme decisions: `{THEME_PATH_SHORT}/BLOCKSTUDIO-7.md` + `.cursor/rules/blockstudio.mdc`.

## Fluent Forms

- Gutenberg block name is misspelled in the plugin: **`fluentfom/guten-block`**. Keep that string in templates/`allowedBlocks`.
- Brand styles: `.fluentform.brand-form` in `resources/css/theme/forms.css` (listed in `theme.css` `@source inline`).
- Prefer Fluent Forms for multi-step / complex forms rather than inventing Blockstudio RPC unless there is a clear reason.

## Rank Math

- FAQ block: `rank-math/faq-block`. Extension: `app/blocks/extensions/rank-math-faq.json`.
- Frontend: `faqWidget()` in `trait-blocks.php` → `.mpx-faqs-grid` / `.mpx-faq-card` (`resources/css/theme/faqs.css`).
- Allowed in `mpx/one-column` (and wherever templates explicitly list it).

## Other

- SEO is Rank Math, not theme `trait-seo` (empty). Don’t add ACF assumptions unless that plugin is installed.
- Mu-plugins (if tracked): `{mu_plugins}`.
```

(When a plugin flag is `no`, also drop that plugin from the `globs` list.)

---

## Template F — `docs/copy-theme.md`

```markdown
# Bootstrap a new site from this starter

Preferred flow: copy the Sage theme folder into a new Local WP site, open the **site root** in Cursor, fill Active identity in:

`{THEME_PATH}/docs/agent-context.md`

…then run that file’s **Agent prompt**. The agent creates `AGENTS.md`, `.cursor/rules/*`, and this checklist’s next-site copy.

## Manual prerequisites

- [ ] Local WP site with `app/public/` layout; workspace = site root
- [ ] Theme at `{THEME_PATH}/` (folder slug `{SLUG}`)
- [ ] Plugins installed on the machine (Blockstudio; Fluent Forms / Rank Math if used)
- [ ] Active identity filled in `docs/agent-context.md` → agent prompt applied
- [ ] Active brand filled in `docs/design-tokens.md` → that playbook’s agent prompt applied
- [ ] In the theme: `composer install`, `npm install`, `npm run build`

## Do not commit

Theme `node_modules/`, `vendor/`, `public/build/`, block `_dist/`, uploads, plugins, `conf/`, `logs/`.

## Day-to-day agent orientation

1. Root `AGENTS.md`
2. `.cursor/rules/*`
3. Theme `BLOCKSTUDIO-7.md` + `docs/blocks.md` + `docs/design-tokens.md`
4. On-disk plugin LLM docs
```

---

## Template G — root `.gitignore` stub (only if missing)

```gitignore
# WordPress / Local — keep theme + selected mu-plugins
/*
!/.gitignore
!/AGENTS.md
!/.cursor/
!/docs/
!/app/
/app/*
!/app/public/
/app/public/*
!/app/public/wp-content/
/app/public/wp-content/*
!/app/public/wp-content/themes/
/app/public/wp-content/themes/*
!/app/public/wp-content/themes/{SLUG}/
!/app/public/wp-content/mu-plugins/
/app/public/wp-content/mu-plugins/*
!/app/public/wp-content/mu-plugins/wp-admin-bar.php
!/app/public/wp-content/mu-plugins/wp-login-screen.php
!/app/public/wp-content/mu-plugins/wp-last-edited.php
```

Adjust mu-plugin whitelist lines to match Active `mu_plugins` filenames if different.

---

## Verify

- [ ] Workspace root is the Local site root  
- [ ] `AGENTS.md` title uses `{PROJECT}`; paths use `{SLUG}`  
- [ ] Rule files are named `{SLUG}-*.mdc` and globs match `themes/{SLUG}`  
- [ ] Opening a theme file attaches theme / design-system / Blockstudio rules  
- [ ] `vite.config.js` `base` matches `{VITE_BASE}`  
- [ ] Next: `docs/design-tokens.md` agent prompt for brand
