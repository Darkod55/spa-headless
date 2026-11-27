import assert from "node:assert/strict";
import test from "node:test";
import { splitMenu, withCurrent, type MenuItem } from "../astro/src/lib/wp/menu.ts";

function item(partial: Partial<MenuItem> & Pick<MenuItem, "id" | "title" | "url">): MenuItem {
  return {
    target: "",
    classes: [],
    parent: 0,
    children: [],
    current: false,
    currentAncestor: false,
    ...partial,
  };
}

test("withCurrent marks the page and a nested ancestor", () => {
  const menu = withCurrent(
    [
      item({ id: 1, title: "Home", url: "/" }),
      item({
        id: 2,
        title: "Services",
        url: "/services",
        children: [item({ id: 3, title: "Website design", url: "/services/websites", parent: 2 })],
      }),
    ],
    "/services/websites",
  );

  assert.equal(menu[0]?.current, false);
  assert.equal(menu[1]?.current, false);
  assert.equal(menu[1]?.currentAncestor, true);
  assert.equal(menu[1]?.children[0]?.current, true);
});

test("splitMenu lifts the cta class out of the link list", () => {
  const { links, cta } = splitMenu([
    item({ id: 1, title: "About", url: "/about" }),
    item({ id: 2, title: "Let's talk", url: "/contact", classes: ["cta"] }),
  ]);
  assert.equal(links.length, 1);
  assert.equal(cta?.title, "Let's talk");
});
