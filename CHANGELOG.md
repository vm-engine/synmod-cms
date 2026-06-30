# Changelog

All notable changes to `vm-engine/synmod-cms` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.10] - 2026-06-30

### Fixed
- TinyMCE: added `relative_urls: false` and `remove_script_host: false` to prevent video/file paths from being converted to relative URLs based on the current admin page depth, which was prepending `../../..` to inserted media paths

## [1.1.9] - 2026-06-29

### Added
- `is_package: true` flag added to `module.json`
- TinyMCE toolbar now includes `media` button (video embeds) by default

### Changed
- TinyMCE FM picker hidden via targeted CSS (hides label/input-row but preserves the modal) instead of `hidden` class, so the picker overlay still opens correctly
- `module.json` ACL entries reformatted to one-key-per-line style for readability

## [1.1.8] - 2026-04-28

### Added
- **`CmsActivityLogger::logReorder()`** — generic helper that logs a reorder action against a parent entity with the new ordered child-ID list. Action key follows `cms.{parent}.{id}.reorder_{collection}` (e.g. `cms.download.5.reorder_files`).

### Changed
- **`CmsDownloadService::reorderFilesInDownload`** now logs `cms.download.{id}.reorder_files` after the transaction commits.
- **`CmsGalleryService::reorderImagesInAlbum`** now logs `cms.album.{id}.reorder_images` after the transaction commits.
- **`CmsMenuService::reorderItemsInMenu`** now logs `cms.menu.{id}.reorder_items` after the transaction commits.

Each reorder method now resolves the parent model via `findOrFail` before mutating, so a stale parent id surfaces as a 404 before any sort-order writes happen.

## [1.1.7] - 2026-04-27

### Changed
- **Domain service refactor** — extracted four new domain services (`CmsPageService`, `CmsArticleService`, `CmsGalleryService`, `CmsDownloadService`) and extended `CmsMenuService` so every read query and write operation outside the model itself flows through a service. Every backend list/form, frontend viewer/index, view-component widget, controller, and form object now calls service methods instead of static `Cms*::` queries.
- **Form objects** (`PageFormObject`, `ArticleFormObject`, `SectionFormObject`, `CategoryFormObject`, `TagFormObject`, `AlbumFormObject`, `AlbumImageFormObject`, `DownloadFormObject`, `DownloadFileFormObject`, `MenuFormObject`) keep validation rules and `setX()` / `toData()` field state but delegate every `create` / `update` / `delete` / `sync` / activity-log call to the corresponding domain service.
- **Article writes** (`CmsArticleService::createArticle` / `updateArticle`) now run inside `DB::transaction` so the article save, tag sync, and activity log either all commit or all roll back.
- **Observer cleanup** — `CmsAlbumImageObserver` cover-image cascade logic moved into `CmsGalleryService::applyAutoCoverOnImageCreate` / `recomputeCoverOnImageDelete`; observer is now a thin dispatcher.
- **Backend `menu-item-manager`** — adv-select search/load methods (`searchPages`, `searchSections`, `searchCategories`, `searchArticles`, `searchAlbums`, `searchAlbumImages`, `searchDownloads`, plus their `load*` counterparts) delegate to per-domain service methods. The edit flow now uses the menu-scoped lookup (`findMenuItemOrFailInMenu`), preventing edits to items that belong to a different menu.
- **Page service** delegates `full_slug` computation back to the model's existing `CmsPage::computeFullSlug()` instead of duplicating the logic.

### Fixed
- N+1 in `CmsPageService::getParentOptions` — root pages now eager-load `children` so the parent dropdown no longer fires one query per root page.

### Internal
- `CmsArticleService::resolveTagIds` and `findOrCreateTagByName` made `private` (only used internally by `createArticle` / `updateArticle`).
- `CmsPageService::getPublishedChildren` and `getPublishedDescendants` reuse the `published()` Eloquent scope instead of inlining its predicate.
- `CmsFallbackService`, `CmsActivityLogger`, and `CmsSitemapService` remain unchanged — cross-cutting infrastructure that domain services compose with.

## [1.1.6] - 2026-04-23

