import { colorSlug } from "./color";

/** Live type scale, plus the older select values. */
export const fontSizeClasses = [
  "fs-display",
  "fs-h1-page",
  "fs-h2",
  "fs-h2-lg",
  "fs-statement",
  "fs-h3",
  "fs-tagline",
  "fs-eyebrow",
  "fs-hero-sub",
  "fs-lead",
  "fs-body",
  "fs-small",
  "fs-meta",
  "fs-xs",
  "fs-sm",
  "fs-base",
] as const;

export const fontWeightClasses = ["fw-400", "fw-500", "fw-600", "fw-700"] as const;

const sizes = new Set<string>(fontSizeClasses);
const weights = new Set<string>(fontWeightClasses);

/** Select fields save a string or `{ value, label }`. */
export function selectClass(value: unknown): string | null {
  if (typeof value === "string") {
    const trimmed = value.trim();
    return /^[\w-]+$/.test(trimmed) ? trimmed : null;
  }
  if (value && typeof value === "object" && "value" in value) {
    return selectClass((value as { value?: unknown }).value);
  }
  return null;
}

/**
 * Classes for a core heading or paragraph extension.
 * Colour accepts a slug, `var(--color-…)`, or a BlockStudio colour object.
 */
export function typographyClasses(attrs: {
  text_colour?: unknown;
  text_size?: unknown;
  text_weight?: unknown;
}): string[] {
  const classes: string[] = [];
  const colour = colorSlug(attrs.text_colour);
  if (colour) {
    classes.push(`has-text-${colour}`);
  }
  const size = selectClass(attrs.text_size);
  if (size && sizes.has(size)) {
    classes.push(size);
  }
  const weight = selectClass(attrs.text_weight);
  if (weight && weights.has(weight)) {
    classes.push(weight);
  }
  return classes;
}
