/**
 * Add a class to the first element in an HTML fragment.
 * Attribute values are scanned with quote awareness so an existing class,
 * or the word "class" inside another attribute, is left intact.
 */
export function addClassToRoot(html: string, className: string): string {
  if (!className || !/^[\w-]+$/.test(className)) {
    return html;
  }

  let index = 0;
  while (index < html.length && /\s/.test(html[index] ?? "")) {
    index += 1;
  }
  if (html[index] !== "<" || html.startsWith("</", index) || html.startsWith("<!", index) || html.startsWith("<?", index)) {
    return html;
  }

  const tagStart = index;
  index += 1;
  while (index < html.length && /[\w:-]/.test(html[index] ?? "")) {
    index += 1;
  }
  if (index === tagStart + 1) {
    return html;
  }

  const tagEnd = findTagEnd(html, index);
  if (tagEnd < 0) {
    return html;
  }

  const open = html.slice(tagStart, tagEnd);
  const rewritten = insertClass(open, className);
  return html.slice(0, tagStart) + rewritten + html.slice(tagEnd);
}

function findTagEnd(html: string, index: number): number {
  let quote: string | null = null;
  for (let cursor = index; cursor < html.length; cursor += 1) {
    const char = html[cursor];
    if (quote) {
      if (char === quote) {
        quote = null;
      }
      continue;
    }
    if (char === '"' || char === "'") {
      quote = char;
      continue;
    }
    if (char === ">") {
      return cursor + 1;
    }
  }
  return -1;
}

function insertClass(openTag: string, className: string): string {
  const closeAt = openTag.endsWith("/>") ? openTag.length - 2 : openTag.length - 1;
  const head = openTag.slice(0, closeAt);
  const tail = openTag.slice(closeAt);
  const attr = findClassAttribute(head);
  if (!attr) {
    return `${head} class="${className}"${tail}`;
  }

  const tokens = attr.value.split(/\s+/).filter((token) => token.length > 0);
  if (tokens.includes(className)) {
    return openTag;
  }
  const next = [...tokens, className].join(" ");
  const quoted = `${attr.quote}${next}${attr.quote}`;
  return head.slice(0, attr.valueStart - 1) + quoted + head.slice(attr.valueEnd + 1) + tail;
}

function findClassAttribute(head: string): { quote: string; value: string; valueStart: number; valueEnd: number } | null {
  let index = 0;
  while (index < head.length && head[index] !== " " && head[index] !== "\n" && head[index] !== "\t") {
    index += 1;
  }

  while (index < head.length) {
    while (index < head.length && /\s/.test(head[index] ?? "")) {
      index += 1;
    }
    if (index >= head.length || head[index] === "/" || head[index] === ">") {
      return null;
    }
    const nameStart = index;
    while (index < head.length && /[\w:-]/.test(head[index] ?? "")) {
      index += 1;
    }
    const name = head.slice(nameStart, index).toLowerCase();
    while (index < head.length && /\s/.test(head[index] ?? "")) {
      index += 1;
    }
    if (head[index] !== "=") {
      continue;
    }
    index += 1;
    while (index < head.length && /\s/.test(head[index] ?? "")) {
      index += 1;
    }
    const quote = head[index];
    if (quote !== '"' && quote !== "'") {
      continue;
    }
    const valueStart = index + 1;
    const valueEnd = head.indexOf(quote, valueStart);
    if (valueEnd < 0) {
      return null;
    }
    if (name === "class") {
      return { quote, value: head.slice(valueStart, valueEnd), valueStart, valueEnd };
    }
    index = valueEnd + 1;
  }

  return null;
}