### Added
- **`<x-cms::menu>`** — new `template` and `with` props so callers can pass a custom Blade view that receives `$items`, `$position`, and any forwarded vars. Default `<nav class="cms-menu">` markup remains the fallback when no template is passed, so existing callers are unaffected.
- **`<x-cms::widget.section-articles>`** — new `template` and `with` props that render a caller-provided view with `$section`, `$articles`, `$card`, plus forwarded vars. Enables wrapping the section header + article grid in custom layouts without re-querying the section.

### Changed
- **`composer.json`** — dropped the static `version` field so the package version is derived from git tags (avoids drift between composer.json and tags).

## [1.1.5] - 2026-04-20

### Added
- **Custom view / layout overrides on every content type** — new `custom_view` + `custom_layout` columns on `cms_pages`, `cms_sections`, `cms_categories`, `cms_articles`, `cms_albums`, `cms_downloads`, and `cms_menu_items`; plus `custom_category_view` + `custom_article_view` on sections and `custom_article_view` on categories for scoped inheritance
- **Runtime cascade resolvers** (`resolveCustomView()` / `resolveCustomLayout()` on each model) — articles inherit from category → section, categories inherit from section, child pages inherit from parent pages; menu items + albums + downloads resolve locally
- **Admin form UI** — new Custom Rendering card exposes the override fields on every page / section / category / article / album / download form; menu form shows the override inputs only for `album_list` and `download_list` target types
- **Dedicated cascade tests** — `tests/Feature/CustomView/ResolverCascadeTest.php` covers article → category → section, child page → parent, and category `custom_article_view` inheritance
- **`FrontendViewResolver` helper** (`src/Support/FrontendViewResolver.php`) centralises view + layout resolution used by every frontend viewer MFC (page, section, category, article, album, gallery, download category, download file)

### Changed
- Every frontend viewer MFC now dispatches through `FrontendViewResolver`, eliminating ~80 lines of duplicated `view()->exists()` / theme-layout fallback logic
- Extracted the Custom Rendering card into two reusable partials (`_custom-rendering-card.blade.php`, `_custom-rendering-fields.blade.php`) consumed by all six content forms plus the three menu-item sub-forms (add, edit, inline add)
- `CmsMenuItemObserver` trimmed of redundant narration comments

### Translations
- Added `custom_view`, `custom_layout`, `custom_category_view`, `custom_article_view`, and per-entity `*_help` strings to both `lang/en/labels.php` and `lang/id/labels.php`

## [1.1.4] - 2026-04-15

### Added
- `<x-cms::widget.page>` — new `template`, `bare`, `wrapperClass`, and `with` props to render a custom Blade template receiving the `$page` + forwarded vars
- `<x-cms::widget.banner-slider>` — new `template` and `with` props plus a public `$images` collection so custom templates can render the album without re-querying; each slide now exposes a `link` value sourced from `config.link`
- `<x-cms::widget.section-articles>` — new `card`, `bare`, `wrapperClass`, and `gridClass` props for per-article template overrides; extracted default card into `_default-card.blade.php`
- `<x-cms::widget.gallery>` component (initial scaffolding for gallery rendering)

### Changed
- Renamed the old `$view` prop on widget components to `$template` to avoid collision with Blade's reserved `$view` variable, which previously broke rendering inside class components



### Changed
- Tightened dependency version constraints: `vm-engine/synapps-fallback` from `*` to `^1.0`, `vm-engine/synmod-fm` from `*` to `^1.0`

## [1.1.2] - 2026-04-11

### Added
- `CmsActivityLogger` service (`src/Services/CmsActivityLogger.php`) — lightweight audit helper wrapping `SynAuth::logActivity()` with `logCreate()`, `logUpdate()`, and `logDelete()` methods
- Update logs only record the diff of changed fields (old → new), ignoring `created_at`/`updated_at`
- Activity logging wired into every backend mutation path:
  - Create/update logging inside all form objects: `PageFormObject`, `ArticleFormObject`, `SectionFormObject`, `CategoryFormObject`, `AlbumFormObject`, `AlbumImageFormObject`, `MenuFormObject`, `TagFormObject`, `DownloadFormObject`, `DownloadFileFormObject`
  - Delete logging inside list components: `page-list`, `article-list`, `section-list`, `category-list`, `album-list`, `menu-list`, `tag-list`, `download-list`
  - Full create/update/delete logging inside in-page managers: `menu-item-manager`, `album-image-manager`, `download-file-manager`
