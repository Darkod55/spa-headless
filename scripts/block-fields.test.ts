import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";
import test from "node:test";
import {
  collectFields,
  diffFieldIds,
  diffManifest,
  fieldIds,
  fieldManifest,
  parseContractManifest,
  type FieldManifest,
  type FieldNode,
} from "./block-fields.ts";

const root = join(dirname(fileURLToPath(import.meta.url)), "..");
const fieldsDir = join(root, "wordpress/themes/spa-headless/blockstudio/fields");

function readJson(path: string): { blockstudio?: { attributes?: FieldNode[] }; attributes?: FieldNode[] } {
  return JSON.parse(readFileSync(path, "utf8")) as {
    blockstudio?: { attributes?: FieldNode[] };
    attributes?: FieldNode[];
  };
}

function resolveCustom(name: string): FieldNode[] {
  const parsed = readJson(join(fieldsDir, name, "field.json"));
  return parsed.attributes ?? [];
}

test("hero fields include content fields and the shared section toggles", () => {
  const block = readJson(join(root, "wordpress/themes/spa-headless/blockstudio/sections/hero/block.json"));
  const fields = collectFields(block.blockstudio?.attributes ?? [], resolveCustom);
  assert.deepEqual(fieldIds(fields), [
    "eyebrow",
    "heading",
    "description",
    "primaryButton",
    "secondaryButton",
    "image",
    "removeBottomPadding",
    "accentBg",
  ]);
  const image = fields.find((field) => field.id === "image");
  assert.equal(image?.type, "files");
  assert.equal(image?.multiple, false);
});

test("section fields stay flat and do not invent an InnerBlocks attribute", () => {
  const block = readJson(
    join(root, "wordpress/themes/spa-headless/blockstudio/sections/section/block.json"),
  );
  assert.deepEqual(fieldIds(collectFields(block.blockstudio?.attributes ?? [], resolveCustom)), [
    "heading",
    "intro",
    "removeBottomPadding",
    "accentBg",
  ]);
});

test("card grid keeps repeater row fields nested", () => {
  const block = readJson(
    join(root, "wordpress/themes/spa-headless/blockstudio/sections/card-grid/block.json"),
  );
  const fields = collectFields(block.blockstudio?.attributes ?? [], resolveCustom);
  const cards = fields.find((field) => field.id === "cards");
  assert.equal(cards?.type, "repeater");
  assert.deepEqual(fieldIds(cards?.children ?? []), ["title", "text", "link"]);
});

test("a named group prefixes child ids", () => {
  const nodes: FieldNode[] = [
    {
      id: "presentation",
      type: "group",
      attributes: [{ id: "align", type: "select" }],
    },
  ];
  assert.deepEqual(fieldIds(collectFields(nodes, () => [])), ["presentation_align"]);
});

test("diffFieldIds reports added and removed fields", () => {
  assert.deepEqual(diffFieldIds(["heading", "kicker"], ["heading", "eyebrow"]), {
    missing: ["kicker"],
    extra: ["eyebrow"],
  });
});

test("parseContractManifest reads nested repeater rows", () => {
  const source = `
    export const cardGridFields = {
      heading: true,
      cards: {
        title: true,
        link: true,
      },
    } as const satisfies SpaCardGridFieldMap;
  `;
  assert.deepEqual(parseContractManifest(source, "card-grid.contract.ts"), {
    heading: true,
    cards: { title: true, link: true },
  });
});

test("diffManifest reports a repeater field that was not acknowledged", () => {
  const expected = fieldManifest([
    {
      id: "cards",
      type: "repeater",
      multiple: undefined,
      children: [
        { id: "title", type: "text", multiple: undefined, children: [] },
        { id: "kicker", type: "text", multiple: undefined, children: [] },
      ],
    },
  ]);
  const actual: FieldManifest = { cards: { title: true } };
  assert.deepEqual(diffManifest(expected, actual), {
    missing: ["cards.kicker"],
    extra: [],
  });
});
