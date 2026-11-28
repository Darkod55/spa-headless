import type { SpaHeroFieldMap } from "../../../generated/blocks";

/**
 * Every stored field on spa/hero, including repeater rows when a block has
 * them. `satisfies` fails typecheck when a field is added or removed.
 * scripts/generate-block-types.ts compares this object to block.json and
 * fails `astro check` / `astro build` before the typecheck.
 */
export const heroFields = {
  accentBg: true,
  description: true,
  eyebrow: true,
  heading: true,
  image: true,
  primaryButton: true,
  removeBottomPadding: true,
  secondaryButton: true,
} as const satisfies SpaHeroFieldMap;
