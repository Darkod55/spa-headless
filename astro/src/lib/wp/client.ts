import { fixtureContent, fixtureHead, fixtureMenu, fixtureRoutes, fixtureSite } from "./fixtures";
import type { WpMenu, WpSite } from "./menu";
import type { WpContent, WpRoute } from "./types";

export type FixturesMode = "force" | "auto" | "off";

let fixtureFallback = false;
let warned = false;

export function fixturesMode(): FixturesMode {
  const value = (import.meta.env.WP_FIXTURES ?? "auto").trim().toLowerCase();
  if (value === "1" || value === "true" || value === "force") {
    return "force";
  }
  if (value === "0" || value === "false" || value === "off") {
    return "off";
  }
  return "auto";
}

export function wpOrigin(): string | undefined {
  const value = import.meta.env.WP_URL?.replace(/\/$/, "").trim();
  return value ? value : undefined;
}

export function usingFixtures(): boolean {
  const mode = fixturesMode();
  if (mode === "force") {
    return true;
  }
  if (mode === "off") {
    return false;
  }
  return fixtureFallback || !wpOrigin();
}

function rememberUnreachable(reason: unknown): void {
  fixtureFallback = true;
  if (warned) {
    return;
  }
  warned = true;
  const message = reason instanceof Error ? reason.message : "request failed";
  console.warn(`[spa] WordPress is unreachable (${message}). Using fixtures.`);
}

async function wpGet(pathname: string, search?: Record<string, string>): Promise<Response> {
  const origin = wpOrigin();
  if (!origin) {
    throw new Error("WP_URL is not set.");
  }
  const url = new URL(pathname, `${origin}/`);
  if (search) {
    for (const [key, value] of Object.entries(search)) {
      url.searchParams.set(key, value);
    }
  }
  return fetch(url, {
    headers: { Accept: "application/json" },
    signal: AbortSignal.timeout(2500),
  });
}

function isMenu(value: unknown): value is WpMenu {
  if (!value || typeof value !== "object") {
    return false;
  }
  const record = value as Partial<WpMenu>;
  return typeof record.location === "string" && Array.isArray(record.items);
}

function isSite(value: unknown): value is WpSite {
  if (!value || typeof value !== "object") {
    return false;
  }
  const record = value as Partial<WpSite>;
  return typeof record.title === "string" && (record.logo === null || typeof record.logo === "object");
}

function isContent(value: unknown): value is WpContent {
  if (!value || typeof value !== "object") {
    return false;
  }
  const record = value as Partial<WpContent>;
  return typeof record.path === "string" && Array.isArray(record.blocks) && typeof record.title === "string";
}

export async function getRoutes(): Promise<WpRoute[]> {
  if (usingFixtures()) {
    return fixtureRoutes();
  }

  try {
    const response = await wpGet("/wp-json/spa/v1/routes");
    if (!response.ok) {
      throw new Error(`spa/v1/routes returned ${response.status}`);
    }
    const payload = (await response.json()) as { routes?: WpRoute[] };
    if (!Array.isArray(payload.routes)) {
      throw new Error("spa/v1/routes did not return a routes array.");
    }
    return payload.routes;
  } catch (error) {
    if (fixturesMode() === "off") {
      throw error;
    }
    rememberUnreachable(error);
    return fixtureRoutes();
  }
}

export async function getContent(path: string): Promise<WpContent | null> {
  const normalized = path === "/" ? "/" : `/${path.replace(/^\/+|\/+$/g, "")}`;
  if (usingFixtures()) {
    return fixtureContent(normalized);
  }

  try {
    const response = await wpGet("/wp-json/spa/v1/content", { path: normalized });
    if (response.status === 404) {
      return null;
    }
    if (!response.ok) {
      throw new Error(`spa/v1/content returned ${response.status}`);
    }
    const payload: unknown = await response.json();
    if (!isContent(payload)) {
      throw new Error("spa/v1/content returned an unexpected payload.");
    }
    return payload;
  } catch (error) {
    if (fixturesMode() === "off") {
      throw error;
    }
    rememberUnreachable(error);
    return fixtureContent(normalized);
  }
}

