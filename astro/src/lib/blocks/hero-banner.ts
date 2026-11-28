import type { SpaFileValue } from "../../generated/blocks";

export const heroBackgrounds = ["navy", "red", "royal", "black", "white"] as const;

export type HeroBackground = (typeof heroBackgrounds)[number];

const modifiers: Record<HeroBackground, string> = {
  navy: "hero--bg-navy",
  red: "hero--bg-red",
  royal: "hero--bg-royal",
  black: "hero--bg-black",
  white: "hero--bg-white",
};

/** Unknown or empty backgrounds use the live homepage navy. */
export function heroBackgroundClass(background: string | null | undefined): string {
  if (background && Object.hasOwn(modifiers, background)) {
    return modifiers[background as HeroBackground];
  }
  return modifiers.navy;
}

export interface HeroImage {
  id: number;
  url: string;
  alt: string;
  width: number | null;
  height: number | null;
}

/** The image object REST returns: id, url, alt, width, height. */
export function heroImage(value: SpaFileValue | null | undefined): HeroImage | null {
  if (!value || Array.isArray(value)) {
    return null;
  }
  const url = typeof value.url === "string" ? value.url : "";
  if (!url) {
    return null;
  }
  const rawId = value.id ?? value.ID;
  const id = typeof rawId === "number" ? rawId : 0;
  return {
    id,
    url,
    alt: typeof value.alt === "string" ? value.alt : "",
    width: typeof value.width === "number" ? value.width : null,
    height: typeof value.height === "number" ? value.height : null,
  };
}
