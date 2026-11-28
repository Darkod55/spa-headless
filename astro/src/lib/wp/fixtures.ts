import type { SiteIcon } from "./icons";
import type { MenuItem, WpMenu, WpSite } from "./menu";
import type { WpBlock, WpContent, WpRoute } from "./types";

export interface FixtureEntry {
  content: WpContent;
  head: string;
}

function block(
  blockName: string | null,
  attrs: Record<string, unknown>,
  innerBlocks: WpBlock[] = [],
  innerHTML = "",
): WpBlock {
  const innerContent: Array<string | null> =
    innerBlocks.length > 0
      ? innerBlocks.map(() => null)
      : innerHTML
        ? [innerHTML]
        : [];
  return { blockName, attrs, innerBlocks, innerHTML, innerContent };
}

function paragraph(text: string): WpBlock {
  const html = `<p>${text}</p>`;
  return block("core/paragraph", {}, [], html);
}

function heading(level: number, text: string): WpBlock {
  const html = `<h${level}>${text}</h${level}>`;
  return block("core/heading", { level, content: text }, [], html);
}

function page(options: {
  id: number;
  path: string;
  title: string;
  type?: "page" | "post";
  description: string;
  blocks: WpBlock[];
  eyebrow?: string;
}): FixtureEntry {
  const slug = options.path === "/" ? "home" : (options.path.split("/").filter(Boolean).at(-1) ?? "home");
  const type = options.type ?? "page";
  const canonical = `http://localhost:4321${options.path === "/" ? "/" : options.path}`;
  return {
    content: {
      id: options.id,
      type,
      slug,
      path: options.path,
      status: "publish",
      title: options.title,
      excerpt: options.description,
      link: `http://squarepeg-cms.local${options.path === "/" ? "/" : `${options.path}/`}`,
      meta: {
        spa_eyebrow: options.eyebrow ?? "",
        spa_hide_title: false,
      },
      blocks: options.blocks,
    },
    head: [
      `<title>${options.title} — The Square Peg Agency</title>`,
      `<meta name="description" content="${options.description}">`,
      `<link rel="canonical" href="${canonical}">`,
      `<meta property="og:title" content="${options.title}">`,
    ].join("\n"),
  };
}

const home = page({
  id: 1,
  path: "/",
  title: "Strategic marketing for addiction, mental health and wellness",
  description:
    "The Square Peg Agency helps organisations in addiction, mental health, behavioural health and wellness stand out.",
  eyebrow: "The Square Peg Agency",
  blocks: [
    block("spa/hero", {
      eyebrow: "Addiction, mental health and wellness",
      heading: "Why fit in when you can stand out?",
      description:
        "Strategic marketing for organisations that need a team who understands the sector and the people it serves.",
      primaryButton: { title: "Our services", url: "/services" },
      secondaryButton: { title: "Get in touch", url: "/contact" },
      accentBg: false,
      removeBottomPadding: false,
    }),
    block(
      "spa/section",
      {
        heading: "How we help",
        intro: "Understanding first. Strategy second. Implementation third.",
      },
      [
        paragraph(
          "You don't need another marketing agency. You need one that can communicate your expertise and help the organisation grow with care.",
        ),
        block(
          "core/list",
          { ordered: true },
          [
            block("core/list-item", {}, [], "<li>Marketing audits and a practical plan</li>"),
            block("core/list-item", {}, [], "<li>Brand, content, and the website as one system</li>"),
            block("core/list-item", {}, [], "<li>Ongoing support when the work needs to keep moving</li>"),
          ],
          "<ol></ol>",
        ),
        block(
          "core/quote",
          {},
          [],
          "<blockquote><p>Each part of the marketing has a role, and the strength comes from joining them up.</p></blockquote>",
        ),
      ],
    ),
    block("spa/card-grid", {
      heading: "Where we usually start",
      intro: "Three of the twelve services. The rest are composed the same way in WordPress.",
      cards: [
        {
          title: "Marketing strategy",
          text: "A clear picture of where you are, where you are going, and what happens next.",
          link: { title: "Talk to us", url: "/contact" },
        },
        {
          title: "Brand strategy and design",
          text: "Identity and language the organisation can use without a second translation.",
          link: { title: "See services", url: "/services" },
        },
        {
          title: "Website design and development",
          text: "A site that explains the work and makes the next step obvious.",
          link: { title: "About the rebuild", url: "/about" },
        },
      ],
    }),
  ],
});

