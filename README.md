# synmod-cms

Content Management System module for [Laravel Synapse](https://github.com/vm-engine/synapse). Provides static pages, dynamic articles, a gallery system, frontend menu management, and embeddable widget components — all with automatic slug-based public routing via `synapps-fallback`.

**Package:** `vm-engine/synmod-cms`  
**Module ID:** `cms`  
**Requires:** PHP 8.2+, Laravel 12, Livewire 4, `vm-engine/synapse`, `vm-engine/synapps-auth`, `vm-engine/synapps-fallback`, `vm-engine/synmod-fm`

---

## Features

- **Static Pages** — up to 3 levels deep (`/parent/child/grandchild`), with a rich-text body editor, SEO fields, cover image, and per-page display config (show title, breadcrumb, children layout)
- **Sections / Categories / Articles** — hierarchical dynamic content; categories are optional; articles support tags, scheduling (`published_at`, `expires_at`), featured flag, and automatic read-time computation
- **Gallery** — albums of type `normal` (public, routed at `/gallery/{slug}`) or `banner` (display-only, no public route); drag-and-drop image ordering; auto-sets album cover from first image uploaded; FM file picker integration
- **Menus** — user-defined positions (`header`, `footer`, `sidebar`, or any string), infinitely nested items linking to pages, sections, categories, articles, albums, or custom URLs, drag-and-drop reordering
- **Tags** — shared across articles with unique slug, managed via inline modal
- **Downloads** — categorised file downloads with `public`/`private` (login-required) access via the `DownloadAccess` enum, drag-and-drop file ordering, FM file picker integration
- **Custom Variables** — configurable key/value pairs on all content types (pages, sections, categories, articles, albums, album images), accessible in frontend components and templates
- **Custom View & Layout Overrides** — every content type can override its rendered Blade view and/or layout per-record, with a cascading fallback (article → category → section, page → parent page); see [Custom View & Layout Overrides](#custom-view--layout-overrides)
- **Automatic Fallback Routing** — all published content registers its public slug in `fallback_routes` on save and removes it on delete/unpublish; slug renames cascade to children automatically; no route cache clearing needed
- **Rich-text editor** — Jodit (default) or TinyMCE, switchable at runtime from **CMS → Settings** (`/admin/cms/settings`, `cms.settings` ACL); both integrate with the FM file picker for images and file links. Jodit is Synapse's optional editor (`<x-synapse-rte>`) with the CMS's file-manager buttons added: enable it on the host with `php artisan synapps:reconfig` ("Enable the rich-text editor?"), otherwise Jodit fields show a notice above a plain textarea. Output is sanitized by the `cms_rte` purifier profile regardless of editor.
- **HTML Sanitization** — rich-text bodies/descriptions (articles, pages, categories) are cleaned with the strict `cms_rte` Purifier profile (`config/cms-purifier.php`, merged into `purifier.settings.cms_rte`): no event handlers, `<script>`/`<iframe>`; the only inline style allowed is `text-align` on blocks; `http`/`https`/`mailto` URIs only; embedded images and `<video>` must be local (relative or on the app's host — i.e. from the File Manager). Re-clean existing rows with `php artisan cms:sanitize-content [--dry-run]`
- **Sitemap** — `/sitemap.xml` served by `SitemapController`, includes all published pages, sections, categories, articles, and active normal albums with priority and change frequency metadata
- **RSS feeds** — RSS 2.0 feeds of public content at `/feed/articles.xml`, `/feed/articles/{section}.xml`, `/feed/downloads.xml` and `/feed/gallery.xml` (20 newest items, guest visibility only), localized under `/{locale}/feed/…` when multi-language is on; `<x-cms::feed-links />` adds the autodiscovery `<link>` tags
- **Activity Logging** — every backend create/update/delete/reorder is logged via `CmsActivityLogger`, recording only the diff of changed fields on updates
- **Frontend Livewire Components** — semantic BEM-style components for page, section, category, article, gallery index, album, and menu rendering; all resolved automatically via the fallback routing system
- **Widget Blade Components** — 8 embeddable `<x-cms::widget.*>` components for use anywhere in your layout, most supporting `template`/`card` overrides for full custom markup (see [Widget Components](#widget-components))
- **Granular ACL** — CRUD permissions per resource and sub-resource (`cms.article.section`, `cms.gallery.album`, etc.)
- **Content access control** — Public / Protected / Private (selected users or roles) frontend visibility with page and article inheritance, plus per-item managers lists (page subtrees, sections, albums, downloads) on top of the module ACL. Edited from each form's **Access** panel (`cms.access.update`); entries a dev adds are locked for everyone else

---

## Installation

This is a private package, not published on Packagist — add its VCS repository to your project's `composer.json` **before** requiring it, so Composer knows where to fetch it from:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "ssh://git@repo.4visionmedia.com:40288/vm-engine/synmod-cms.git"
        }
    ],
    "require": {
        "vm-engine/synmod-cms": "^2.0"
    }
}
```

Then install and discover:

```bash
composer update vm-engine/synmod-cms
php artisan synapps:discover
php artisan migrate
php artisan cms:setup   # wires CMS JS into resources/js/app.js + frontend.js (incl. synapse components), TinyMCE assets into vite.config.js, picks the default editor
npm install && npm run build
```

`cms:setup` is idempotent. `cms:patch-resources` (and its old name `mod-cms:patch-resources`) still works as a deprecated alias.

---

## Backend Routes

All admin routes are under `/admin/cms/` and require authentication. Access is controlled per route by the `can-access` middleware.

| Route | Name | ACL required |
|---|---|---|
| `GET /admin/cms/page` | `backend.cms.page.index` | `cms.page.read` |
| `GET /admin/cms/page/form/{id?}` | `backend.cms.page.form` | `cms.page.create\|update` |
| `GET /admin/cms/section` | `backend.cms.section.index` | `cms.article.section.read` |
| `GET /admin/cms/section/form/{id?}` | `backend.cms.section.form` | `cms.article.section.create\|update` |
| `GET /admin/cms/section/{sectionId}/category` | `backend.cms.category.index` | `cms.article.category.read` |
| `GET /admin/cms/section/{sectionId}/category/form` | `backend.cms.category.form` | `cms.article.category.create\|update` |
| `GET /admin/cms/section/{sectionId}/category/{id}/form` | `backend.cms.category.edit` | `cms.article.category.create\|update` |
| `GET /admin/cms/article` | `backend.cms.article.index` | `cms.article.read` |
| `GET /admin/cms/section/{sectionId}/article` | `backend.cms.section.article.index` | `cms.article.read` |
| `GET /admin/cms/section/{sectionId}/article/form` | `backend.cms.section.article.form` | `cms.article.create\|update` |
| `GET /admin/cms/section/{sectionId}/article/{id}/form` | `backend.cms.section.article.edit` | `cms.article.create\|update` |
| `GET /admin/cms/album` | `backend.cms.album.index` | `cms.gallery.album.read\|banner.read` |
| `GET /admin/cms/album/form/{id?}` | `backend.cms.album.form` | `cms.gallery.album.create\|update\|banner.create\|update` |
| `GET /admin/cms/album/{albumId}/images` | `backend.cms.album.images` | `cms.gallery.image.read\|update` |
| `GET /admin/cms/album/{albumId}/image/{id}/form` | `backend.cms.album.image.form` | `cms.gallery.image.update` |
| `GET /admin/cms/menu` | `backend.cms.menu.index` | `cms.menu.read` |
| `GET /admin/cms/menu/{menuId}/items` | `backend.cms.menu.items` | `cms.menu.update` |
| `GET /admin/cms/tag` | `backend.cms.tag.index` | `cms.tag.read` |
| `GET /admin/cms/analytics` | `backend.cms.analytics` | `cms.analytics.read` |
| `GET /admin/cms/settings` | `backend.cms.settings` | `cms.settings.read` |

> **Note:** Article create/edit forms are always section-scoped. The global article list (`cms.article.index`) is read-only for overview; all CRUD operations go through the section-scoped routes.

---

## Fallback Routing

All public content is served through the `synapps-fallback` catch-all. Slugs are registered automatically by Eloquent observers.

| Content | Public URL |
|---|---|
| Root page | `/{page_slug}` |
| Child page | `/{parent_slug}/{page_slug}` |
| Grandchild page | `/{grandparent_slug}/{parent_slug}/{page_slug}` |
| Section index | `/{section_slug}` |
| Category index | `/{section_slug}/{category_slug}` |
| Article (no category) | `/{section_slug}/{article_slug}` |
| Article (with category) | `/{section_slug}/{category_slug}/{article_slug}` |
| Normal album | `/gallery/{album_slug}` |
| Gallery index | `/{gallery_slug}` (registered when an `album_list` menu item is created) |

**Cascade behavior:** Renaming a page's slug updates `full_slug` for all descendants. Renaming a section's slug re-syncs all category and article routes under it. Renaming a category's slug removes old routes and re-registers articles under the new path. Banner-type albums have no public route.

---

## Frontend Components

Livewire full-page components for public content rendering. All are resolved automatically by the fallback router, which passes the relevant ID as a route parameter.

```blade
{{-- Static page --}}
<livewire:cms::frontend.page-viewer />

{{-- Section index (article listing) --}}
<livewire:cms::frontend.section-index />

{{-- Category index (filtered article listing) --}}
<livewire:cms::frontend.category-index />

{{-- Single article --}}
<livewire:cms::frontend.article-viewer />

{{-- Normal album with lightbox --}}
<livewire:cms::frontend.album-viewer />

{{-- Gallery index (all normal albums) --}}
<livewire:cms::frontend.gallery-index />

{{-- Download category (file listing) --}}
<livewire:cms::frontend.download-index />

{{-- Single download category's files --}}
<livewire:cms::frontend.download-viewer />
```

### Menu Component

Render a menu by its position string anywhere in your layout:

```blade
{{-- Livewire component --}}
<livewire:cms::frontend.menu :position="'header'" />

{{-- Or the Blade component wrapper --}}
<x-cms::menu :position="'header'" />
```

Positions are arbitrary strings set by the admin when creating a menu (e.g. `header`, `footer`, `sidebar`). The component renders up to 3 levels of nesting with BEM-style CSS classes by default.

Pass `template` (a Blade view name) to render your own markup instead — it receives `$items`, `$position`, plus any vars forwarded via `with`:

```blade
<x-cms::menu :position="'header'" template="theme::partials.main-nav" :with="['activeClass' => 'is-active']" />
```

---

## Widget Components

Eight embeddable Blade view components for use anywhere in layouts, other modules, or theme files. All data is fetched server-side on render.

Several widgets accept a common set of customization props — pass `template` to fully replace the default markup with your own Blade view (receives the widget's resolved data plus `with`), `card` to override just the per-item markup within the default layout, `wrapperClass`/`gridClass` to add classes to the built-in containers, and `bare` to skip the built-in wrapper `<div>`s entirely. Props not listed below are unsupported on that widget.

### `<x-cms::widget.page>`

Renders a single page's title, cover image, and body.

```blade
<x-cms::widget.page
    slug="about"
    :showTitle="true"
    :showCover="true"
    template="theme::widgets.page"   {{-- receives $page + $with; falls back to default markup if empty --}}
    wrapperClass="my-page-widget"
    :bare="false"
    :with="['extra' => 'value']"
/>
```

### `<x-cms::widget.page-children>`

Renders a page and its published child pages in a grid or list.

```blade
<x-cms::widget.page-children
    slug="services"
    layout="grid"      {{-- "grid" | "list" --}}
    :limit="12"
    :showTitle="true"
    :showCover="true"
    :showExcerpt="true"
    :recursive="false"
/>
```

### `<x-cms::widget.articles>`

Renders all published articles with sorting and layout options.

```blade
<x-cms::widget.articles
    sort="latest"          {{-- "latest" | "oldest" | "featured" | "az" | "za" --}}
    :limit="6"
    layout="grid"          {{-- "grid" | "list" --}}
    :showSection="true"
    :showExcerpt="true"
    :showCover="true"
    :showDate="true"
    :showFeaturedBadge="true"
/>
```

### `<x-cms::widget.section-articles>`

Renders published articles filtered by section slug.

```blade
<x-cms::widget.section-articles
    section="news"
    :limit="6"
    sort="latest"
    layout="grid"
    :showCategory="true"
    :showExcerpt="true"
    :showCover="true"
    :showDate="true"
    card="theme::widgets.article-card"      {{-- per-article override; receives $article + $section --}}
    template="theme::widgets.news-section"  {{-- full override; receives $section, $articles, $card + $with --}}
    wrapperClass="my-section"
    gridClass="my-grid"
    :bare="false"
    :with="[]"
/>
```

### `<x-cms::widget.category-articles>`

Renders published articles filtered by section + category slug.

```blade
<x-cms::widget.category-articles
    section="news"
    category="tech"
    :limit="6"
    sort="latest"
    layout="grid"
    :showExcerpt="true"
    :showCover="true"
    :showDate="true"
/>
```

### `<x-cms::widget.tag-articles>`

Renders published articles filtered by one or more tag slugs.

```blade
<x-cms::widget.tag-articles
    tags="php,laravel"    {{-- comma-separated tag slugs --}}
    match="any"           {{-- "any" | "all" --}}
    :limit="6"
    sort="latest"
    layout="grid"
    :showSection="true"
    :showExcerpt="true"
    :showCover="true"
    :showDate="true"
/>
```

### `<x-cms::widget.banner-slider>`

Renders a banner-type album as an Alpine.js carousel.

```blade
<x-cms::widget.banner-slider
    album="hero"           {{-- album slug (must be type=banner) --}}
    :autoplay="true"
    :interval="5000"       {{-- milliseconds --}}
    :showCaption="true"
    :showControls="true"
    :showIndicators="true"
    height="480px"
    template="theme::widgets.hero-slider"  {{-- full override; receives $slides, the album record, $images collection + $with --}}
    :with="[]"
/>
```

### `<x-cms::widget.gallery>`

Renders an active album's images in a grid (not restricted to `banner`-type albums — use `banner-slider` for the carousel presentation).

```blade
<x-cms::widget.gallery
    album="team-photos"
    :limit="0"              {{-- 0 = no limit --}}
    :showTitle="true"
    :showCaption="true"
    card="theme::widgets.gallery-card"   {{-- per-image override; receives $image + $album + $with --}}
    template="theme::widgets.gallery"    {{-- full override; receives $images, $album + $with --}}
    wrapperClass="my-gallery"
    gridClass="my-grid"
    :bare="false"
    :with="[]"
/>
```

---

## Multi-language Content & Search

When `synapps.apps.multi_language` is on, every page, article, section, category, album, album image, download, download file and menu item can be translated into each enabled language. Slugs are shared, and the visitor's language comes from the `/{locale}/` URL prefix that `synapps-fallback` handles.

- **Storage:** the base columns hold the **primary locale** (`app.locale`, pinned at boot). Other locales live in `cms_translations`. An empty translation field falls back to the primary text, field by field.
- **Editing:** every CMS form shows language tabs, and one Save writes all languages. Menu items get one label input per language.
- **Frontend:** viewers, widgets and menus call `$model->translate('title')`. **Custom templates** (`custom_view`, widget `template`/`card`) must do the same to show translations.

### Search

| Piece | Usage |
|---|---|
| Search page | `/{locale}/search?q=…` (slug from `search.slug`), registered by `php artisan cms:search-reindex` (also run by `cms:setup`) |
| Search box | `<livewire:cms::frontend.search-box />` anywhere in your layout. A plain GET form (Enter works without JS), with live top-5 suggestions, popular searches when empty, keyboard navigation and an ARIA combobox. Needs `php artisan cms:setup` (imports `cms-frontend.js` into `resources/js/frontend.js`) and `npm run build` |
| Backend | **CMS → Search Terms**: hide, unhide or delete popular terms (ACL `cms.search_term`) |
| Index | `cms_searchables`, kept in sync automatically. Rebuild with `php artisan cms:search-reindex {--type=page --type=article …}` |
| PHP API | `app(CmsSearchService::class)->search($q, $types, $perPage)`, `->suggest($q)` |
| Backend palette | Separate from the public index: every page is in the Synapse Ctrl/⌘+K palette (`content:cms.page`, opens the page form, ACL `cms.page.update`), kept in sync by `CmsPageObserver`. Rebuild with `php artisan cms:palette-reindex` (also run by `cms:setup`). `module.json` `searchables` add New page / New section / New album. Needs `synapps:searchable-setup` |

Results link to album items and download files with `#item-{id}` / `#file-{id}`: the album lightbox opens the linked image, and download rows can be highlighted with CSS, e.g. `.cms-download-item:target { background: #fff7d6; }`.

### Links & language switcher

Every CMS link keeps the visitor's language. In custom templates, never hand-build `href="/{{ $x->slug }}"`; use `\VmEngine\Cms\Support\CmsUrl::to($model)` (root-relative, locale-prefixed: `/id/news/ai`) or `CmsUrl::to('some/path')`. `CmsUrl::absolute($path, $locale)` gives full URLs. With multi-language on, `/` redirects to `/{app.locale}`, and `/{locale}` serves your home route in that language (synapps-fallback). The sitemap lists every language version with `hreflang` alternates.

Add to your frontend layout:

```blade
<head>
    …
    <x-cms::hreflang />   {{-- <link rel="alternate" hreflang="…"> + x-default --}}
</head>
<header>
    <x-cms::language-switcher />
</header>
```

| Prop | Values | Default |
|---|---|---|
| `variant` | `dropdown` (native `<details>`, no JS) · `list` | `dropdown` |
| `display` | `name` · `code` · `both` · `icon` · `icon-name` · `icon-code` | `name` |
| `trigger` | `current` (current language on the dropdown button) · `globe` | `current` |
| `globe-icon` | CSS class for the globe trigger (your theme must load the icon font) | `ph ph-globe` |
| `hide-current` | omit the active language from the list | `false` |
| `wrapper-class` | extra classes on the root (any other attribute, e.g. `id`, `data-*`, is merged too) | — |
| `template` + `:with` | your own Blade view; receives `$languages` (`code`, `name`, `url`, `absolute`, `active`, `icon`), `$current`, `$display` + `$with` | — |

Markup is unstyled BEM (`cms-language-switcher`, `__current`, `__list`, `__item`, `__item--active`, `__link`, `__icon`, `__name`, `__code`). For a fully custom switcher, call `CmsUrl::alternates()` yourself. The switcher and hreflang render nothing when multi-language is off, only one language is enabled, or the page has no locale prefix.

**Names:** each language's own name from PHP `intl` ("English", "Indonesia", "日本語"). Override in `language_names`, e.g. `{"id": "Bahasa Indonesia"}`.

**Icons:** `language_icons` entries may be an **image** (`/storage/fm/public/flags/id.svg`, any `http(s)://` URL, `data:image/…`; File Manager uploads work), a **CSS class** (`fi fi-id` for flag-icons, or any icon font), or an **emoji**. Without an entry, a flag emoji is derived from the locale's region (`pt_BR` → 🇧🇷) or a built-in map (`en` → 🇬🇧, `id` → 🇮🇩, …). Windows doesn't render flag emoji, so use images or a flag CSS class for production.

### Settings (`module.json` → `config('synapps.modules.cms.*')`)

Override them per project in `synapps/config/modules.json`, e.g. `{"cms": {"search": {"engine": "scout"}}}`, then run `php artisan synapps:config`.

| Key | Default | Meaning |
|---|---|---|
| `primary_locale` | `null` (= `app.locale`) | Language of the base columns. **Never change it once content exists** |
| `language_names` | `{}` | Per-locale display-name overrides for the switcher |
| `language_icons` | `{}` | Per-locale icon: image path/URL, CSS class or emoji |
| `search.engine` | `database` | `database` (LIKE) or `scout` (requires `laravel/scout`) |
| `search.scout_content_limit` | `5000` | Body characters sent to the Scout engine |
| `search.slug` | `search` | Search page path; run `cms:search-reindex` after changing it |
| `search.per_page` | `10` | Results per page |
| `search.rate_limit` / `search.suggest_rate_limit` | `30` / `120` | Searches / suggestions per minute per IP |
| `search.popular_min_hits` / `search.popular_limit` | `3` / `8` | Popular-term threshold and list size |

Upgrade an existing install: `php artisan migrate && php artisan cms:search-reindex && npm run build`.

## Custom Variables

All content types (pages, sections, categories, articles, albums, album images) support a configurable set of key/value custom variables stored in the `config` JSON column.

**Schema managers** (users with the relevant `update` permission) can define the schema: variable key, display label, and whether non-manager users may edit the value.

**Non-manager users** can edit variables marked as editable and, if the schema manager enables "Allow users to add", may also add new free-form key/value pairs.

Custom variables are accessible in frontend components and widgets via `$model->config['vars'] ?? []`.

---

## Custom Fields

Typed fields defined by a **dev** (`is_dev()`) on a container and filled in by any editor. Unlike [Custom Variables](#custom-variables), editors cannot add or change the definitions.

| Defined on | Values on |
|---|---|
| Root page | the root page and all its descendants |
| Section | the section and its articles |
| Download category | the category and its files |
| Album (normal or banner) | the album and its images |

- **Types:** text, textarea, number, email, url, date, datetime, select, radio, checkbox, checkboxes.
- **Definition:** each field has a name, label, type, options, default, required, help text and searchable / filterable / sortable flags.
- **Translatable labels:** a label, help text or option label starting with `__` is a translation key (`__cms::labels.price` → `__('cms::labels.price')`).
- **Per-locale values:** a missing locale falls back to the primary locale, then to the field default. `required` applies to the primary locale only.
- **Type lock:** a field's type can't change once it has values. Renaming a field keeps its values. Deleting a field deletes its values.

Reading values:

```php
$article->field('price');          // current locale
$article->field('price', 'id');
$article->fields();                // ['price' => 12.5, 'tags' => ['a', 'b'], ...]
```

Querying (pass `int|float` for number fields and a `DateTimeInterface` for date fields; `like` matches text):

```php
CmsArticle::whereField('price', '<', 100)->orderByField('event_date', 'desc')->get();
CmsArticle::whereField('colour', 'red')->get();
CmsArticle::whereFieldIn('tags', ['a', 'b'])->get();
```

**Search:** searchable values are added to the public search index. For option fields the option labels are indexed.

**Listing filters:** the section, category, album, download and page-children listings accept these query parameters for filterable and sortable fields:
- `?f[name]=value`
- `?f[name][min]=..&f[name][max]=..` (number and date fields)
- `?f[name][]=a` (checkboxes)
- `?sort=name&dir=asc|desc`

The default views render `<x-cms::field-filters :filter="$fieldFilter" />`. Custom views receive `$fieldFilter` too and can include it, or build their own form.

The filter bar is Livewire-bound. Its inputs `wire:model` the listing's URL-synced `$f` / `$sort` / `$dir` (the `WithFieldFilters` trait), Apply calls `applyFieldFilters()` and Reset calls `resetFieldFilters()`. Date and datetime fields use `<x-synapse-datepicker>` in both the filters and the backend value inputs, so the frontend JS bundle must register the synapse components. `php artisan cms:setup` adds this to `resources/js/frontend.js`. It skips the step if `frontend.js` or `synapps-frontend.js` already imports `synapse-components.js`:

```js
import 'flatpickr/dist/flatpickr.min.css';
import '../../synapps/resources/js/synapse-components.js';
```

The default theme styles the bar in `themes/default/css/cms.css` (`.cms-field-filters*`).

## Media Galleries

Small inline image galleries placed in the middle of rich-text content.

- **Enable (dev only):** turn on *Enable media galleries* on a **root page** (covers that page and every child page), a **section** (its articles) or a **download** (its files). Stored as `config['media_gallery']`; non-dev saves never change it.
- **Manage:** the page, article and download-file forms show a **Media Galleries** panel once the item is saved. Each gallery has a title, a key (unique per item), a layout (`slider` or `grid`) and FM images with per-locale captions. Copy its tag, e.g. `[gallery:intro-shots]`, and paste it into the body/description. A tag alone in a paragraph replaces the whole `<p>`.
- **Render:** templates output `{!! $page->renderWithMediaGalleries('body') !!}` (articles: `body`, download files: `description`). Unknown keys, empty galleries and disabled features render nothing. Use `CmsMediaGalleryRenderer::strip($html)` wherever content is shown as plain text.
- **Cleanup:** deleting a page, article or download file deletes its galleries. Deleting a section or download removes its articles/files by FK cascade, which fires no events and leaves their galleries orphaned. Run `php artisan cms:prune-media-galleries` (`--dry-run` lists them first) to delete those, with their images and caption translations.
- **Theme:** override `cms::components.frontend.partials._media-gallery-slider` / `_media-gallery-grid` (vendor view override). The slider reuses the banner slider's `cmsBannerSlider` Alpine component and `.cms-banner-slider*` styles; extra styles are `.cms-media-gallery*`.

## Page Display Options

Per-page settings stored in `cms_pages.config`:

| Setting | Key | Default | Notes |
|---|---|---|---|
| Show Excerpt on Detail Page | `show_excerpt` | off | Renders the translated excerpt below the title (`.cms-page__excerpt`). |
| Show Child Pages as Sub-Menu | `show_submenu` | off | The page shows a menu of its children; its children show the same menu (siblings), current page marked `aria-current="page"`. Sidebar on desktop, stacked on mobile (`.cms-submenu*`). |
| Child Pages Display | `children_display` | `none` | `none`, `cards` (card grid + field filters) or `one_page` (each published child's title and body rendered below as `<section id="cms-child-{slug}">`; sub-menu links become anchors). Child URLs keep working. Legacy `show_children: true` reads as `cards`. |
| Allow non-dev users to add child pages | `allow_child_create` | on | **Dev only.** When off, non-devs get no "add child" button, a 403 on the form, and the page is left out of the parent dropdown. Existing children stay editable. Read with `$page->acceptsChildrenFromCurrentUser()`. |

### Sorting pages

Sibling pages are reordered by dragging the grip handle on their row in the backend page list. A page can only be dropped among its siblings, never under another parent, so URLs never change. Handles appear only for groups with 2 or more pages that you can update in full; root pages are dev-only and locked while a status filter is active, and search results have no handles. Backed by `CmsPageService::siblingsOf()` / `reorderChildren()` (logged as `cms.page.{id}.reorder_children`).

## Article Display Options

Set on the **section** (`cms_sections.config`), overridable per **article** (`cms_articles.config`):

| Setting | Section key (default) | Article override | Applies to |
|---|---|---|---|
| Show Timestamp | `show_date` (on) | `show_date`: `null` follow section / `true` / `false` | Article detail, section and category lists, article widgets (widgets also need their own `showDate`) |
| Show Excerpt on Detail Page | `show_excerpt` (on) | `show_excerpt`: same | Article detail page |
| Items per page | `items_per_page` (12) | — | Section and category lists |

Resolve with `$article->showsDate()` / `$article->showsExcerpt()` (article override, else section, else on) — custom templates should call these instead of reading `config`.

## Custom View & Layout Overrides

Every content type can override its rendered Blade view and/or layout on a per-record basis via `custom_view` / `custom_layout` columns, editable from a "Custom Rendering" card on each admin form. Sections and categories additionally expose a scoped override for the content they contain (`custom_category_view`, `custom_article_view`).

### Resolution cascade

Each model exposes `resolveCustomView()` / `resolveCustomLayout()`, called by the frontend viewers instead of reading the raw column directly — this is what implements the fallback chain:

| Model | `resolveCustomView()` falls back through | `resolveCustomLayout()` falls back through |
|---|---|---|
| `CmsPage` | own → parent page (recursive up the tree) | own → parent page (recursive) |
| `CmsSection` | own only | own only |
| `CmsCategory` | own → section's `custom_category_view` | own → section's `custom_layout` |
| `CmsArticle` | own → category's `custom_article_view` → section's `custom_article_view` | own → category's `custom_layout` → section's `custom_layout` |
| `CmsAlbum`, `CmsDownload`, `CmsMenuItem` | own only (no cascade) | own only (no cascade) |

A resolved value is only used if the view actually exists (`view()->exists()`) — an invalid/typo'd view name silently falls through to the next level in the cascade, then to the theme's default frontend layout.

### `FrontendViewResolver`

Every bundled frontend viewer MFC (page, section, category, article, album, gallery, download category, download file) calls this helper instead of duplicating the `view()->exists()` / theme-fallback logic:

```php
use VmEngine\Cms\Support\FrontendViewResolver;

$view = FrontendViewResolver::view($page->resolveCustomView(), 'cms::frontend.page-viewer.default');
$layout = FrontendViewResolver::layout($page->resolveCustomLayout()); // falls back to active theme's frontend layout, then synapps::components.layouts.layout
```

Use the same pattern if you add your own frontend viewer for a CMS content type.

---

## Google Analytics & Search Console

GA4 tracking, Search Console verification, an optional cookie-consent banner and a backend **Analytics** dashboard. No extra Composer packages: Google's REST APIs are called with Laravel `Http`, authenticated as a service account (self-signed RS256 JWT via `openssl`).

### 1. Tracking + verification (CMS Settings → Tracking)

- **GA4 Measurement ID** (`G-…`): GA4 → Admin → Data streams → Web. `<x-cms::analytics-head />` renders `gtag.js` **only in `production` and never for users with backend access**, and adds Google's hosts to the CSP on those pages (no `apps.json` change needed).
- **Search Console verification token**: add a URL-prefix property → *HTML tag* method → paste only the `content` value. (Domain properties verify via DNS instead.) The meta tag renders in every environment.
- Submit `/sitemap.xml` in Search Console; `cms:setup` puts it in `robots.txt` (see [Sitemap](#sitemap)).

### 2. Cookie consent (Consent Mode v2)

**Require cookie consent** (default on): GA starts with `analytics_storage: denied` (cookieless pings) and `<x-cms::cookie-consent />` asks the visitor. Accept/Reject updates consent and stores `cms_consent=granted|denied` for 180 days; `<x-cms::cookie-settings-link />` reopens the banner. Ads consent is always denied. Turn it off only for sites without EU/UK visitors (GA then starts granted, no banner).

Theme wiring (the default theme already does this): `<x-cms::analytics-head />` in `<head>`, `<x-cms::cookie-consent />` before the scripts, `<x-cms::cookie-settings-link />` in the footer. Markup is unstyled BEM (`.cms-consent*`, `.cms-consent-link`); the default theme styles it in `themes/default/css/cms.css`.

### 3. Reporting API (CMS Settings → Reporting API)

1. Google Cloud console → create/select a project → enable **Google Analytics Data API** and **Google Search Console API**.
2. IAM → Service accounts → create one → Keys → *Add key* → JSON.
3. GA4 → Admin → Property access management → add the service-account email as **Viewer**.
4. Search Console → Settings → Users and permissions → add the email (Restricted is enough).
5. In CMS Settings fill **GA4 Property ID** (numeric, Admin → Property details — not the `G-` ID), **Search Console property** (`https://example.com/` or `sc-domain:example.com`) and paste the **JSON key**. The key is stored encrypted (`dbconf('cms.analytics_key')`, app key) and never shown again; other values live in `dbconf('cms.analytics')`.

### 4. Dashboard

`/admin/cms/analytics` (`cms.analytics.read`): range 7/28/90 days, GA users/sessions/views/engagement rate + daily views trend + top pages, Search Console clicks/impressions/CTR/position + daily clicks trend + top queries/pages. Each panel loads lazily and fails on its own (a Google error is shown in that panel). Results are cached for 1 hour; **Refresh** (`cms.analytics.update`) and saving settings clear the cache. Search Console data lags ~3 days, so its ranges end 3 days ago.

### 5. Dummy data (development)

To review the dashboard without a Google account, turn on dummy mode in `synapps/config/modules.json` and regenerate config:

```json
{ "cms": { "analytics": { "fake": true } } }
```

```bash
php artisan synapps:config
```

Then fill any **GA4 Property ID** and **Search Console property** in CMS Settings (e.g. `123` and `sc-domain:example.com`); no JSON key is needed. `FakeGoogleApiClient` replaces the real client and returns Google-shaped sample data (stable per date, weekday/weekend rhythm), so reporters, caching and panels run unchanged. The dashboard shows a warning while it is on. The flag is **ignored in production** (and under tests, so a local flag can't change the suite).

Code: `AnalyticsSettings`, `AnalyticsCache`, `TrendLine` (`src/Support`), `GoogleApiClient`, `Ga4Reporter`, `SearchConsoleReporter` (`src/Services/Analytics`), `AnalyticsRange` enum.

---

## Models

| Model | Table | Key fields |
|---|---|---|
| `CmsPage` | `cms_pages` | `parent_id`, `slug`, `full_slug`, `status`, `sort_order`, `config` (JSON), `custom_view`, `custom_layout` |
| `CmsSection` | `cms_sections` | `slug`, `status`, `config` (JSON: display options, items_per_page), `custom_view`, `custom_layout`, `custom_category_view`, `custom_article_view` |
| `CmsCategory` | `cms_categories` | `section_id`, `slug`, `status`, `inherit_config`, `config` (JSON, overrides section when `inherit_config=false`), `custom_view`, `custom_layout`, `custom_article_view` |
| `CmsArticle` | `cms_articles` | `section_id`, `category_id` (nullable), `slug`, `status`, `published_at`, `expires_at`, `is_featured`, `read_time`, `config` (JSON), `custom_view`, `custom_layout` |
| `CmsTag` | `cms_tags` | `slug` (unique) |
| `CmsAlbum` | `cms_albums` | `slug`, `type` (`normal`\|`banner`), `status` (`active`\|`inactive`), `cover_image`, `config` (JSON), `custom_view`, `custom_layout` |
| `CmsAlbumImage` | `cms_album_images` | `album_id`, `file_id` (nullable FK to `fm_files`), `title`, `caption`, `sort_order`, `config` (JSON) |
| `CmsMenu` | `cms_menus` | `position`, `is_active` |
| `CmsMenuItem` | `cms_menu_items` | `menu_id`, `parent_id`, `target_type`, `target_id`, `target_url`, `sort_order`, `is_active`, `custom_view`, `custom_layout` |
| `CmsDownload` | `cms_downloads` | `slug`, `status` (`active`\|`inactive`), `config` (JSON), `custom_view`, `custom_layout` |
| `CmsDownloadFile` | `cms_download_files` | `download_id`, `file_id` (nullable FK to `fm_files`), `title`, `sort_order` |

All models with delete operations use `WithDeleteToken` for secure, CSRF-safe deletion.

### Notable model methods

| Method | Description |
|---|---|
| `CmsPage::computeFullSlug()` | Builds the full path slug by walking up the parent chain |
| `CmsPage::getDepth()` | Returns nesting depth (0 = root, max 2 = grandchild) |
| `CmsCategory::effectiveConfig()` | Returns own config or falls back to parent section config when `inherit_config = true` |
| `CmsArticle::computeReadTime(string $body)` | Static; estimates reading minutes at 200 wpm, minimum 1 |
| `CmsMenuItem::resolveUrl()` | Builds the public URL for any target type (see below) |

### Menu item target types

`CmsMenuItem::resolveUrl()` resolves all 8 target types:

| `target_type` | Resolved URL |
|---|---|
| `url` | `target_url` value (custom link) |
| `page` | `/{page->full_slug}` |
| `section` | `/{section->slug}` |
| `category` | `/{section->slug}/{category->slug}` |
| `article` | `/{section->slug}/{article->slug}` or `/{section->slug}/{category->slug}/{article->slug}` |
| `album` | `/gallery/{album->slug}` |
| `album_image` | `/gallery/{album->slug}#image-{image->id}` |
| `album_list` | `/{target_url ?: 'gallery'}` (gallery index; slug configurable) |

---

## Enums

| Enum | Values | Used on |
|---|---|---|
| `ArticleStatus` | `Draft`, `Published`, `Scheduled` | `CmsArticle::status` |
| `AlbumType` | `Normal`, `Banner` | `CmsAlbum::type` |
| `DownloadAccess` | `Public`, `Private` (login required) | `CmsDownload::access` |

---

## Observers & Automatic Sync

Every content model has a dedicated Eloquent observer that syncs fallback routes automatically. No manual intervention is required.

| Observer | Events handled |
|---|---|
| `CmsPageObserver` | `created/updated/deleted` — syncs page fallback; cascades `full_slug` to descendants on slug change |
| `CmsSectionObserver` | `created/updated/deleted` — syncs section fallback; cascades category and article routes on slug change |
| `CmsCategoryObserver` | `created/updated/deleted` — syncs category fallback; removes old routes and re-syncs article routes on slug change |
| `CmsArticleObserver` | `created/updated/deleted` — syncs article fallback based on section+category path |
| `CmsAlbumObserver` | `created/updated/deleted` — syncs album fallback for `normal` type only; `inactive` or `banner` albums have no route |
| `CmsAlbumImageObserver` | `created` — auto-sets album `cover_image` from first image if none exists; `deleted` — updates cover to next available image |
| `CmsMenuItemObserver` | `created/updated/deleted` — registers/removes gallery-index fallback for `album_list` items; deduplicates when multiple items share the same gallery slug |

---

## Services

Since v1.1.7, every read query and write operation outside the model itself flows through one of these domain services — backend lists/forms, frontend viewers, widgets, and form objects all call service methods instead of static `Cms*::` queries. If you're extending the module, add new queries here rather than querying models directly from Livewire/forms.

### `CmsPageService`, `CmsArticleService`, `CmsGalleryService`, `CmsDownloadService`

One service per content family, each covering finders (`find*ById`, `find*BySlug`, `findPublished*`), backend pagination/filtering (`paginate*`, `listForBackend`), `create*`/`update*`/`delete*ByToken` writes, and adv-select `search*Options`/`load*Options` helpers used by the menu-item picker.

```php
use VmEngine\Cms\Services\CmsArticleService;

$service = app(CmsArticleService::class);

$article = $service->findPublishedArticleOrFailById($id);
$articles = $service->getPublishedArticlesInSectionForWidget(sectionId: $section->id, sort: 'latest', limit: 6);
$service->createArticle($data, $tagInputs, auth()->id());   // wrapped in DB::transaction
```

`CmsArticleService` additionally owns `CmsSection`, `CmsCategory`, and `CmsTag` queries/writes (there's no separate service for those). `CmsGalleryService` owns both `CmsAlbum` and `CmsAlbumImage` — including `reorderImagesInAlbum()` and the auto-cover logic (`applyAutoCoverOnImageCreate`/`recomputeCoverOnImageDelete`) that used to live in `CmsAlbumImageObserver`. `CmsDownloadService` owns both `CmsDownload` and `CmsDownloadFile`, including `reorderFilesInDownload()`. `CmsPageService::reorderChildren($parentId, $ids)` reorders sibling pages (`null` = root pages) and ignores ids under another parent.

Every service constructor injects `CmsActivityLogger` (see below) and calls it internally from every write method — you don't need to log activity yourself when using these services.

### `CmsActivityLogger`

Lightweight audit helper wrapping `SynAuth::logActivity()`. Wired into every backend mutation path (form objects, list-component deletes, in-page managers).

```php
use VmEngine\Cms\Services\CmsActivityLogger;

$logger = app(CmsActivityLogger::class);

$logger->logCreate('page', $page);
$logger->logUpdate('page', $page, $oldData);   // records only the diff of changed fields (old → new), ignoring timestamps
$logger->logDelete('page', $page);
$logger->logReorder('download', 'files', $download, $orderedFileIds); // action key: cms.download.{id}.reorder_files
```

Action keys follow `cms.{entity}.{id}.{action}` with feature `{entity}.{id}` and module `cms`, matching the logging pattern used by other Synapse modules.

### `CmsFallbackService`

Central service for all fallback route registration. Called exclusively by observers; do not call directly from forms.

```php
use VmEngine\Cms\Services\CmsFallbackService;

$service = app(CmsFallbackService::class);

$service->syncPage(CmsPage $page);
$service->forgetPage(CmsPage $page);
$service->syncSection(CmsSection $section);
$service->forgetSection(CmsSection $section);
$service->syncCategory(CmsCategory $category);
$service->forgetCategory(CmsCategory $category);
$service->forgetCategoryByOldSlug(string $sectionSlug, string $oldCategorySlug, CmsCategory $category);
$service->syncArticle(CmsArticle $article);
$service->forgetArticle(CmsArticle $article);
$service->syncAlbum(CmsAlbum $album);
$service->forgetAlbum(CmsAlbum $album);
$service->syncGalleryIndex(string $slug);
$service->forgetGalleryIndex(string $slug);
$service->syncPageDescendants(CmsPage $page);  // recursive
$service->syncSectionArticles(CmsSection $section); // cascades categories + articles
```

### `CmsMenuService`

Builds nested menu trees for frontend rendering.

```php
use VmEngine\Cms\Services\CmsMenuService;

$service = app(CmsMenuService::class);

// Build tree from position string (returns root items with nested children)
$items = $service->buildTree('header');

// Build tree from a CmsMenu instance
$items = $service->buildTreeFromMenu($menu);

// Get the active menu for a position
$menu = $service->getMenuByPosition('header'); // returns null if none active
```

### `CmsSitemapService`

Generates the sitemap XML. Called by `SitemapController`.

```php
use VmEngine\Cms\Services\CmsSitemapService;

$xml = app(CmsSitemapService::class)->generate();
```

Content included and priority values:

| Content | `changefreq` | `priority` |
|---|---|---|
| Root pages | `weekly` | `0.8` |
| Child/grandchild pages | `weekly` | `0.6` |
| Sections | `weekly` | `0.7` |
| Categories (published section) | `weekly` | `0.6` |
| Featured articles | `monthly` | `0.9` |
| Regular articles | `monthly` | `0.7` |
| Active normal albums | `monthly` | `0.5` |

Banner albums and inactive content are excluded.

---

## ACL Permissions

Defined in `module.json` and registered via Synapse's ACL system.

| Permission | Actions | Controls |
|---|---|---|
| `cms.page` | create, read, update, delete | Static pages |
| `cms.page.publish` | read, update | Publish/unpublish pages |
| `cms.article` | create, read, update, delete | Articles |
| `cms.article.publish` | read, update | Publish/unpublish articles |
| `cms.article.section` | create, read, update, delete | Sections |
| `cms.article.category` | create, read, update, delete | Categories |
| `cms.gallery.album` | create, read, update, delete | Normal albums |
| `cms.gallery.banner` | create, read, update, delete | Banner albums |
| `cms.gallery.image` | create, read, update, delete | Album images |
| `cms.menu` | create, read, update, delete | Menus and menu items |
| `cms.tag` | create, read, update, delete | Tags |
| `cms.settings` | read, update | CMS-wide settings (incl. Google Analytics / Search Console) |
| `cms.analytics` | read, update | Analytics dashboard (read = view, update = Refresh) |
| `cms.access` | update | Edit an item's Access panel (visibility, viewers, managers); only on items the user can manage |

`cms.gallery` itself has no CRUD actions — permissions are always checked at the sub-permission level (`album`, `banner`, `image`).

---

## Sitemap

The sitemap is served at `/sitemap.xml` by `SitemapController` registered in `routes/web.override.php` (no module URL prefix). The response sets `Content-Type: application/xml` and `Cache-Control: public, max-age=3600`.

`cms:setup` (or its deprecated alias `cms:patch-resources`) adds `Sitemap: {APP_URL}/sitemap.xml` to the host's static `public/robots.txt` (created if missing; an existing `Sitemap:` line is left alone).

---

## RSS Feeds

| Feed | URL | Items |
|---|---|---|
| Articles | `/feed/articles.xml` | published articles of published sections, newest `published_at` first |
| Section | `/feed/articles/{section-slug}.xml` | the same, one section (404 unless the section is published and public) |
| Downloads | `/feed/downloads.xml` | active download categories, newest first |
| Gallery | `/feed/gallery.xml` | active normal albums (no banners), newest first |

Only content a guest can see is listed (`CmsAccessService::applyVisibleScope($query, null)`), capped at 20 items, served as `application/rss+xml` with `Cache-Control: public, max-age=3600`. Each item carries title, link/guid, description (`translatedMetaDescription()`), `pubDate`, section/category as `<category>` and the cover image as `<enclosure>`.

Unprefixed feeds use the CMS primary locale. When multi-language is on, `/{locale}/feed/…` serves translated titles, descriptions and links — those routes are registered only then, so run `php artisan route:clear` after toggling multi-language if routes are cached.

Autodiscovery: `<x-cms::feed-links />` in `<head>` links the three site feeds, plus the current section's feed on section, category and article pages. `cms:setup` / `cms:patch-resources` appends it to the active theme's `layouts/partials/cms-head.blade.php` (warns if the partial is missing). Feed logic lives in `CmsFeedService`, routes in `routes/web.override.php`.

---

## Testing

```bash
# All CMS module tests
./vendor/bin/pest packages/synmod-cms/tests/

# Filter by resource
./vendor/bin/pest packages/synmod-cms/tests/ --filter="PageTest"
./vendor/bin/pest packages/synmod-cms/tests/ --filter="ArticleTest"
./vendor/bin/pest packages/synmod-cms/tests/ --filter="CategoryTest"
./vendor/bin/pest packages/synmod-cms/tests/ --filter="AlbumImageTest"
./vendor/bin/pest packages/synmod-cms/tests/ --filter="SitemapTest"
./vendor/bin/pest packages/synmod-cms/tests/ --filter="MenuServiceTest"
```

Test coverage includes: model factories, page rendering, CRUD via Livewire, fallback route sync (create/update/delete/slug-rename cascade), observer behaviour (auto-cover image), all 8 `CmsMenuItem::resolveUrl()` target types, sitemap XML output, menu tree building, and ACL enforcement for all resources.

---

## Code Quality

Run from the main Laravel project root:

```bash
# Static analysis (run first)
vendor/bin/phpstan analyse -c packages/synmod-cms/phpstan.neon --memory-limit=1G   # level 7, no ignores

# Code style
vendor/bin/pint packages/synmod-cms/
```
