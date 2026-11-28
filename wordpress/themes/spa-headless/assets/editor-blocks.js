/**
 * BlockStudio reads text_colour.slug. Saved posts can have "" at the top
 * level and the real {value,name,slug} under blockstudio.attributes.
 * Gutenberg drops a string when the schema says object, and the control
 * throws on "". Promote the nested object before the editor renders.
 */
(function (wp) {
  if (!wp || !wp.hooks) {
    return;
  }

  var names = { navy: "Navy", red: "Red", royal: "Royal", black: "Black", white: "White" };

  function colourObject(value) {
    if (value && typeof value === "object" && typeof value.slug === "string" && names[value.slug]) {
      return {
        value: typeof value.value === "string" && value.value ? value.value : "var(--color-" + value.slug + ")",
        name: typeof value.name === "string" && value.name ? value.name : names[value.slug],
        slug: value.slug,
      };
    }
    if (typeof value !== "string") {
      return null;
    }
    var slug = value.trim();
    var match = slug.match(/^var\(--color-([a-z]+)\)$/);
    if (match) {
      slug = match[1];
    }
    if (!names[slug]) {
      return null;
    }
    return { value: "var(--color-" + slug + ")", name: names[slug], slug: slug };
  }

  wp.hooks.addFilter(
    "blocks.getBlockAttributes",
    "spa/colour-shape",
    function (blockAttributes, blockType, innerHTML, commentAttributes) {
      if (!commentAttributes) {
        return blockAttributes;
      }
      var nested =
        commentAttributes.blockstudio && commentAttributes.blockstudio.attributes
          ? commentAttributes.blockstudio.attributes
          : {};
      ["text_colour", "background"].forEach(function (key) {
        var chosen = colourObject(nested[key]) || colourObject(commentAttributes[key]) || colourObject(blockAttributes[key]);
        if (chosen) {
          blockAttributes[key] = chosen;
        } else if (blockAttributes[key] === "") {
          delete blockAttributes[key];
        }
      });
      return blockAttributes;
    },
  );
})(window.wp);
