import type { SpaHeroBannerDefaultFieldMap } from "../../../generated/blocks";

export const heroBannerDefaultFields = {
  background: true,
  image: true,
} as const satisfies SpaHeroBannerDefaultFieldMap;
