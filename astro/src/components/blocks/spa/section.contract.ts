import type { SpaSectionFieldMap } from "../../../generated/blocks";

export const sectionFields = {
  accentBg: true,
  heading: true,
  intro: true,
  removeBottomPadding: true,
} as const satisfies SpaSectionFieldMap;
