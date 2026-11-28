import type { WpBlock } from "../wp/types";

export type FallbackPiece = { kind: "html"; html: string } | { kind: "child"; index: number };

/**
 * Unmapped blocks keep WordPress's saved markup. innerContent mixes HTML
 * strings with nulls; each null is the slot where the next inner block sits.
 * innerHTML is only the string pieces, so rendering it alone drops children.
 */
export function fallbackPlan(block: WpBlock): FallbackPiece[] {
  const source = block.innerContent.length > 0 ? block.innerContent : [block.innerHTML];
  const plan: FallbackPiece[] = [];
  let child = 0;
  let sawNull = false;

  for (const piece of source) {
    if (piece === null) {
      sawNull = true;
      plan.push({ kind: "child", index: child });
      child += 1;
      continue;
    }
    if (piece.trim() !== "") {
      plan.push({ kind: "html", html: piece });
    }
  }

  if (!sawNull) {
    for (let index = 0; index < block.innerBlocks.length; index += 1) {
      plan.push({ kind: "child", index });
    }
  }

  return plan;
}