const about = page({
  id: 2,
  path: "/about",
  title: "About",
  description: "A specialist marketing agency working across addiction, mental health and wellness.",
  blocks: [
    block(
      "spa/section",
      {
        heading: "About The Square Peg Agency",
        intro: "Strategy, content, design, and development, held in one team.",
        accentBg: true,
      },
      [
        paragraph(
          "We work with organisations in addiction, mental health, behavioural health and wellness. The public site you are looking at is a fixture until Local WP is connected.",
        ),
        heading(2, "What the editor can place inside a section"),
        block("core/image", {
          url: "/fixtures/mark.svg",
          alt: "Square Peg mark",
        }),
        block(
          "core/buttons",
          {},
          [
            block("core/button", { url: "/services", text: "Services" }),
            block("core/button", { url: "/contact", text: "Contact" }),
          ],
        ),
        block(
          "core/group",
          {},
          [paragraph("A group is a core block. Its children render through the same recursive renderer.")],
        ),
        block(
          "core/columns",
          {},
          [
            block("core/column", {}, [paragraph("Columns keep their own inner blocks.")]),
            block("core/column", {}, [paragraph("The Astro column component is a slot, not a second source of copy.")]),
          ],
        ),
      ],
    ),
  ],
});

const services = page({
  id: 3,
  path: "/services",
  title: "Services",
  description: "Marketing, brand, digital, content, and ongoing support for specialist organisations.",
  blocks: [
    block("spa/hero", {
      eyebrow: "Services",
      heading: "Marketing that holds together",
      description:
        "Strategy, brand, website, content, search, and the work that keeps it moving. Slugs stay identical to the live site.",
      primaryButton: { title: "Start a conversation", url: "/contact" },
    }),
    block("spa/card-grid", {
      heading: "The set",
      cards: [
        { title: "Marketing strategy", text: "Audits, plans, and senior marketing leadership." },
        { title: "Brand strategy and design", text: "Positioning, identity, and the assets around them." },
        { title: "Website design and development", text: "WordPress and custom builds, including this headless rebuild." },
        { title: "Content and copy", text: "Writing that is useful to the people the organisation serves." },
        { title: "Search and visibility", text: "Technical SEO and content that can be found, including in AI search." },
        { title: "Ongoing support", text: "Hosting, reporting, and marketing that does not stall after launch." },
      ],
    }),
  ],
});

const contact = page({
  id: 4,
  path: "/contact",
  title: "Contact",
  description: "Talk to The Square Peg Agency.",
  blocks: [
    block(
      "spa/section",
      {
        heading: "Get in touch",
        intro: "Send a message below, or email hello@thesquarepeg.agency.",
      },
      [
        paragraph(
          "The form posts to the Astro /api/contact route and checks Cloudflare Turnstile when keys are set. Mail delivery is not wired in this scaffold. Fluent Forms remains the alternative if submissions should live in WordPress.",
        ),
      ],
    ),
  ],
});

const blog = page({
  id: 5,
  path: "/blog",
  title: "Blog",
  description: "Notes on marketing, trust, and search for specialist organisations.",
  blocks: [
    block("spa/card-grid", {
      heading: "From the journal",
      intro: "Permalinks use /blog/%postname%/, the same shape as the live site.",
      cards: [
        {
          title: "How AI search is changing the way people find support services",
          text: "People asking AI tools for help still need a clear, trustworthy site.",
          link: { title: "Read", url: "/blog/how-ai-search-is-changing-support-services" },
        },
        {
          title: "Why trust matters more than reach",
          text: "A smaller audience that believes you is the point.",
          link: { title: "Read", url: "/blog/why-trust-matters-more-than-reach" },
        },
        {
          title: "Writing about mental health with care",
          text: "The words have to be as careful as the work.",
          link: { title: "Read", url: "/blog/writing-about-mental-health-with-care" },
        },
      ],
    }),
  ],
});

function post(id: number, slug: string, title: string, description: string): FixtureEntry {
  return page({
    id,
    path: `/blog/${slug}`,
    title,
    type: "post",
    description,
    blocks: [
      paragraph(description),
      paragraph(
        "This is fixture copy so the Astro build can run without WordPress. After migration, the post body is whatever blocks are saved in the CMS, at this same slug.",
      ),
    ],
  });
}

const legal = (id: number, slug: string, title: string): FixtureEntry =>
  page({
    id,
    path: `/${slug}`,
    title,
    description: `${title} for The Square Peg Agency.`,
    blocks: [
      paragraph(
        `${title} will be migrated from the current site at this slug. This fixture only holds the path so the route exists before WordPress is connected.`,
      ),
    ],
  });