- Action keys follow the convention `cms.{entity}.{id}.{action}` with feature `{entity}.{id}` and module `cms`, matching the logging pattern used by other Synapse modules

## [1.1.1] - 2026-04-07

### Added
- Indonesian (`id`) translations for all language files: `lang/id/acl.php`, `lang/id/labels.php`, `lang/id/menu.php`
- `CLAUDE.md` with architecture documentation and development commands for this package

### Fixed
- Removed stale `@phpstan-ignore argument.type` comments from `Menu.php` and `Tinymce.php` view components (errors no longer present)
- Removed redundant `php` and `laravel/framework` entries from `composer.json` `require` (already required by the host application)
- Test roles now set `can_access_backend: true` to correctly pass backend access middleware in all `Page Rendering` tests

## [1.0.2] - 2026-04-06

### Fixed
- All `create` migrations now guard with `Schema::hasTable()` to prevent errors on fresh installs where tables were previously created outside Laravel migrations
- `update_cms_menu_items_target_type` migration is now idempotent — checks column existence before each step to safely re-run on existing databases
- `add_config_to_cms_content_tables` migration guards against adding duplicate columns when the migration has already been partially applied

## [1.0.1] - 2026-04-06

### Fixed
- Migration order: renamed `cms_albums`, `cms_menus`, and subsequent migrations to timestamps after `fm_files` migration (`2026_03_14_000001`) to prevent foreign key constraint error (errno: 150) when `cms_album_images.file_id` references `fm_files.id`

## [1.0.0] - 2026-04-04

### Added
- Static pages with 3-level nesting (grandparent → parent → child), full slug computation, and Eloquent observer syncing fallback routes on create/update/delete
- Dynamic content system: sections, categories (optional), and articles with draft/published/scheduled status, featured flag, tags, SEO fields, and read-time computation
- Gallery system: albums (normal/banner types) and album images with drag-and-drop reordering and image lightbox preview
- Frontend menu system with named positions (e.g. header, footer), 3-level nested items, and support for 8 target types: Custom URL, Page, Section, Category, Article, Album, Album Image, Album List
- Menu item manager backend with inline sub-item add forms at every level, content selectors using `x-synapse-adv-select` for each content type, and confirm-dialog delete pattern
- Tag management with modal form and inline slug generation
- TinyMCE self-hosted rich text editor component integrated with File Manager picker
- File Manager picker integration (`livewire:fm::file-picker`) for cover images and album images, dispatching `fileId` alongside URL via `fm:file-selected` event
- Upload drag-and-drop with client-side pre-validation (file type and size) and specific error toasts
- Album image lightbox preview on click with Alpine.js (prev/next navigation, keyboard shortcuts ←/→/Escape, image counter, title and caption display)
- Gallery index frontend component (`cms::frontend.gallery-index`) with album card grid, breadcrumb (`Home > Gallery`), and empty state
- Album viewer frontend component with breadcrumbs, gallery URL derived from request path, and full Alpine.js lightbox
- `CmsMenuService::buildTree()` eager-loads 3 levels (`children.children`) for frontend menu rendering
- Frontend `<x-cms::menu :position="'header'" />` Blade component with full 3-level BEM-class HTML output
- `CmsMenuItem::resolveUrl()` handles all 8 target types including `album_list` (→ `/gallery`) and `album_image` (→ `/gallery/{slug}#image-{id}`)
- `CmsAlbumImageObserver`: automatically sets album `cover_image` from the first image added; updates cover to next image when the cover image is deleted
- `CmsMenuItemObserver`: registers/forgets the gallery-index fallback route when `album_list` menu items are created, updated, or deleted; handles slug changes and multi-item slug deduplication
- Gallery slug configurable per menu item — `target_url` field on `album_list` items stores the gallery slug (defaults to `gallery`)
- `CmsFallbackService::syncGalleryIndex(string $slug)` and `forgetGalleryIndex(string $slug)` for gallery fallback management
- All 5 frontend MFC components (`page-viewer`, `section-index`, `category-index`, `article-viewer`, `album-viewer`) now resolve layout via `theme_service()` with fallback to default layout
- Sitemap generation via `CmsSitemapService` and `/sitemap.xml` route
- ACL definitions for page, section, article, gallery, menu, tag, and settings resources
- ACL sub-permissions for gallery: `cms.gallery.album`, `cms.gallery.banner`, `cms.gallery.image` — granular control per content type
- ACL sub-permissions for articles: `cms.article.section` and `cms.article.category` — sections and categories managed under the article permission tree
- Backend navigation registered in `module.json` with icon, route, and ACL per sub-section
- Fallback route registration/removal via Eloquent model observers on all public content models
- `CmsAlbumImage` model uses `WithDeleteToken` trait — consistent with all other deletable models
- 7 embeddable Blade widget components in `src/View/Components/Widget/` (`<x-cms::widget.*>`):
  - `<x-cms::widget.page slug="about" />` — single page content (title, cover image, body)
  - `<x-cms::widget.page-children slug="services" layout="grid" />` — page + published children grid/list
  - `<x-cms::widget.articles sort="latest" :limit="6" layout="grid" />` — all published articles with sort (latest/oldest/featured/az/za) and grid/list layout
  - `<x-cms::widget.section-articles section="news" :limit="6" />` — articles filtered by section slug
  - `<x-cms::widget.category-articles section="news" category="tech" />` — articles filtered by section + category slug
  - `<x-cms::widget.tag-articles tags="php,laravel" match="any" />` — articles filtered by tag slugs (any/all match)
  - `<x-cms::widget.banner-slider album="hero" :autoplay="true" :interval="5000" height="480px" />` — banner-type album as Alpine.js carousel with controls and dot indicators
