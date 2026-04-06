# Changelog

All notable changes to `vm-engine/synmod-cms` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
