# synmod-cms

Content Management System module for [Laravel Synapse](https://github.com/vm-engine/synapse). Provides static pages, dynamic articles, a gallery system, frontend menu management, and embeddable widget components — all with automatic slug-based public routing via `synapps-fallback`.

**Package:** `vm-engine/synmod-cms`  
**Module ID:** `cms`  
**Requires:** PHP 8.2+, Laravel 12, Livewire 4, `vm-engine/synapse`, `vm-engine/synapps-auth`, `vm-engine/synapps-fallback`, `vm-engine/synmod-fm`

---

## Features

- **Static Pages** — up to 3 levels deep (`/parent/child/grandchild`), with TinyMCE body editor, SEO fields, cover image, and per-page display config (show title, breadcrumb, children layout)
- **Sections / Categories / Articles** — hierarchical dynamic content; categories are optional; articles support tags, scheduling (`published_at`, `expires_at`), featured flag, and automatic read-time computation
- **Gallery** — albums of type `normal` (public, routed at `/gallery/{slug}`) or `banner` (display-only, no public route); drag-and-drop image ordering; auto-sets album cover from first image uploaded; FM file picker integration
- **Menus** — user-defined positions (`header`, `footer`, `sidebar`, or any string), infinitely nested items linking to pages, sections, categories, articles, albums, or custom URLs, drag-and-drop reordering
- **Tags** — shared across articles with unique slug, managed via inline modal
- **Downloads** — categorised file downloads with `public`/`private` (login-required) access via the `DownloadAccess` enum, drag-and-drop file ordering, FM file picker integration
- **Custom Variables** — configurable key/value pairs on all content types (pages, sections, categories, articles, albums, album images), accessible in frontend components and templates
- **Custom View & Layout Overrides** — every content type can override its rendered Blade view and/or layout per-record, with a cascading fallback (article → category → section, page → parent page); see [Custom View & Layout Overrides](#custom-view--layout-overrides)
- **Automatic Fallback Routing** — all published content registers its public slug in `fallback_routes` on save and removes it on delete/unpublish; slug renames cascade to children automatically; no route cache clearing needed
- **TinyMCE** — self-hosted rich text editor bundled via Vite, integrated with FM file picker for inline image insertion and video embeds
- **Sitemap** — `/sitemap.xml` served by `SitemapController`, includes all published pages, sections, categories, articles, and active normal albums with priority and change frequency metadata
- **Activity Logging** — every backend create/update/delete/reorder is logged via `CmsActivityLogger`, recording only the diff of changed fields on updates
- **Frontend Livewire Components** — semantic BEM-style components for page, section, category, article, gallery index, album, and menu rendering; all resolved automatically via the fallback routing system
- **Widget Blade Components** — 8 embeddable `<x-cms::widget.*>` components for use anywhere in your layout, most supporting `template`/`card` overrides for full custom markup (see [Widget Components](#widget-components))
- **Granular ACL** — CRUD permissions per resource and sub-resource (`cms.article.section`, `cms.gallery.album`, etc.)

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
        "vm-engine/synmod-cms": "^1.1"
    }
}
```

Then install and discover:

```bash
composer update vm-engine/synmod-cms
php artisan synapps:discover
php artisan migrate
```

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

## Custom Variables

All content types (pages, sections, categories, articles, albums, album images) support a configurable set of key/value custom variables stored in the `config` JSON column.

**Schema managers** (users with the relevant `update` permission) can define the schema: variable key, display label, and whether non-manager users may edit the value.

**Non-manager users** can edit variables marked as editable and, if the schema manager enables "Allow users to add", may also add new free-form key/value pairs.

Custom variables are accessible in frontend components and widgets via `$model->config['vars'] ?? []`.

---

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

`CmsArticleService` additionally owns `CmsSection`, `CmsCategory`, and `CmsTag` queries/writes (there's no separate service for those). `CmsGalleryService` owns both `CmsAlbum` and `CmsAlbumImage` — including `reorderImagesInAlbum()` and the auto-cover logic (`applyAutoCoverOnImageCreate`/`recomputeCoverOnImageDelete`) that used to live in `CmsAlbumImageObserver`. `CmsDownloadService` owns both `CmsDownload` and `CmsDownloadFile`, including `reorderFilesInDownload()`.

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
| `cms.settings` | read, update | CMS-wide settings |

`cms.gallery` itself has no CRUD actions — permissions are always checked at the sub-permission level (`album`, `banner`, `image`).

---

## Sitemap

The sitemap is served at `/sitemap.xml` by `SitemapController` registered in `routes/web.override.php` (no module URL prefix). The response sets `Content-Type: application/xml` and `Cache-Control: public, max-age=3600`.

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
vendor/bin/phpstan analyse packages/synmod-cms/src packages/synmod-cms/database/factories --level=5

# Code style
vendor/bin/pint packages/synmod-cms/
```
