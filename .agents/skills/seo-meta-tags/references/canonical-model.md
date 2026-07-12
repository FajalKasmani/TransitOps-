# Canonical Metadata Model

Every framework target encodes the same abstract metadata concepts. This reference maps each concept to its implementation across all four framework targets, so you can cross-check parity when adding or modifying fields.

## Token Registry

The canonical registry at [tokens.json](tokens.json) is the single source of truth for all placeholder fields. Before adding a new field, register it there first, then propagate to the relevant framework files.

## Concept-to-Implementation Table

| Concept | HTML | Next.js | Vite React | Vite Vue |
|---|---|---|---|---|
| Page title | `<title>` + `<meta name="title">` | `metadata.title` | `<Helmet><title>` | `useHead({ title })` |
| Description | `<meta name="description">` | `metadata.description` | `<Helmet><meta name="description">` | `useHead({ meta: [{ name: 'description' }] })` |
| Canonical URL | `<link rel="canonical">` | `metadata.alternates.canonical` | `<Helmet><link rel="canonical">` | `useHead({ link: [{ rel: 'canonical' }] })` |
| OG title | `<meta property="og:title">` | `metadata.openGraph.title` | `<Helmet><meta property="og:title">` | `useHead({ meta: [{ property: 'og:title' }] })` |
| OG description | `<meta property="og:description">` | `metadata.openGraph.description` | `<Helmet><meta property="og:description">` | `useHead({ meta: [{ property: 'og:description' }] })` |
| OG image | `<meta property="og:image">` | `metadata.openGraph.images` | `<Helmet><meta property="og:image">` | `useHead({ meta: [{ property: 'og:image' }] })` |
| OG image alt | `<meta property="og:image:alt">` | `metadata.openGraph.images[].alt` | `<Helmet><meta property="og:image:alt">` | `useHead({ meta: [{ property: 'og:image:alt' }] })` |
| OG site name | `<meta property="og:site_name">` | `metadata.openGraph.siteName` | `<Helmet><meta property="og:site_name">` | `useHead({ meta: [{ property: 'og:site_name' }] })` |
| OG locale | `<meta property="og:locale">` | `metadata.openGraph.locale` | `<Helmet><meta property="og:locale">` | `useHead({ meta: [{ property: 'og:locale' }] })` |
| Twitter card type | `<meta name="twitter:card">` | `metadata.twitter.card` | `<Helmet><meta name="twitter:card">` | `useHead({ meta: [{ name: 'twitter:card' }] })` |
| Twitter site | `<meta name="twitter:site">` | `metadata.twitter.site` | `<Helmet><meta name="twitter:site">` | `useHead({ meta: [{ name: 'twitter:site' }] })` |
| Twitter creator | `<meta name="twitter:creator">` | `metadata.twitter.creator` | `<Helmet><meta name="twitter:creator">` | `useHead({ meta: [{ name: 'twitter:creator' }] })` |
| Twitter title | `<meta name="twitter:title">` | `metadata.twitter.title` | `<Helmet><meta name="twitter:title">` | `useHead({ meta: [{ name: 'twitter:title' }] })` |
| Twitter description | `<meta name="twitter:description">` | `metadata.twitter.description` | `<Helmet><meta name="twitter:description">` | `useHead({ meta: [{ name: 'twitter:description' }] })` |
| Twitter image | `<meta name="twitter:image">` | `metadata.twitter.images` | `<Helmet><meta name="twitter:image">` | `useHead({ meta: [{ name: 'twitter:image' }] })` |
| Theme color | `<meta name="theme-color">` | `metadata.themeColor` | Static in `index.html` | Static in `index.html` |
| JSON-LD | `<script type="application/ld+json">` | Separate `<Script>` component | `<Helmet><script>` | `useHead({ script })` |
| Robots | `<meta name="robots">` | `metadata.robots` | Static in `index.html` | Static in `index.html` |
| Icons | `<link rel="icon">` | `metadata.icons` | Static in `index.html` | Static in `index.html` |
| Keywords | `<meta name="keywords">` | `metadata.keywords` | `<Helmet><meta name="keywords">` | `useHead({ meta: [{ name: 'keywords' }] })` |
| Author | `<meta name="author">` | `metadata.authors` | `<Helmet><meta name="author">` | `useHead({ meta: [{ name: 'author' }] })` |

