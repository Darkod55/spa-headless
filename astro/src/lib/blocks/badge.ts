import { colorSlug, type BrandColourSlug } from "./color";

const lightBackgrounds = new Set<BrandColourSlug>(["white", "gray", "grey"]);

/** Missing background is the live red eyebrow. */
export function badgeBackgroundSlug(value: unknown): BrandColourSlug {
  return colorSlug(value) ?? "secondary";
}

/**
 * Default text is white. White on a white or gray badge becomes navy.
 * Any other chosen text colour is kept.
 */
export function badgeTextSlug(background: unknown, text: unknown): BrandColourSlug {
  const bg = badgeBackgroundSlug(background);
  const chosen = colorSlug(text) ?? "white";
  if (chosen === "white" && lightBackgrounds.has(bg)) {
    return "primary";
  }
  return chosen;
}

export function badgeClasses(background: unknown, text: unknown): string[] {
  return ["badge", `badge--bg-${badgeBackgroundSlug(background)}`, `badge--text-${badgeTextSlug(background, text)}`];
}
