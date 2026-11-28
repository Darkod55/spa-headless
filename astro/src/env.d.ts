/// <reference path="../.astro/types.d.ts" />
/// <reference types="astro/client" />

interface ImportMetaEnv {
  readonly WP_URL?: string;
  readonly WP_FIXTURES?: string;
  readonly PUBLIC_SITE_URL?: string;
  readonly PUBLIC_GA_MEASUREMENT_ID?: string;
  readonly PUBLIC_TURNSTILE_SITE_KEY?: string;
  readonly TURNSTILE_SECRET_KEY?: string;
  readonly SPA_PREVIEW_TOKEN?: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}
