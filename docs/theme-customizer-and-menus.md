# Theme, Customizer, and menus

The theme is `wordpress/themes/spa-headless`. Activate it. BlockStudio must be active or the blocks do not register.

## Menus

| Location | Label in Appearance → Menus | Used for |
| --- | --- | --- |
| `primary` | Primary | Header. A top-level item with class `cta` (or `button`) is the slanted “Let’s talk” button |
| `footer` | Menu Footer | Links beside the wordmark |
| `legal` | Menu Legal | Bottom row |
| `footer-social` | Footer Menu Social | Icons chosen from the URL host |

`GET /wp-json/spa/v1/menus/{location}` returns `id`, `title`, `url`, `target`, `classes`, `parent`, `children`, `current`, `currentAncestor`. Same-host URLs are root-relative. `?path=` sets the current flags; Astro also recomputes them. A registered location with no menu returns `{ items: [] }`, not a 404.

Social hosts: facebook.com and fb.com, instagram.com, linkedin.com, x.com and twitter.com, youtube.com and youtu.be. Anything else is a generic link icon. Those links always open in a new tab.

Enable **CSS Classes** under Screen Options or the CTA field stays hidden.

## Site Identity

Appearance → Customize → Site Identity.

- **Logo** is `custom_logo`. SVG uploads are allowed. Missing pixel size uses the SVG viewBox.
- **Alternative logo** sits at priority 9, directly under Logo (the core control is priority 8). It stores an attachment id in theme mod `spa_alt_logo`, sanitized with `absint`.
- **Show in footer** (`spa_alt_logo_in_footer`) is the next control. It is hidden until an alternative logo is set, including before you publish (`assets/customize-controls.js` plus a PHP `active_callback`).
- **Site Icon** is `siteIcon` on `spa/v1/site`: `mime` and sizes 32, 180, 192, 270 from `get_site_icon_url()`. An SVG is one `<link rel="icon">`. Rank Math icon tags are removed when this is set. There is no invented web manifest or `theme-color`.

`footerLogo` is the alternative logo when it is set and ticked, otherwise the main logo, otherwise null. Astro then uses `astro/public/fixtures/logo-white.svg`. Fixtures mode always uses that file.

## Announcement bar

Appearance → Customize → **Announcement bar**.

Show toggle, message, link label, link URL, same tab or a new tab. A newline in the message is hidden on desktop and shown under 768px. There is no dismiss control. The bar sticks with the header. The fixture uses the live sentence and “Book your free website audit”.

## Checklist

- Each menu is assigned to one location and saved.
- The CTA item's CSS class is `cta`.
- Alternative logo is chosen before expecting **Show in footer**.
- After a Customizer change, reload Astro. The dev server reads WordPress at build and on each request in dev.

## Common mistakes

- Looking for the footer wordmark in the colour Logo field. The footer uses `footerLogo`, then the white file.
- Expecting a 404 when a menu location is empty. The list is empty.
- Uploading an SVG that WordPress rejects. The theme allows the mime type; the file still has to be a real SVG.
