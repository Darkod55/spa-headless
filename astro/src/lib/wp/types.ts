export interface WpBlock {
  blockName: string | null;
  attrs: Record<string, unknown>;
  innerBlocks: WpBlock[];
  innerHTML: string;
  innerContent: Array<string | null>;
}

export interface SpaPublicMeta {
  spa_eyebrow: string;
  spa_hide_title: boolean;
}

export interface WpContent {
  id: number;
  type: "page" | "post";
  slug: string;
  path: string;
  status: string;
  title: string;
  excerpt: string;
  link: string;
  meta: SpaPublicMeta;
  blocks: WpBlock[];
}

export interface WpRoute {
  path: string;
  id: number;
  type: "page" | "post";
  slug: string;
}
