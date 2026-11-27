/**
 * Read BlockStudio block.json files and collect the attribute ids that
 * are stored on the block. Structural nodes (tabs, un-named groups,
 * messages) are not attributes.
 *
 * Named groups flatten children and prefix their ids (`presentation` +
 * `align` → `presentation_align`), matching BlockStudio's storage.
 * Repeater rows keep their own fields.
 */

export interface FieldNode {
  id?: string;
  type?: string;
  multiple?: boolean;
  attributes?: FieldNode[];
  tabs?: Array<{ attributes?: FieldNode[] }>;
}

export interface CollectedField {
  id: string;
  type: string;
  multiple: boolean | undefined;
  children: CollectedField[];
}

const STRUCTURAL = new Set(["tabs", "group", "message"]);

export function collectFields(
  nodes: FieldNode[],
  resolveCustom: (name: string) => FieldNode[],
  prefix = "",
  stack: string[] = [],
): CollectedField[] {
  const fields: CollectedField[] = [];

  for (const node of nodes) {
    const type = node.type ?? "";

    if (type === "tabs") {
      const tabNodes = node.tabs ?? [];
      for (const tab of tabNodes) {
        fields.push(...collectFields(tab.attributes ?? [], resolveCustom, prefix, stack));
      }
      if (tabNodes.length === 0 && node.attributes) {
        fields.push(...collectFields(node.attributes, resolveCustom, prefix, stack));
      }
      continue;
    }

    if (type.startsWith("custom/")) {
      const name = type.slice("custom/".length);
      if (stack.includes(name)) {
        throw new Error(`Custom field cycle: ${[...stack, name].join(" -> ")}`);
      }
      fields.push(...collectFields(resolveCustom(name), resolveCustom, prefix, [...stack, name]));
      continue;
    }

    if (type === "group") {
      const nextPrefix = node.id ? `${prefix}${node.id}_` : prefix;
      fields.push(...collectFields(node.attributes ?? [], resolveCustom, nextPrefix, stack));
      continue;
    }

    if (!node.id || STRUCTURAL.has(type)) {
      continue;
    }

    const id = `${prefix}${node.id}`;
    if (type === "repeater") {
      fields.push({
        id,
        type,
        multiple: node.multiple,
        children: collectFields(node.attributes ?? [], resolveCustom, "", stack),
      });
      continue;
    }

    fields.push({
      id,
      type,
      multiple: node.multiple,
      children: [],
    });
  }

  return fields;
}

export function fieldIds(fields: CollectedField[]): string[] {
  return fields.map((field) => field.id);
}

/** Nested manifest: repeater rows are objects, every other field is `true`. */
export type FieldManifest = { [key: string]: true | FieldManifest };

export function fieldManifest(fields: CollectedField[]): FieldManifest {
  const manifest: FieldManifest = {};
  for (const field of fields) {
    manifest[field.id] = field.type === "repeater" ? fieldManifest(field.children) : true;
  }
  return manifest;
}

export function manifestIdentifiers(manifest: FieldManifest): string[] {
  const ids: string[] = [];
  for (const [id, value] of Object.entries(manifest)) {
    ids.push(id);
    if (value !== true) {
      ids.push(...manifestIdentifiers(value));
    }
  }
  return ids;
}

export function diffFieldIds(
  expected: string[],
  actual: string[],
): { missing: string[]; extra: string[] } {
  const expectedSet = new Set(expected);
  const actualSet = new Set(actual);
  return {
    missing: expected.filter((id) => !actualSet.has(id)),
    extra: actual.filter((id) => !expectedSet.has(id)),
  };
}

export function pascal(value: string): string {
  return value
    .split(/[^A-Za-z0-9]+/)
    .filter((part) => part.length > 0)
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join("");
}

export function splitBlockName(name: string): { namespace: string; slug: string } {
  const slash = name.indexOf("/");
  if (slash <= 0 || slash !== name.lastIndexOf("/") || slash === name.length - 1) {
    throw new Error(`Block name "${name}" must be namespace/slug.`);
  }
  return {
    namespace: name.slice(0, slash),
    slug: name.slice(slash + 1),
  };
}

export function attributeInterfaceName(blockName: string): string {
  const { namespace, slug } = splitBlockName(blockName);
  return `${pascal(namespace)}${pascal(slug)}Attributes`;
}

