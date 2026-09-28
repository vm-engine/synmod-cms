# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Package Identity

- **Package:** `vm-engine/synmod-cms` | **Module ID:** `cms` | **Namespace:** `VmEngine\Cms\`
- **Type:** Synapse package module under `packages/synmod-cms/`
- **Depends on:** `vm-engine/synapps-auth`, `vm-engine/synapps-fallback`, `vm-engine/synmod-fm`

## Code Quality Commands

All tools run from the **main Laravel project root** (`/srv/www/pips-kfl/`), not from inside this package:

```bash
# Static analysis (run FIRST, fix all errors before Pint)
vendor/bin/phpstan analyse -c packages/synmod-cms/phpstan.neon --no-progress

# Code style
vendor/bin/pint packages/synmod-cms/

# Run all tests
./vendor/bin/pest packages/synmod-cms/tests/

# Run a single test file
./vendor/bin/pest packages/synmod-cms/tests/Feature/PageTest.php

# Run filtered tests
./vendor/bin/pest packages/synmod-cms/tests/ --filter="ArticleTest"
```

## Generating New Files

Use `synmod-make` from inside this package directory (or `--path` from project root):

```bash
# From inside packages/synmod-cms/
./vendor/bin/synmod-make livewire DownloadList --mfc
./vendor/bin/synmod-make model CmsDownload
./vendor/bin/synmod-make migration add_field_to_cms_pages

