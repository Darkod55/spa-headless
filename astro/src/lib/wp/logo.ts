import type { SiteLogo } from "./menu";

/** Bundled white wordmark. Used when WordPress has no footer logo, and in fixtures mode. */
export const bundledFooterLogo = "/fixtures/logo-white.svg";

export interface FooterLogoView {
  url: string;
  alt: string;
  width: number | null;
  height: number | null;
}

/**
 * The footer image. Fixtures mode always uses the bundled white wordmark.
 * A live response uses footerLogo (already resolved in WordPress), and the
 * same file when that is missing.
 */
export function resolveFooterLogo(
  footerLogo: SiteLogo | null | undefined,
  options: { fixtures: boolean; title?: string },
): FooterLogoView {
  const fallback: FooterLogoView = {
    url: bundledFooterLogo,
    alt: options.title?.trim() || "The Square Peg Agency",
    width: 232,
    height: 60,
  };
  if (options.fixtures || !footerLogo?.url) {
    return fallback;
  }
  return {
    url: footerLogo.url,
    alt: footerLogo.alt.trim() || fallback.alt,
    width: footerLogo.width,
    height: footerLogo.height,
  };
}