export function componentBaseName(blockName: string): string {
  return pascal(splitBlockName(blockName).slug);
}

export function contractFileName(blockName: string): string {
  return `${splitBlockName(blockName).slug}.contract.ts`;
}

export function tsTypeFor(field: CollectedField, indent = 0): string {
  const pad = " ".repeat(indent);
  switch (field.type) {
    case "toggle":
      return "boolean";
    case "number":
    case "range":
      return "number";
    case "link":
      return "SpaLinkValue";
    case "files":
      return field.multiple === false ? "SpaFileValue | null" : "SpaFileValue[]";
    case "repeater": {
      const rows = field.children
        .map((child) => `${pad}  ${child.id}?: ${tsTypeFor(child, indent + 2)};`)
        .join("\n");
      return `Array<{\n${rows}\n${pad}}>`;
    }
    case "text":
    case "textarea":
    case "richtext":
    case "select":
    case "radio":
    case "icon":
    case "color":
    case "url":
    case "email":
    case "date":
    case "time":
    case "datetime":
      return "string";
    default:
      return "unknown";
  }
}

export function parseContractManifest(source: string, file: string): FieldManifest {
  const match = source.match(/export const \w+ = (\{[\s\S]*\}) as const satisfies/);
  if (!match?.[1]) {
    throw new Error(
      `${file} must export \`export const <name> = { ... } as const satisfies <Block>FieldMap\`.`,
    );
  }
  return parseManifestObject(match[1], file);
}

function parseManifestObject(source: string, file: string): FieldManifest {
  const body = source.trim();
  if (!body.startsWith("{") || !body.endsWith("}")) {
    throw new Error(`${file} contract must be an object literal.`);
  }

  const manifest: FieldManifest = {};
  let index = 1;
  const end = body.length - 1;

  while (index < end) {
    while (index < end && /[\s,]/.test(body[index] ?? "")) {
      index += 1;
    }
    if (index >= end) {
      break;
    }

    const keyMatch = /^([A-Za-z_][A-Za-z0-9_]*)/.exec(body.slice(index));
    if (!keyMatch?.[1]) {
      throw new Error(`${file} has an unreadable contract key near "${body.slice(index, index + 24)}".`);
    }
    const key = keyMatch[1];
    index += key.length;
    while (index < end && /\s/.test(body[index] ?? "")) {
      index += 1;
    }
    if (body[index] !== ":") {
      throw new Error(`${file} contract key ${key} is missing a colon.`);
    }
    index += 1;
    while (index < end && /\s/.test(body[index] ?? "")) {
      index += 1;
    }

    if (body.startsWith("true", index)) {
      manifest[key] = true;
      index += 4;
      continue;
    }

    if (body[index] === "{") {
      const nestedStart = index;
      let depth = 0;
      for (; index < body.length; index += 1) {
        if (body[index] === "{") {
          depth += 1;
        } else if (body[index] === "}") {
          depth -= 1;
          if (depth === 0) {
            index += 1;
            break;
          }
        }
      }
      manifest[key] = parseManifestObject(body.slice(nestedStart, index), file);
      continue;
    }

    throw new Error(`${file} contract value for ${key} must be true or a nested object.`);
  }

  return manifest;
}

export function diffManifest(
  expected: FieldManifest,
  actual: FieldManifest,
  prefix = "",
): { missing: string[]; extra: string[] } {
  const missing: string[] = [];
  const extra: string[] = [];
  const expectedKeys = new Set(Object.keys(expected));
  const actualKeys = new Set(Object.keys(actual));

  for (const key of expectedKeys) {
    const path = prefix ? `${prefix}.${key}` : key;
    if (!actualKeys.has(key)) {
      missing.push(path);
      continue;
    }
    const expectedValue = expected[key];
    const actualValue = actual[key];
    if (expectedValue === true && actualValue === true) {
      continue;
    }
    if (expectedValue === true || actualValue === true || !expectedValue || !actualValue) {
      missing.push(path);
      extra.push(path);
      continue;
    }
    const nested = diffManifest(expectedValue, actualValue, path);
    missing.push(...nested.missing);
    extra.push(...nested.extra);
  }

  for (const key of actualKeys) {
    if (!expectedKeys.has(key)) {
      extra.push(prefix ? `${prefix}.${key}` : key);
    }
  }

  return { missing, extra };
}