export const fixtures: FixtureEntry[] = [
  home,
  about,
  services,
  contact,
  blog,
  legal(6, "privacy-policy", "Privacy policy"),
  legal(7, "cookie-policy", "Cookie policy"),
  legal(8, "terms-and-conditions", "Terms and conditions"),
  post(
    9,
    "how-ai-search-is-changing-support-services",
    "How AI search is changing the way people find support services",
    "Search behaviour is shifting. People asking AI tools for help still need clear, trustworthy information.",
  ),
  post(
    10,
    "why-trust-matters-more-than-reach",
    "Why trust matters more than reach",
    "Reach is easy to count. Trust is what makes the work worth finding.",
  ),
  post(
    11,
    "is-your-marketing-moving-your-business-forward",
    "Is your marketing moving your business forward?",
    "Activity is not the same thing as progress.",
  ),
  post(
    12,
    "when-is-the-right-time-to-rebrand",
    "When is the right time to rebrand?",
    "A rebrand is worth it when the organisation has outgrown the way it explains itself.",
  ),
  post(
    13,
    "writing-about-mental-health-with-care",
    "Writing about mental health with care",
    "The writing has to respect the people the organisation exists to support.",
  ),
  post(
    14,
    "reading-your-analytics-without-getting-lost",
    "Reading your analytics without getting lost",
    "A few numbers, read in context, are more useful than a dashboard nobody opens.",
  ),
];

const byPath = new Map(fixtures.map((entry) => [entry.content.path, entry]));

export function fixtureRoutes(): WpRoute[] {
  return fixtures.map((entry) => ({
    path: entry.content.path,
    id: entry.content.id,
    type: entry.content.type,
    slug: entry.content.slug,
  }));
}

export function fixtureContent(path: string): WpContent | null {
  return byPath.get(path)?.content ?? null;
}

export function fixtureHead(path: string): string | null {
  return byPath.get(path)?.head ?? null;
}

function menuItem(
  id: number,
  title: string,
  url: string,
  extras: Partial<MenuItem> = {},
): MenuItem {
  return {
    id,
    title,
    url,
    target: "",
    classes: [],
    parent: 0,
    children: [],
    current: false,
    currentAncestor: false,
    ...extras,
  };
}

/** Matches the live header, plus one nested item so dropdowns can be checked. */
export function fixtureMenu(location = "primary"): WpMenu | null {
  if (location === "primary") {
    return {
      location,
      items: [
        menuItem(1, "Home", "/"),
        menuItem(2, "About", "/about"),
        menuItem(3, "Services", "/services", {
          children: [menuItem(5, "Website design", "/services", { parent: 3 })],
        }),
        menuItem(4, "Let's talk", "/contact", { classes: ["cta"] }),
      ],
    };
  }

  if (location === "footer") {
    return {
      location,
      items: [
        menuItem(11, "Home", "/"),
        menuItem(12, "About", "/about"),
        menuItem(13, "Services", "/services"),
        menuItem(14, "Contact", "/contact"),
      ],
    };
  }

  if (location === "legal") {
    return {
      location,
      items: [
        menuItem(21, "Privacy Policy", "/privacy-policy"),
        menuItem(22, "Cookie Policy", "/cookie-policy"),
        menuItem(23, "Terms and Conditions", "/terms-and-conditions"),
        menuItem(24, "Sitemap", "/sitemap.xml"),
      ],
    };
  }

  if (location === "footer-social") {
    return {
      location,
      items: [
        menuItem(31, "LinkedIn", "https://www.linkedin.com/company/94275355/"),
        menuItem(32, "Facebook", "https://www.facebook.com/thesquarepegagency"),
        menuItem(33, "Instagram", "https://www.instagram.com/thesquarepegagency/"),
        menuItem(34, "X", "https://x.com/thesquarepegagency"),
        menuItem(35, "YouTube", "https://www.youtube.com/@thesquarepegagency"),
      ],
    };
  }

  return null;
}

/** Live repo favicon fallback: public/logo.svg (BaseLayout uses this when EmDash has no favicon). */
export function fixtureSiteIcon(): SiteIcon {
  const url = "/fixtures/favicon.svg";
  return {
    mime: "image/svg+xml",
    sizes: { "32": url, "180": url, "192": url, "270": url },
  };
}

export function fixtureSite(): WpSite {
  return {
    title: "The Square Peg Agency",
    description: "Strategic marketing for addiction, mental health and wellness.",
    url: "http://squarepeg-cms.local/",
    logo: {
      url: "/fixtures/logo.svg",
      width: 820,
      height: 212,
      alt: "The Square Peg Agency",
      mime: "image/svg+xml",
    },
    siteIcon: fixtureSiteIcon(),
    announcement: {
      enabled: true,
      message: "Is your website underperforming?\nNot ranking on Google or showing up in AI search?",
      linkLabel: "Book your free website audit",
      url: "/contact?enquiry=website-audit",
      target: "",
    },
  };
}
