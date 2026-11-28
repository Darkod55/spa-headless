export const buttonTypes = ["primary", "light", "dark", "outline-light", "text"] as const;

export type ButtonType = (typeof buttonTypes)[number];

type ButtonAttrs = {
  url?: unknown;
  text?: unknown;
  linkTarget?: unknown;
  rel?: unknown;
  button_type?: unknown;
  arrow?: unknown;
  blockstudio?: { attributes?: { button_type?: unknown; arrow?: unknown } };
};

/** A select saved as `{ value, label }` or as the slug itself. */
export function buttonType(value: unknown): ButtonType {
  const raw = value && typeof value === "object" && "value" in value ? value.value : value;
  if (typeof raw === "string") {
    const slug = raw.startsWith("cta--") ? raw.slice(5) : raw;
    if ((buttonTypes as readonly string[]).includes(slug)) {
      return slug as ButtonType;
    }
  }
  return "primary";
}

function arrowValue(value: unknown): boolean {
  return !(value === false || value === "false" || value === 0 || value === "0");
}

/**
 * Missing means on, matching the live Button default and the toggle default.
 * `false` is dropped by the normalizer's empty check, so the nest still counts.
 */
export function buttonShowsArrow(attrs: ButtonAttrs): boolean {
  const nested = attrs.blockstudio?.attributes;
  if (nested && Object.prototype.hasOwnProperty.call(nested, "arrow")) {
    return arrowValue(nested.arrow);
  }
  if (Object.prototype.hasOwnProperty.call(attrs, "arrow")) {
    return arrowValue(attrs.arrow);
  }
  return true;
}

export function buttonLabel(attrs: ButtonAttrs, innerHTML = ""): string {
  if (typeof attrs.text === "string" && attrs.text.trim() !== "") {
    return attrs.text;
  }
  const link = innerHTML.match(/<a\b[^>]*>([\s\S]*?)<\/a>/i);
  const text = link?.[1]?.replace(/<[^>]*>/g, "").replace(/\s+/g, " ").trim();
  return text ?? "";
}

export function buttonUrl(attrs: ButtonAttrs, innerHTML = ""): string {
  if (typeof attrs.url === "string" && attrs.url.trim() !== "") {
    return attrs.url;
  }
  const href = innerHTML.match(/\bhref="([^"]*)"/i);
  return href?.[1] ?? "";
}

export function buttonOpensNewTab(attrs: ButtonAttrs): boolean {
  return attrs.linkTarget === "_blank";
}
