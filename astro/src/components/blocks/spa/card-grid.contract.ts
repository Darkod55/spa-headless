import type { SpaCardGridFieldMap } from "../../../generated/blocks";

export const cardGridFields = {
  accentBg: true,
  cards: {
    title: true,
    text: true,
    link: true,
  },
  heading: true,
  intro: true,
  removeBottomPadding: true,
} as const satisfies SpaCardGridFieldMap;
