export const brandColourSlugs = ["navy", "red", "royal", "black", "white"] as const;

export type BrandColourSlug = (typeof brandColourSlugs)[number];

const slugSet = new Set<string>(brandColourSlugs);

/**
 * Brand slug from a legacy string (`navy`), a CSS variable (`var(--color-navy)`),
 * or a BlockStudio colour object (`{ slug }`).
 */
export function colorSlug(value: unknown): BrandColourSlug | null {
  if (typeof value === "string") {
    const trimmed = value.trim();
    const fromVar = /^var\(--color-([a-z]+)\)$/.exec(trimmed);
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
