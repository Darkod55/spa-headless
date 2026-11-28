# Agent context

This theme is Square Peg Agency (`spa-sage`), not the Edy Nathan site it was copied from.

Start at the repo root [AGENTS.md](../../../AGENTS.md). Theme conventions:

- Sage 11, Tailwind 4, Vite, Alpine, Blade, Acorn. `composer install` and `npm install && npm run build` inside this theme. Do not commit `vendor/`, `node_modules/`, or `public/build/`.
- BlockStudio root is `app/blocks`. See [blocks.md](blocks.md) and [BLOCKSTUDIO-7.md](../BLOCKSTUDIO-7.md).
- Menu locations the plugin expects: `primary`, `footer`, `legal`, `footer-social`.
- Theme mods the plugin reads: `custom_logo`, `spa_alt_logo`, `spa_alt_logo_in_footer`, `spa_announcement_*`.
- Do not put client names, Flodesk, or podcast/event post types back in.
