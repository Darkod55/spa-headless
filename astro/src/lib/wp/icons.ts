export interface SiteIcon {
  mime: string;
  sizes: {
    "32": string | null;
    "180": string | null;
    "192": string | null;
    "270": string | null;
  };
}

export type IconTag =
  | { kind: "link"; rel: string; href: string; type?: string; sizes?: string }
  | { kind: "meta"; name: string; content: string };

const ICON_REL = /^(?:shortcut icon|icon|apple-touch-icon(?:-precomposed)?)$/i;

/**
 * Tags that match WordPress `wp_site_icon()`: 32 and 192 icons, the 180
 * apple-touch icon, and the 270 tile image. An SVG site icon is one file at
 * every size, so it becomes a single `rel="icon"` instead of four copies.
 * WordPress does not emit a web manifest or theme-color for the site icon.
 */
export function siteIconTags(icon: SiteIcon | null | undefined): IconTag[] {
  if (!icon) {
    return [];
  }
  const sizes = icon.sizes;
  const urls = [sizes["32"], sizes["180"], sizes["192"], sizes["270"]].filter(
    (url): url is string => typeof url === "string" && url !== "",
  );
  if (urls.length === 0) {
    return [];
  }

  const mime = icon.mime.trim();
  const unique = new Set(urls);
  if (mime === "image/svg+xml" && unique.size === 1) {
    return [{ kind: "link", rel: "icon", href: urls[0]!, type: mime }];
  }

  const tags: IconTag[] = [];
  if (sizes["32"]) {
    tags.push({ kind: "link", rel: "icon", href: sizes["32"], type: mime || undefined, sizes: "32x32" });
  }
  if (sizes["192"]) {
    tags.push({ kind: "link", rel: "icon", href: sizes["192"], type: mime || undefined, sizes: "192x192" });
  }
  if (sizes["180"]) {
    tags.push({ kind: "link", rel: "apple-touch-icon", href: sizes["180"] });
  }
  if (sizes["270"]) {
    tags.push({ kind: "meta", name: "msapplication-TileImage", content: sizes["270"] });
  }
  return tags;
}

export function renderIconTags(tags: IconTag[]): string {
  return tags
    .map((tag) => {
      if (tag.kind === "meta") {
        return `<meta name="${tag.name}" content="${tag.content}" />`;
      }
      const type = tag.type ? ` type="${tag.type}"` : "";
      const sizes = tag.sizes ? ` sizes="${tag.sizes}"` : "";
      return `<link rel="${tag.rel}" href="${tag.href}"${type}${sizes} />`;
    })
    .join("\n");
}

/** Drop icon tags Rank Math already printed when we emit our own site icon. */
export function stripHeadIconTags(head: string): string {
  const withoutLinks = head.replace(/<link\b[^>]*>/gi, (tag) => {
    const rel = /(?:^|\s)rel\s*=\s*["']([^"']+)["']/i.exec(tag)?.[1] ?? "";
    return ICON_REL.test(rel.trim()) ? "" : tag;
  });
  return withoutLinks.replace(/<meta\b[^>]*\bname\s*=\s*["']msapplication-tileimage["'][^>]*>/gi, "");
}