- Test coverage: `CategoryTest.php`, `AlbumImageTest.php`, `SitemapTest.php`, `MenuServiceTest.php` covering CRUD, fallback route sync, observer behaviour, sitemap output, menu tree building, and all 8 `CmsMenuItem::resolveUrl()` target types
- ACL denial tests added to all resource test files (section, article, album, menu)
- `home` and `featured` translation keys added to `lang/en/labels.php`
- Banner slider CSS (`.cms-banner-slider`, `.cms-banner-slider__slide`, `.cms-banner-slider__control`, `.cms-banner-slider__dot`) in theme.css
- Gallery index CSS (`cms-gallery-index`, `cms-gallery-album-card`) in theme.css
- Lightbox CSS (`cms-lightbox`, `cms-lightbox__stage`, `cms-lightbox__nav`, `cms-lightbox__caption-bar`) in theme.css
- Widget layout CSS: `.cms-article-grid--list`, `.cms-article-card__section`, `.cms-widget-tag-labels`, `.cms-widget-page`, `.cms-widget__heading`

### Changed
- `cms.section` permission moved from top-level to `cms.article.section` sub-permission
- `cms.gallery` flat CRUD replaced with `cms.gallery.album`, `cms.gallery.banner`, `cms.gallery.image` sub-permissions

### Fixed
- `input-group` / `input-group-item` CSS redesigned to proper flex layout — Browse button in all FM file pickers now displays correctly with full text, correct border-radius, and is clickable
- Icon color in `input-group-item.btn` no longer forced to gray; inherits from button variant (white on primary)
- All list pages (page, article, album, section, category) moved filter button after search input to match flex DOM order
- `FallbackRoutingMiddleware`: `livewire` handler type now uses `LivewirePageController::class.'@__invoke'` and stores component alias in `action['livewire_component']`, fixing "Method name must be a string" error
- Frontend MFC components used `$this->view()` (not available on `Livewire\Component`) — replaced with global `view()` helper
- `CmsAlbumImage` model: added `@property-read FmFile|null $file` PHPDoc for correct static analysis
- `target_type` enum in `cms_menu_items` migration was missing `album_image` and `album_list` values — updated original migration and added alter migration for existing databases