# From project root
./vendor/bin/synmod-make livewire DownloadList --mfc --path=packages/synmod-cms
```

**Always prefer MFC (`--mfc`) for Livewire components** unless the component is trivially simple or lives inside a modal.

## Architecture

### Content Types & Models

| Model | Table | Notes |
|---|---|---|
| `CmsPage` | `cms_pages` | 3-level nesting via `parent_id`; `full_slug` computed by observer |
| `CmsSection` | `cms_sections` | Top-level container for articles |
| `CmsCategory` | `cms_categories` | Optional grouping within a section; `effectiveConfig()` inherits from section |
| `CmsArticle` | `cms_articles` | Draft/Published/Scheduled (`ArticleStatus` enum); `read_time` auto-computed |
| `CmsAlbum` | `cms_albums` | Type: `Normal` (public route) or `Banner` (display-only, no route) |
| `CmsAlbumImage` | `cms_album_images` | FK to `fm_files`; auto-cover via observer |
| `CmsDownload` | `cms_downloads` | Download categories |
| `CmsDownloadFile` | `cms_download_files` | Files within a download category |
| `CmsMenu` | `cms_menus` | Identified by `position` string (e.g. `header`) |
| `CmsMenuItem` | `cms_menu_items` | 8 target types; URL resolved via `resolveUrl()` |
| `CmsTag` | `cms_tags` | Shared across articles; unique slug |

All deletable models use the `WithDeleteToken` trait for CSRF-safe deletion.

### Fallback Routing (Critical Pattern)

All public slugs are auto-registered in `fallback_routes` by Eloquent **observers** — never register routes manually from forms or services:

- `CmsPageObserver` → syncs page + cascades `full_slug` to descendants
- `CmsSectionObserver` → syncs section + cascades categories and articles
- `CmsCategoryObserver` → syncs category + re-syncs articles under new path
- `CmsArticleObserver` → syncs article based on section + optional category path
- `CmsAlbumObserver` → syncs `normal` albums only; `banner` and `inactive` get no route
- `CmsAlbumImageObserver` → auto-sets `cover_image`; updates on delete
- `CmsMenuItemObserver` → registers/removes gallery-index route for `album_list` items

**`CmsFallbackService`** is the central service called exclusively by observers. Do not call it from Livewire forms.

### Livewire Components

Located in `src/Livewire/` (standard) or `resources/views/components/` (MFC). Form objects live in `src/Forms/`.

**Backend (admin):** All CRUD under `/admin/cms/` with `can-access` middleware.

**Frontend (public):** Resolved automatically via fallback routing — no explicit route needed:
- `cms::frontend.page-viewer`, `section-index`, `category-index`, `article-viewer`, `album-viewer`, `gallery-index`, `download-index`, `download-viewer`, `menu`
- Each viewer resolves its Blade view/layout via `FrontendViewResolver::view()` / `::layout()` against the model's `resolveCustomView()` / `resolveCustomLayout()` — see "Custom View/Layout Overrides" below.

**Widgets (embeddable anywhere):** `<x-cms::widget.page>`, `page-children`, `articles`, `section-articles`, `category-articles`, `tag-articles`, `banner-slider`, `gallery` — all in `src/View/Components/Widget/`. Most accept `template`/`card` (Blade view overrides), `wrapperClass`/`gridClass`, `bare`, and `with` (extra vars forwarded to the custom view) — check the individual component class for which props it supports, they aren't uniform across all eight.

### Rich-Text Editor

Forms render `<x-cms::rte>`, which picks `<x-cms::jodit>` (default) or `<x-cms::tinymce>` from `RteDriver::current()` (`dbconf('cms.editor.driver')`, set by `cms:setup` and the settings page at `cms.settings`). Never use `x-cms::jodit`/`x-cms::tinymce` directly in forms. Jodit's Alpine component (`cmsJodit`) lives in `resources/js/cms.js` and lazy-imports `jodit` **plus `jodit/esm/plugins/all.js`** (`'jodit'` alone is the ESM core with 24 plugins — no justify/source/video/paste, so those buttons render as text and paste cleanup silently does nothing); `cms:setup` imports `cms.js` into the host's `resources/js/app.js`. Both editors insert images/links through the FM picker events (`fm:open-picker` → `fm:file-selected`, filtered by picker key).

Tests for `cms:setup` must point `$this->app->setBasePath()` at a temp dir — never edit the real host files and restore them in `afterEach` (it is skipped when a `PendingCommand` fails).

### Custom View/Layout Overrides

Every content model (`CmsPage`, `CmsSection`, `CmsCategory`, `CmsArticle`, `CmsAlbum`, `CmsDownload`, `CmsMenuItem`) has `custom_view`/`custom_layout` columns plus `resolveCustomView()`/`resolveCustomLayout()` methods — **always call the resolver method, never read the raw column**, since several models cascade:

- `CmsArticle` → own → category's `custom_article_view` → section's `custom_article_view` (views); own → category → section (layouts)
- `CmsCategory` → own → section's `custom_category_view`/`custom_layout`
- `CmsPage` → own → parent page, recursively
- `CmsAlbum`, `CmsDownload`, `CmsMenuItem` → own value only, no cascade

`FrontendViewResolver::view($custom, $default)` / `::layout($custom)` (in `src/Support/FrontendViewResolver.php`) apply the `view()->exists()` check and theme-layout fallback — every bundled frontend viewer MFC calls these instead of duplicating the logic. Do the same in any new frontend viewer.

### Services

Since v1.1.7, all queries/writes outside the model itself go through these — never call `Cms*::query()` directly from Livewire/forms/controllers:

- **`CmsPageService`** — `CmsPage` finders, backend listing, create/update/delete, adv-select search/load
- **`CmsArticleService`** — `CmsArticle` **and** `CmsSection`/`CmsCategory`/`CmsTag` (no separate services for those three)
- **`CmsGalleryService`** — `CmsAlbum` **and** `CmsAlbumImage`, incl. `reorderImagesInAlbum()` and the auto-cover logic (moved out of `CmsAlbumImageObserver`)
- **`CmsDownloadService`** — `CmsDownload` **and** `CmsDownloadFile`, incl. `reorderFilesInDownload()`
- **`CmsActivityLogger`** — `logCreate()`/`logUpdate()` (diff-only)/`logDelete()`/`logReorder()`; injected into every domain service above and called internally on every write — action keys follow `cms.{entity}.{id}.{action}`
- **`CmsFallbackService`** — fallback route management (observers only)
- **`CmsMenuService`** — `buildTree('position')` eager-loads 3 levels for frontend rendering
- **`CmsSitemapService`** — generates `/sitemap.xml` XML; called by `SitemapController`

### Routes

- `routes/web.php` — standard module routes (must exist even if empty)
- `routes/web.backend.php` — all admin routes under `/admin/cms/`
- `routes/web.override.php` — `/sitemap.xml` (no module prefix)

### ACL Structure

Permissions follow the `cms.resource[.sub][action]` pattern. Key permissions:
- `cms.page`, `cms.page.publish`
- `cms.article`, `cms.article.publish`, `cms.article.section`, `cms.article.category`
- `cms.gallery.album`, `cms.gallery.banner`, `cms.gallery.image` (no top-level `cms.gallery` CRUD)
- `cms.menu`, `cms.tag`, `cms.download.category`, `cms.download.file`, `cms.settings`

### Translations

Translation keys follow `__cms::file.key` format. Always update both `lang/en/` and `lang/id/` when adding keys.

### Custom Variables (`config` JSON column)

All content types have a `config` JSON column storing custom variables under `config['vars']`. Schema managers define keys/labels; non-managers can edit editable vars. Access in frontend: `$model->config['vars'] ?? []`.
