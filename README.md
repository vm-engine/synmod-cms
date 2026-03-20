# synmod-cms

Content Management System module for [Laravel Synapse](https://github.com/vm-engine/synapse). Provides static pages, dynamic articles, a gallery system, and frontend menu management — all with automatic slug-based public routing via `synapps-fallback`.

**Package:** `vm-engine/synmod-cms`
**Module ID:** `cms`
**Requires:** PHP 8.2+, Laravel 12, Livewire 4, `vm-engine/synapse`, `vm-engine/synapps-auth`, `vm-engine/synapps-fallback`, `vm-engine/synmod-fm`

---

## Features

- **Static Pages** — up to 3 levels deep (`/parent/child/grandchild`), with body editor, SEO fields, and configurable display options
- **Sections / Categories / Articles** — hierarchical dynamic content with optional category grouping, tags, scheduling, featured flag, and read-time computation
- **Gallery** — albums of type `normal` (public, routed) or `banner` (display-only, no public route), with drag-and-drop image ordering via SortableJS
- **Menus** — user-defined positions (header, footer, sidebar, etc.), nested menu items linking to any CMS content or external URL, drag-and-drop reordering
- **Tags** — shared across articles with unique slug enforcement
- **Automatic Fallback Routing** — all published content registers its slug in `fallback_routes` on save and removes it on delete/unpublish; no route cache clearing needed
- **TinyMCE** — self-hosted rich text editor bundled via Vite, integrated with the FM file picker
- **Sitemap** — `/sitemap.xml` auto-generated from all published content
- **Frontend Components** — semantic BEM-style Blade/Livewire components for page, section, category, article, album, and menu rendering
- **ACL** — granular CRUD permissions per resource (`cms.page`, `cms.section`, `cms.article`, `cms.gallery`, `cms.menu`, `cms.tag`, `cms.settings`)

---

## Installation

This module is managed via the Synapse discovery system. Add it to your project's `composer.json`:

```json
{
    "require": {
        "vm-engine/synmod-cms": "@dev"
    },
    "repositories": {
        "vm-engine/synmod-cms": {
            "type": "path",
            "url": "packages/synmod-cms"
        }
    }
}
```

Then install and discover:

```bash
composer require vm-engine/synmod-cms:@dev
php artisan synapps:discover
php artisan migrate
```

---

## Backend Routes

All admin routes are under `/admin/cms/` and require authentication. Access is controlled per route by the `can-access` middleware.

| Route | Name | ACL |
|---|---|---|
| `GET /admin/cms/page` | `backend.cms.page.index` | `cms.page.read` |
| `GET /admin/cms/page/form/{id?}` | `backend.cms.page.form` | `cms.page.create\|update` |
| `GET /admin/cms/section` | `backend.cms.section.index` | `cms.section.read` |
| `GET /admin/cms/section/form/{id?}` | `backend.cms.section.form` | `cms.section.create\|update` |
| `GET /admin/cms/article` | `backend.cms.article.index` | `cms.article.read` |
| `GET /admin/cms/article/form/{id?}` | `backend.cms.article.form` | `cms.article.create\|update` |
| `GET /admin/cms/album` | `backend.cms.album.index` | `cms.gallery.read` |
| `GET /admin/cms/album/form/{id?}` | `backend.cms.album.form` | `cms.gallery.create\|update` |
| `GET /admin/cms/album/{albumId}/images` | `backend.cms.album.images` | `cms.gallery.update` |
| `GET /admin/cms/menu` | `backend.cms.menu.index` | `cms.menu.read` |
| `GET /admin/cms/menu/{menuId}/items` | `backend.cms.menu.items` | `cms.menu.update` |
| `GET /admin/cms/tag` | `backend.cms.tag.index` | `cms.tag.read` |

---

## Frontend Components

Livewire components for rendering public content. These are resolved automatically via the fallback routing system.

```blade
{{-- Static page --}}
<livewire:cms::frontend.page-viewer :slug="$slug" />

{{-- Section index --}}
<livewire:cms::frontend.section-index :slug="$slug" />

{{-- Category index --}}
<livewire:cms::frontend.category-index :sectionSlug="$sectionSlug" :slug="$slug" />

{{-- Article --}}
<livewire:cms::frontend.article-viewer :slug="$slug" />

{{-- Album --}}
<livewire:cms::frontend.album-viewer :slug="$slug" />
```

### Menu Component

Render a menu by position anywhere in your layout:

```blade
<livewire:cms::frontend.menu :position="'header'" />
```

Or use the Blade component:

```blade
<x-cms::menu :position="'header'" />
```

Positions are strings defined by the admin when creating a menu (e.g. `header`, `footer`, `sidebar`).

---

## Fallback Route Slugs

| Content | Public URL |
|---|---|
| Root page | `/{page_slug}` |
| Child page | `/{parent_slug}/{page_slug}` |
| Grandchild page | `/{grandparent_slug}/{parent_slug}/{page_slug}` |
| Section index | `/{section_slug}` |
| Category index | `/{section_slug}/{category_slug}` |
| Article under section | `/{section_slug}/{article_slug}` |
| Article under category | `/{section_slug}/{category_slug}/{article_slug}` |
| Normal album | `/gallery/{album_slug}` |

Banner-type albums do not register a public route.

---

## Models

| Model | Table | Notes |
|---|---|---|
| `CmsPage` | `cms_pages` | Self-referential tree, max 3 levels, `WithDeleteToken` |
| `CmsSection` | `cms_sections` | Top-level content container |
| `CmsCategory` | `cms_categories` | Belongs to section, optional config inheritance |
| `CmsArticle` | `cms_articles` | Belongs to section + optional category, BelongsToMany tags |
| `CmsTag` | `cms_tags` | Unique slug, shared across articles |
| `CmsAlbum` | `cms_albums` | `normal` or `banner` type |
| `CmsAlbumImage` | `cms_album_images` | Belongs to album, optional FK to `fm_files` |
| `CmsMenu` | `cms_menus` | Named position string |
| `CmsMenuItem` | `cms_menu_items` | Self-referential tree, polymorphic target |

---

## ACL Permissions

Permissions are defined in `module.json` and registered via Synapse's ACL system.

| Permission | Actions |
|---|---|
| `cms.page` | create, read, update, delete |
| `cms.page.publish` | read, update |
| `cms.section` | create, read, update, delete |
| `cms.article` | create, read, update, delete |
| `cms.article.publish` | read, update |
| `cms.gallery` | create, read, update, delete |
| `cms.menu` | create, read, update, delete |
| `cms.tag` | create, read, update, delete |
| `cms.settings` | read, update |

---

## Testing

```bash
# All CMS module tests
./vendor/bin/pest packages/synmod-cms/tests/

# Filter by resource
./vendor/bin/pest packages/synmod-cms/tests/ --filter="PageTest"
./vendor/bin/pest packages/synmod-cms/tests/ --filter="ArticleTest"
```

Tests cover: model factories, page rendering, CRUD via Livewire, fallback route sync (create/update/delete), and ACL enforcement.

---

## Code Quality

Run from the main Laravel project root:

```bash
# Static analysis (run first)
vendor/bin/phpstan analyse packages/synmod-cms/src packages/synmod-cms/database/factories --level=5

# Code style
vendor/bin/pint packages/synmod-cms/
```
