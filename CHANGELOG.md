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

### Fixed
- `target_type` enum in `cms_menu_items` migration was missing `album_image` and `album_list` values — updated original migration and added alter migration for existing databases