## Metadata Families

### Essential / Primary

| Tag | Constraint |
|---|---|
| `<title>` / `title` | Under 60 characters |
| `description` | 150-160 characters |
| `keywords` | 5-10 terms max, comma-separated |
| `author` | Full name |
| `robots` | `index, follow` by default; include `max-image-preview:large`, `max-snippet:-1`, `max-video-preview:-1` |
| `canonical` | Absolute URL, must match deployment URL |
| `viewport` | `width=device-width, initial-scale=1.0` |

### Open Graph (`og:*`)

| Property | Notes |
|---|---|
| `og:type` | `website` for home/landing; `article` for blog posts; `product` for e-commerce |
| `og:url` | Absolute URL |
| `og:title` | Can differ from `<title>` for social context |
| `og:description` | Can differ from meta description |
| `og:image` | Absolute URL, 1200x630px, JPG or PNG, under 1 MB |
| `og:image:width` / `og:image:height` | `1200` / `630` |
| `og:image:alt` | Required when image is present |
| `og:site_name` | Brand name |
| `og:locale` | e.g. `en_US` |

For `article` type also include: `article:published_time`, `article:modified_time`, `article:author`, `article:tag`.

### Twitter Cards (`twitter:*`)

| Property | Notes |
|---|---|
| `twitter:card` | `summary_large_image` (default) or `summary` |
| `twitter:site` | Organization handle with `@` prefix |
| `twitter:creator` | Author handle with `@` prefix |
| `twitter:title` | Under 70 characters |
| `twitter:description` | Under 200 characters |
| `twitter:image` | Same spec as OG image |
| `twitter:image:alt` | Required when image is present |

### Theme Color

Provide three values:

- Default: `<meta name="theme-color" content="...">` or single value
- Light: `media="(prefers-color-scheme: light)"`
- Dark: `media="(prefers-color-scheme: dark)"`

Next.js uses the `themeColor` array in the Metadata object.

### Icons and Manifest

| Asset | Size | Format |
|---|---|---|
| `favicon.ico` | 32x32 or multi-size | ICO |
| `icon-16x16.png` | 16x16 | PNG |
| `icon-32x32.png` | 32x32 | PNG |
| `apple-touch-icon.png` | 180x180 | PNG |
| `site.webmanifest` | n/a | JSON |

Generate with [favicon.io](https://favicon.io/) or [RealFaviconGenerator](https://realfavicongenerator.net/).

### Structured Data (JSON-LD)

Required keys:

```json
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "",
  "description": "",
  "url": "",
  "image": { "@type": "ImageObject", "url": "", "width": 1200, "height": 630 },
  "author": { "@type": "Person", "name": "" },
  "publisher": {
    "@type": "Organization",
    "name": "",
    "logo": { "@type": "ImageObject", "url": "" }
  }
}
```

Common `@type` values: `WebSite`, `Article`, `Organization`, `Product`, `FAQPage`, `BreadcrumbList`.

### Internationalization (hreflang)

```html
<link rel="alternate" hreflang="en" href="https://example.com/en/" />
<link rel="alternate" hreflang="es" href="https://example.com/es/" />
<link rel="alternate" hreflang="x-default" href="https://example.com/" />
```

Next.js equivalent: `alternates.languages` in the Metadata object.

### Performance Hints

Include when external resources are used:

```html
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link rel="dns-prefetch" href="https://www.google-analytics.com" />
```

## Validation

Run `scripts/validate.sh --check-templates` to verify template integrity against the token registry.
