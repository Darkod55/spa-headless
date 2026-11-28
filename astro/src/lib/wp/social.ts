export type SocialPlatform = "facebook" | "instagram" | "linkedin" | "x" | "youtube" | "link";

const PLATFORM_NAMES: Record<SocialPlatform, string> = {
  facebook: "Facebook",
  instagram: "Instagram",
  linkedin: "LinkedIn",
  x: "X",
  youtube: "YouTube",
  link: "Link",
};

function hostOf(url: string): string {
  try {
    return new URL(url).hostname.toLowerCase().replace(/^www\./, "");
  } catch {
    return "";
  }
}

function isHost(host: string, name: string): boolean {
  return host === name || host.endsWith(`.${name}`);
}

/** Platform from the link host. Unknown or unparseable URLs are a generic link. */
export function socialPlatformFromUrl(url: string): SocialPlatform {
  const host = hostOf(url);
  if (!host) {
    return "link";
  }
  if (isHost(host, "facebook.com") || isHost(host, "fb.com")) {
    return "facebook";
  }
  if (isHost(host, "instagram.com")) {
    return "instagram";
  }
  if (isHost(host, "linkedin.com")) {
    return "linkedin";
  }
  if (isHost(host, "x.com") || isHost(host, "twitter.com")) {
    return "x";
  }
  if (isHost(host, "youtube.com") || isHost(host, "youtu.be")) {
    return "youtube";
  }
  return "link";
}

export function socialPlatformName(platform: SocialPlatform): string {
  return PLATFORM_NAMES[platform];
}

/** Menu item title, or the platform name when the title is empty. */
export function socialAriaLabel(title: string, platform: SocialPlatform): string {
  const trimmed = title.trim();
  return trimmed || socialPlatformName(platform);
}
