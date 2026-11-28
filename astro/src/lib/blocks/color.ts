export const brandColourSlugs = [
  "navy",
  "red",
  "royal",
  "black",
  "white",
  "primary",
  "secondary",
  "tertiary",
  "gray",
  "grey",
  "secondary-deep",
  "red-deep",
  "secondary-on-primary",
  "secondary-on-tertiary",
  "red-on-navy",
  "red-on-royal",
] as const;

export type BrandColourSlug = (typeof brandColourSlugs)[number];

const slugSet = new Set<string>(brandColourSlugs);

/**
 * Brand slug from a legacy string (`navy`), a CSS variable (`var(--color-navy)`),
 * or a BlockStudio colour object (`{ slug }`).
 */
export function colorSlug(value: unknown): BrandColourSlug | null {
  if (typeof value === "string") {
    const trimmed = value.trim();
    const fromVar = /^var\(--color-([a-z0-9-]+)\)$/.exec(trimmed);
    const slug = fromVar?.[1] ?? trimmed;
    return slugSet.has(slug) ? (slug as BrandColourSlug) : null;
  }

  if (value && typeof value === "object" && "slug" in value) {
    const slug = (value as { slug?: unknown }).slug;
    if (typeof slug === "string" && slugSet.has(slug)) {
      return slug as BrandColourSlug;
    }
  }

  return null;
}
