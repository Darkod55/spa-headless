import type { SpaHeroBannerMainFieldMap } from "../../../generated/blocks";

export const heroBannerMainFields = {
  background: true,
  image: true,
} as const satisfies SpaHeroBannerMainFieldMap;
