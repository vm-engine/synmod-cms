# Changelog

All notable changes to `vm-engine/synmod-cms` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
- Album image lightbox preview on click
- `CmsMenuService::buildTree()` eager-loads 3 levels (`children.children`) for frontend menu rendering
- Frontend `<x-cms::menu :position="'header'" />` Blade component with full 3-level BEM-class HTML output
- `CmsMenuItem::resolveUrl()` handles all 8 target types including `album_list` (→ `/gallery`) and `album_image` (→ `/gallery/{slug}#image-{id}`)
- Sitemap generation via `CmsSitemapService` and `/sitemap.xml` route
- ACL definitions for page, section, article, gallery, menu, tag, and settings resources
- Backend navigation registered in `module.json` with icon, route, and ACL per sub-section
- Fallback route registration/removal via Eloquent model observers on all public content models
- Migration `_000008_` to convert `target_type` enum column to plain string, removing the SQLite CHECK constraint and adding `album_image` and `album_list` support

### Added
- Gallery index frontend component (`cms::frontend.gallery-index`) with album card grid, breadcrumb (`Home > Gallery`), and empty state
- Album viewer frontend component: breadcrumbs (`Home > Gallery > Album`), gallery URL derived from request path, and full Alpine.js lightbox with prev/next navigation, keyboard shortcuts (←/→/Escape), image counter, title and caption display
- `CmsAlbumImageObserver`: automatically sets album `cover_image` from the first image added; updates cover to next image when the cover image is deleted
- `CmsMenuItemObserver`: registers/forgets the gallery-index fallback route when `album_list` menu items are created, updated, or deleted; handles slug changes and multi-item slug deduplication
- Gallery slug configurable per menu item — `target_url` field on `album_list` items stores the gallery slug (defaults to `gallery`)
- `CmsFallbackService::syncGalleryIndex(string $slug)` and `forgetGalleryIndex(string $slug)` for gallery fallback management
- All 5 frontend MFC components (`page-viewer`, `section-index`, `category-index`, `article-viewer`, `album-viewer`) now resolve layout via `theme_service()` with fallback to default layout
- `home` translation key added to `lang/en/labels.php`
- Lightbox CSS (`cms-lightbox`, `cms-lightbox__stage`, `cms-lightbox__nav`, `cms-lightbox__caption-bar`) in theme.css
- Gallery index CSS (`cms-gallery-index`, `cms-gallery-album-card`) in theme.css

### Fixed
- `input-group` / `input-group-item` CSS redesigned to proper flex layout — Browse button in all FM file pickers now displays correctly with full text, correct border-radius, and is clickable
- Icon color in `input-group-item.btn` no longer forced to gray; inherits from button variant (white on primary)
- All list pages (page, article, album, section, category) moved filter button after search input to match flex DOM order
- `FallbackRoutingMiddleware`: `livewire` handler type now uses `LivewirePageController::class.'@__invoke'` and stores component alias in `action['livewire_component']`, fixing "Method name must be a string" error
- Frontend MFC components used `$this->view()` (not available on `Livewire\Component`) — replaced with global `view()` helper
- `CmsAlbumImage` model: added `@property-read FmFile|null $file` PHPDoc for correct static analysis

### Fixed
- `target_type` enum in `cms_menu_items` migration was missing `album_image` and `album_list` values — updated original migration and added alter migration for existing databases