export async function getPreview(id: number): Promise<WpContent | { error: string }> {
  const token = import.meta.env.SPA_PREVIEW_TOKEN?.trim();
  if (!token) {
    return {
      error: "Set SPA_PREVIEW_TOKEN in astro/.env and the same value in the Local WP wp-config.php.",
    };
  }
  const origin = wpOrigin();
  if (!origin || fixturesMode() === "force") {
    return { error: "Preview reads drafts from WordPress. Set WP_URL and turn WP_FIXTURES off or to auto." };
  }

  const response = await fetch(new URL(`/wp-json/spa/v1/preview/${id}`, `${origin}/`), {
    headers: {
      Accept: "application/json",
      "X-SPA-Preview-Token": token,
    },
    signal: AbortSignal.timeout(2500),
  });

  if (response.status === 401) {
    return { error: "WordPress rejected the preview token." };
  }
  if (response.status === 404) {
    return { error: "WordPress has no content with that id." };
  }
  if (!response.ok) {
    return { error: `Preview request failed (${response.status}).` };
  }

  const payload: unknown = await response.json();
  if (!isContent(payload)) {
    return { error: "Preview response was not a content payload." };
  }
  return payload;
}

function originOf(value: string): string | null {
  try {
    return new URL(value).origin;
  } catch {
    return null;
  }
}

/**
 * Rank Math's headless endpoint is
 * GET /wp-json/rankmath/v1/getHead?url={absolute URL}
 * and it returns `{ success, head }`. The url must be absolute. We pass the
 * WordPress URL so Rank Math can resolve the post, then rewrite that origin
 * to PUBLIC_SITE_URL so canonicals point at the Astro site.
 *
 * Headless CMS Support has to be enabled in Rank Math → General Settings →
 * Others (advanced mode), or this route 404s.
 */
export async function getRankMathHead(path: string): Promise<string | null> {
  const normalized = path === "/" ? "/" : `/${path.replace(/^\/+|\/+$/g, "")}`;
  if (usingFixtures()) {
    return fixtureHead(normalized);
  }

  const origin = wpOrigin();
  if (!origin) {
    return fixtureHead(normalized);
  }

  try {
    const target = new URL(normalized, `${origin}/`).toString();
    const response = await wpGet("/wp-json/rankmath/v1/getHead", { url: target });
    if (response.status === 404) {
      return null;
    }
    if (!response.ok) {
      throw new Error(`rankmath/v1/getHead returned ${response.status}`);
    }
    const payload = (await response.json()) as { success?: boolean; head?: unknown };
    if (payload.success === false || typeof payload.head !== "string" || payload.head.trim() === "") {
      return null;
    }
    const site = import.meta.env.PUBLIC_SITE_URL?.trim();
    const from = originOf(origin);
    const to = site ? originOf(site) : null;
    if (!from || !to || from === to) {
      return payload.head;
    }
    return payload.head.split(from).join(to);
  } catch (error) {
    if (fixturesMode() === "off") {
      throw error;
    }
    rememberUnreachable(error);
    return fixtureHead(normalized);
  }
}

export async function getMenu(location = "primary"): Promise<WpMenu> {
  if (usingFixtures()) {
    return fixtureMenu(location) ?? { location, items: [] };
  }

  try {
    const response = await wpGet(`/wp-json/spa/v1/menus/${encodeURIComponent(location)}`);
    if (response.status === 404) {
      return { location, items: [] };
    }
    if (!response.ok) {
      throw new Error(`spa/v1/menus/${location} returned ${response.status}`);
    }
    const payload: unknown = await response.json();
    if (!isMenu(payload)) {
      throw new Error(`spa/v1/menus/${location} returned an unexpected payload.`);
    }
    return payload;
  } catch (error) {
    if (fixturesMode() === "off") {
      throw error;
    }
    rememberUnreachable(error);
    return fixtureMenu(location) ?? { location, items: [] };
  }
}

export async function getSite(): Promise<WpSite> {
  if (usingFixtures()) {
    return fixtureSite();
  }

  try {
    const response = await wpGet("/wp-json/spa/v1/site");
    if (!response.ok) {
      throw new Error(`spa/v1/site returned ${response.status}`);
    }
    const payload: unknown = await response.json();
    if (!isSite(payload)) {
      throw new Error("spa/v1/site returned an unexpected payload.");
    }
    return payload;
  } catch (error) {
    if (fixturesMode() === "off") {
      throw error;
    }
    rememberUnreachable(error);
    return fixtureSite();
  }
}
