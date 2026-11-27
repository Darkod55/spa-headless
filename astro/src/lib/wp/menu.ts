import type { SiteIcon } from "./icons";

export interface MenuItem {
  id: number;
  title: string;
  url: string;
  target: string;
  classes: string[];
  parent: number;
  children: MenuItem[];
  current: boolean;
  currentAncestor: boolean;
}

export interface WpMenu {
  location: string;
  items: MenuItem[];
}

export interface SiteLogo {
  id?: number;
  url: string;
  width: number | null;
  height: number | null;
  alt: string;
  mime: string;
}

export interface Announcement {
  enabled: boolean;
  message: string;
  linkLabel: string;
  url: string;
  target: string;
}

export interface WpSite {
  title: string;
  description: string;
  url: string;
  logo: SiteLogo | null;
  siteIcon?: SiteIcon | null;
  announcement?: Announcement | null;
}

/** Lines of the announcement. A newline is the mobile-only break. */
export function announcementLines(message: string): string[] {
  return message
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter((line) => line.length > 0);
}

/** Path used for current-page checks. Query and hash are ignored. */
export function menuItemPath(url: string): string {
  const noHash = url.split("#")[0] ?? "";
  const path = noHash.split("?")[0] ?? "";
  if (path === "" || path === "/") {
    return "/";
  }
  const trimmed = path.replace(/^\/+|\/+$/g, "");
  return trimmed ? `/${trimmed}` : "/";
}

export function isCurrentItem(url: string, pagePath: string): boolean {
  if (!pagePath) {
    return false;
  }
  return menuItemPath(url) === menuItemPath(pagePath);
}

export function isAncestorPath(url: string, pagePath: string): boolean {
  const item = menuItemPath(url);
  const current = menuItemPath(pagePath);
  if (!pagePath || item === "/" || item === current) {
    return false;
  }
  return current.startsWith(`${item}/`);
}

/** Recompute current / currentAncestor for the page Astro is rendering. */
export function withCurrent(items: MenuItem[], pagePath: string): MenuItem[] {
  return items.map((item) => {
    const children = withCurrent(item.children, pagePath);
    const current = isCurrentItem(item.url, pagePath);
    const ancestor =
      !current &&
      (isAncestorPath(item.url, pagePath) || children.some((child) => child.current || child.currentAncestor));
    return { ...item, children, current, currentAncestor: ancestor };
  });
}

/**
 * A top-level item with the class `cta` (or `button`) is the header button,
 * not a text link. Assign that class in Appearance → Menus.
 */
export function splitMenu(items: MenuItem[]): { links: MenuItem[]; cta: MenuItem | null } {
  const links: MenuItem[] = [];
  let cta: MenuItem | null = null;
  for (const item of items) {
    const isCta = item.classes.some((name) => name === "cta" || name === "button");
    if (!cta && isCta) {
      cta = item;
      continue;
    }
    links.push(item);
  }
  return { links, cta };
}
