import type { SpaBadgeFieldMap } from "../../../generated/blocks";

export const badgeFields = {
  text: true,
  bg_colour: true,
  text_colour: true,
  animate: true,
} as const satisfies SpaBadgeFieldMap;
