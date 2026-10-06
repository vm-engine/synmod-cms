# Changelog

All notable changes to `vm-engine/synmod-cms` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - Unreleased

### Added
- **Article display options** — sections get **Show Excerpt on Detail Page** (`show_excerpt`, default on), and the existing **Show Timestamp** (`show_date`) now applies to the article detail page, category lists and article widgets, not just the section list. Each article can override both with **Follow section / Show / Hide** (stored in the article's `config`, `null` = follow); the timestamp override also hides/shows that article's card date in every list and widget. New `CmsArticle::showsDate()` / `showsExcerpt()` — custom article templates should use them. Section `items_per_page` keeps paginating section and category lists.
- **Page display options** — four new per-page settings in `config`: **Show Excerpt on Detail Page** (`show_excerpt`), **Show Child Pages as Sub-Menu** (`show_submenu`: children/sibling navigation on the parent and its children), **Child Pages Display** (`children_display`: `none` / `cards` / `one_page`, which renders every published child's full content below the parent with `#cms-child-{slug}` anchors), and the dev-only **Allow non-dev users to add child pages** (`allow_child_create`, default on; when off, non-devs can't add or move pages under it). New `CmsPage::childrenDisplay()`, `acceptsChildrenFromCurrentUser()` and `childAnchor()`. Default theme styles `.cms-submenu*`, `.cms-page-section*`, `.cms-page__excerpt`. Upgrade: `npm run build`.
- **Media galleries** — inline image sliders or grids placed anywhere in the content with a `[gallery:key]` tag. Each page, article or download file can own several; editors with update rights on the item manage them in a new **Media Galleries** panel (title, key, slider/grid layout, FM images with drag-to-reorder and per-locale captions, copy-tag button). Devs switch the feature on per root page (covers the page and all its children), per section (its articles) or per download (its files). Unknown, empty or disabled tags render nothing, and tags never reach the search index. Backed by the new `cms_media_galleries` / `cms_media_gallery_images` tables, `CmsMediaGalleryService`, `CmsMediaGalleryRenderer` and the `HasMediaGalleries` model trait (`renderWithMediaGalleries('body')` in templates). Default theme styles in `themes/default/css/cms.css` (`.cms-media-gallery*`); override the markup via `cms::components.frontend.partials._media-gallery-slider` / `_media-gallery-grid`. `php artisan cms:prune-media-galleries [--dry-run]` removes galleries orphaned when a section or download delete cascades to its articles/files. Upgrade: `php artisan migrate && npm run build`.
- **Custom fields** — typed fields that devs (`is_dev()`) define on root pages, sections, download categories and albums: text, textarea, number, email, url, date, datetime, select, radio, checkbox, checkboxes, each with label, options, default, required and help. Labels starting with `__` are translation keys. Every editor fills in per-locale values on the container and the items under it (descendant pages, articles, download files, album images), with fallback to the primary locale and then the default. A field's type is locked once it has values. Values can be searched (added to the public search index), filtered and sorted: `whereField()` / `whereFieldIn()` / `orderByField()` scopes, and `?f[...]&sort=&dir=` filters on the section, category, album, download and page-children listings with a default `<x-cms::field-filters>` bar. Read values with `$model->field('name')` / `fields()`. Date and datetime fields use `<x-synapse-datepicker>`, in both the backend inputs and the Livewire-bound filter bar (`WithFieldFilters`). Backed by the new `cms_fields` and `cms_field_values` tables and `CmsCustomFieldService`. `cms:setup` now also adds the flatpickr stylesheet and `synapse-components.js` to the host's `resources/js/frontend.js`. Upgrade: `php artisan migrate && php artisan cms:setup && npm run build`.
- **Content access control** — frontend visibility **Public** (everyone) / **Protected** (logged-in users) / **Private** (selected users or roles) on pages, sections, articles, albums and downloads. Child pages and articles can also **Inherit** (their default): child pages from the parent page, articles from their section, and view lists add up down the tree. Per-item **managers lists** restrict who may update or delete an item and add content under it: page managers cover the whole subtree, section managers cover its categories and articles, album and download managers cover their images and files. The module ACL is still required; an empty list means everyone with the ACL. Managers always see what they manage on the frontend, and devs see everything. Blocked guests are redirected to login and blocked logged-in users get a 404. Lists are edited in a new **Access** panel on each form, gated by the new `cms.access.update` ACL; entries added by a dev are *base* entries that only a dev can remove. Backed by a new `cms_access_grants` table, `CmsAccessService` (`canView`/`authorizeView`/`canManage`/`authorizeManage`/`isPublic`), the `HasCmsAccess` model trait (`visibleTo()` scope) and the `HasAccessFields` / `WithAccessPicker` / `WithCmsAccessChecks` Livewire traits. Upgrade: `php artisan migrate && php artisan cms:search-reindex`, then grant `cms.access` to the roles that should edit access settings.
- **Localized links, sitemap alternates & language switcher** — every CMS link (views, widgets, breadcrumbs, menus) keeps the visitor's language via the new `CmsUrl` helper (`path()` is also what `CmsFallbackService` registers, so links can't drift from routes). Menu `url` targets that are site-internal paths (`/contact`) get the locale prefix; external/anchor/mailto/backend links are untouched. Menu album-image links now use `#item-{id}` (opens the lightbox). `/sitemap.xml` lists every language version with `xhtml:link` hreflang alternates + `x-default` (single-language output unchanged). New `<x-cms::language-switcher>` (native `<details>` dropdown or `variant="list"`; `display="name|code|both|icon|icon-name|icon-code"`, `trigger="globe"`, `hide-current`, `globe-icon`, `wrapper-class`, attribute merge, custom `template`/`with`) and `<x-cms::hreflang />` for layout heads. Language names come from intl (`CmsLocale::displayName()`), overridable via `module.json` `language_names`; icons via `language_icons` (image path/URL incl. File Manager uploads, CSS class such as `fi fi-id`, or emoji) with a region-derived flag-emoji default.
- **Public search (multi-language content, phase 5)** — search page at `/{locale}/{search.slug}` (`cms::frontend.search-results`, registered by `cms:search-reindex`/`cms:setup`) with type tabs and counts (All, Pages, Articles, Categories, Albums & media, Downloads), XSS-safe `<mark>` highlighting, snippets, pagination and a per-IP rate limit; `<livewire:cms::frontend.search-box />` for layouts — plain GET form (Enter works without JS), live top-5 suggestions + "See all", popular searches when empty, keyboard navigation and ARIA combobox (`cmsSearchBox` in `cms.js`, needs `npm run build`). Popular terms (`cms_search_terms`) are counted once per session and only when a search has results, shown above `search.popular_min_hits`, and moderated in the new backend **Search Terms** list (hide/unhide/delete, ACL `cms.search_term`). Album items and download files get `#item-{id}` / `#file-{id}` anchors (the lightbox opens the linked image); style `:target` in your theme to highlight. Settings: `search.slug`, `per_page`, `rate_limit`, `suggest_rate_limit`, `popular_min_hits`, `popular_limit` in `module.json` (override in `synapps/config/modules.json`; run `cms:search-reindex` after changing the slug). Upgrade: `php artisan migrate && php artisan cms:search-reindex && npm run build`.
- **Public search index (multi-language content, phase 4)** — new `cms_searchables` table: one row per enabled locale for every publicly routed page, article, article category, album and download category, plus album items (`#item-{id}`) and download files (`#file-{id}`), with the default-language fallback resolved at write time and URLs stored as locale-prefixed paths. Kept in sync from `CmsFallbackService` (so the index always matches the public routes; private downloads and banner albums are never indexed) and the item observers. Translation-only edits now notify the model's observers (`setTranslations()` bumps `updated_at` quietly and fires `updated` once — `touch()` alone fires nothing within the same second as the base save, which is exactly the form-save case). Query API: `CmsSearchService::search($q, ?$type, $perPage, ?$locale)` / `suggest()`. Optional Laravel Scout engine (set `{"cms": {"search": {"engine": "scout"}}}` in `synapps/config/modules.json`; model `CmsScoutSearchable`). CMS settings now live in `module.json` (`config('synapps.modules.cms.*')`) and `config/cms.php` was removed. New `php artisan cms:search-reindex {--type=*}` (also run by `cms:setup`). Upgrade: `php artisan migrate && php artisan cms:search-reindex`.
- **Translated frontend (multi-language content, phase 3)** — every CMS viewer (page, article, section, category, album, download), widget (articles, section/category/tag articles, page, page children, gallery, banner slider) and menu renders the visitor's language (`/id/...`) with per-field fallback to the default language. Page `<title>` uses the locale's own meta title, then its title (`translatedMetaTitle()`). When multi-language is on, translatable models eager-load `translations` with every query (no N+1); `translations` is hidden from `toArray()`. **Custom frontend templates** (`custom_view` / widget `template`/`card` overrides) must switch from `$model->title` to `$model->translate('title')` to show translations.
- **Translation editing (multi-language content, phase 2)** — language tabs (`<x-cms::locale-tabs>`, ● translated / ○ empty, warning icon on validation errors) on the page, article, section, category, album, download, album-image and download-file forms switch the translatable fields between the default language and each enabled language (`<x-cms::translation-input>`: text, textarea or rich-text editor per locale; empty = fall back to the default; errors name the field by its label). One Save writes the content and all languages in one transaction (`HasTranslationFields`). Menu items get a label input per language. Backend list search matches any language (`whereLikeWithTranslations()`); page and article lists show translated-locale badges (`<x-cms::locale-badges>`). Requires `npm run build` (new `cmsLocaleTabs` in `cms.js`).
- **Translation core (multi-language content, phase 1)** — new `cms_translations` table and `HasTranslations` trait on pages, articles, sections, categories, albums, album images, downloads, download files and menu items. Base columns stay the primary locale (`synapps.modules.cms.primary_locale` in `module.json`, overridable in `synapps/config/modules.json`; default `app.locale`, pinned at boot so per-request `app()->setLocale()` can't move it), so existing content needs no migration. Read with `$model->translate('title', ?$locale)` (per-field fallback to the base column); write with `$model->setTranslations(['id' => [...]])` (HTML fields purified with `cms_rte`, an all-empty locale deletes its row; orphaned rows from DB-level cascades are pruned on delete). New `CmsLocale` helper. Run `php artisan migrate`.
- **Jodit rich-text editor (new default)** — `<x-cms::jodit>`, lazy-loaded from the Vite bundle (core + all plugins, only on editor pages). Toolbar offers the same functions as TinyMCE's (undo/redo, block format, bold/italic, align left/center/right, lists, link, image, video, source); image/file/video buttons use the synmod-fm picker (no separate upload endpoint). TinyMCE remains available.
- **`<x-cms::rte>`** — renders the editor chosen in CMS settings (`RteDriver` enum, stored in `dbconf('cms.editor.driver')`, default `jodit`). Page, section and article forms use it.
- **CMS Settings page** (`/admin/cms/settings`, `cms.settings` ACL, new nav entry) — switch the rich-text editor at runtime.
- **`cms:setup`** — imports `cms.js` into `resources/js/app.js`, adds the TinyMCE `viteStaticCopy` target, and asks for the default editor. Idempotent.
- **`CmsDownload`/`CmsDownloadFile` model factories** (`CmsDownloadFactory`, `CmsDownloadFileFactory`) — both models declared `HasFactory` but had no factory class, unlike every other content type in this package.
- **`tests/Feature/DownloadTest.php` and `DownloadFileTest.php`** — the remaining test-coverage gap for `download-list`/`download-form`/`download-file-manager`, mirroring `AlbumTest`/`AlbumImageTest`'s structure: model factory, page rendering, create/update/delete via Livewire, ACL, and fallback-route sync (confirming `access` does not gate fallback registration, only `status` does — unlike `CmsAlbum` where `type` gates it).
- `HasTranslations::translatedMetaDescription()` (also on `TranslatableContent`): this locale's own `meta_description`, else the translated excerpt/description as plain text (160 chars). The page, article, section, category, album and download viewers pass it to the layout via `->layoutData(['description' => …])`.
- `resources/js/cms-frontend.js` — the public search box's `cmsSearchBox` component, split out of `cms.js`.

### Changed
- The page **Show Child Pages** toggle is replaced by the **Child Pages Display** select. Existing `show_children: true` pages keep showing cards (read as `children_display: cards`); the old key is no longer written. Custom page-viewer templates should check `$page->childrenDisplay() === 'cards'` instead of `config['show_children']`.
- **Download file descriptions are rich text** — edited with `<x-cms::rte>`, purified with `cms_rte` on save (including translations) and rendered as HTML on the download page. Upgrade: run `php artisan cms:sanitize-content` once to clean existing descriptions (now also covered by that command). Custom download templates must output `{!! $file->renderWithMediaGalleries('description') !!}` instead of `{{ $file->translate('description') }}`.
- **Downloads: `access` replaced by `visibility`** — existing *private* (login required) downloads become *protected*. `CmsDownloadService::getFrontendList()` no longer takes `$includePrivate` (it filters for the current visitor), and the backend list filter is now `filterVisibility`.
- **Search index and sitemap list only effectively public content** (previously only private downloads were excluded). Changing a page's or section's visibility re-syncs its descendants and articles.
- **Delete-by-token service methods** (`deleteByToken`, `deleteSectionByToken`, `deleteCategoryByToken`, `deleteArticleByToken`, `deleteAlbumByToken`, `deleteDownloadByToken`) accept an optional `?Closure $authorize`, called with the model before it is deleted.
- **Article form**: the article always stays in the section from the route; a tampered `form.section_id` is ignored.
- **`cms_rte` purifier: alignment + local video, media local-only** — `text-align` is now the one allowed CSS property (on `p`, `h1`–`h6`, `div`); `<video>`/`<source>` are allowed; `URI.DisableExternalResources` + `URI.Host` (from `app.url`) mean embedded images and videos must be local (File Manager / same host). **Existing external `<img>` are stripped on next save or `cms:sanitize-content`.** Iframes stay blocked. `CmsServiceProvider` retypes mews/purifier's html5 `video`/`source` `src`/`poster` as embedded URIs (they were plain link URIs, so external video sources slipped past the local-only rule) and bumps the definition `rev` to refresh HTMLPurifier's cache.
- **TinyMCE toolbar**: `formatselect` → `blocks` (TinyMCE 7 name — the heading dropdown was silently missing); the media dialog's FM picker now asks for `video/*`.
- **Commands dropped the `mod-` prefix**: `mod-cms:patch-resources` → `cms:patch-resources` (old name kept as alias).
- **`cms:patch-resources` is deprecated** and forwards to `cms:setup`. CMS JS now loads from the backend entry `resources/js/app.js`; `cms:setup` removes the old import from `synapps/resources/js/synapps.js`. The old vite anchor never matched the current `vite.config.js` layout — setup now inserts at the start of the `viteStaticCopy` targets.
- `declare(strict_types=1)` added to the remaining 44 files (factories, migrations, lang, routes, tests) — every PHP file in the package is now strict.
- **Requires `vm-engine/synapse` ^3.0** (was `^2.1|^3.0`) and allows `vm-engine/synapps-auth` ^3.0.
- **PHPStan config trimmed to zero ignores** — the blanket `ignoreErrors` patterns and empty baseline are gone; `src/` passes level 7 without them under current Larastan.
- **PHPStan level 7 gate added** (package-local `phpstan.neon`, empty baseline). `src/` hardened: PHPDoc generics/iterable value types across models, enums, Livewire form objects and services; `orderBy` direction is now validated by a shared `normalizeDirection()` helper (falls back to `asc` for anything other than `asc`/`desc`); activity logging skips unauthenticated users instead of passing `null`; `PatchResourcesCommand` guards unreadable files.
- **Semantic CSS migration across the 8 highest-density backend views** (`page-list`, `page-form`, `article-form`, `article-list`, `album-image-manager`, `download-file-manager`, `download-list`, `album-list`) — inline Tailwind utility soup replaced with `vm-engine/synapse`'s `<x-synapse-panel>` (+ `toolbar` slot), `<x-synapse-search-box>`, and `<x-synapse-badge>` wherever the markup matched, plus a new `.syn-form-action-bar` class (added to `vm-engine/synapse`) for the fixed bottom save bar shared by `page-form`/`article-form`. No visual redesign, no behavior change. 6 of the 8 components already had test coverage via `PageTest`/`ArticleTest`/`AlbumTest`/`AlbumImageTest` (all still pass, no regressions); the two genuinely uncovered ones (`download-list`, `download-file-manager`) are now covered — see below.
- `cms:setup` now also imports `cms-frontend.js` into `resources/js/frontend.js` (`cms.js` stays in `app.js`), so public pages get the search box without SortableJS or the editor loaders; warns with the manual import line when `frontend.js` is missing.
- Frontend and widget templates use `cms::labels` for UI text (breadcrumb "Home"/aria-label, "Featured", ":minutes min read", lightbox and banner-slider control labels, download file fallback) and `translatedFormat()` for dates, so translated pages show localized chrome and month names.

### Removed
- `DownloadAccess` enum and the `CmsDownload::publicAccess()` scope (use `CmsVisibility` and `visibleTo()`).

### Fixed
- The section index, category index and download viewer templates wrapped their root element in `@if`, so Livewire mounted the component on the first child (header/breadcrumb) and any `wire:` directive further down the page was ignored. The root element is now unconditional.
- **Album lightbox and banner slider broken under the CSP Alpine build** — the album viewer's caption bar used `images[current]?.title`/`?.caption` (optional chaining is rejected by the CSP parser: 4 console errors on every album page), and both the album viewer and the banner slider passed their data with `@js()`, which emits `JSON.parse(...)` for non-empty data — the CSP evaluator has no `JSON` global, so the whole lightbox/slider failed as soon as it had images. Data now goes through `{{ json_encode() }}` and the caption bar through `currentTitle()`/`currentCaption()` methods.
- **Deleting album images, download files and menu items did nothing** — their delete buttons passed `parseInt($el.dataset.id)` in an Alpine attribute; the CSP evaluator has no globals ("Undefined variable: parseInt"), so the confirm dialog never opened. The id is now rendered server-side. New `FrontendAlpineCspTest` guards every CMS view against `?.`/`??`/arrow functions/`@js()` in frontend templates and JS globals in Alpine attributes.
- **`<livewire:cms::frontend.menu>` crashed on every render** ("Undefined variable $template") — it shares its view with `<x-cms::menu>`, which gained `template`/`with` props the Livewire component never passed.
- **`.form-label`/`.form-error` had no CSS definition anywhere** (added in `vm-engine/synapse`'s `synapse-components.css`) — labels and validation error messages across ~19 backend form/list views were rendering completely unstyled. No Blade changes needed; purely a missing-CSS fix under the class names already in use everywhere.
- **`!border-red-500` replaced with the established `.has-error` class** (15 occurrences across `album-form`, `category-form`, `download-form`, `section-form`, `menu-item-manager`'s 3 sub-forms, `menu-list`, `tag-list`, `page-form`, `article-form`) — matches the themed error-input color used everywhere else in the app instead of a blunt important-override.
- Migrated the remaining `<x-synapse-select>` usages (removed from `vm-engine/synapse` v3.0) to `<x-synapse-adv-select>` across 15 backend views (albums, articles, categories, downloads, menu items, pages, sections) — these views were throwing unknown-component errors. `AlbumType::options()`, `DownloadAccess::options()`, `CmsArticleService::getSectionOptions()`/`getCategoryOptions()`, `CmsPageService::getParentOptions()`, and the various list/form components' option-source methods reshaped to the value/label array format the new component requires.
- **CSP-safe Alpine.js compatibility.** Backend views (`album-form`, `article-form`, `category-form`, `download-form`, `page-form`, `section-form`, `album-image-manager`, `menu-list`, `menu-item-manager`, `download-file-manager`, `tag-list`), the `album-viewer` frontend widget, `banner-slider` widget, and `tinymce` component migrated off inline `x-data="{ ... }"` object literals with methods and multi-statement directive expressions to the `Alpine.data()` registry pattern (required by `vm-engine/synapse` ^3.0's new default CSP-safe Alpine build), guarded against the `alpine:init`/`wire:navigate` timing race. Fixed a pre-existing bug in `tag-list.blade.php` where `{{ '!$form->tag' }}` output the literal string `!$form->tag` instead of a real PHP negation.
- Public search box was dead on frontend pages: `cmsSearchBox` lived in `cms.js`, which `cms:setup` only wired into the admin `app.js`.
- `CmsScoutSearchable::searchVisible()` had two stacked docblocks, so its `LengthAwarePaginator<int, CmsSearchable>` return type was lost.

### Security
- **The `cms_rte` Purifier profile documented as shipped in 1.1.12 was never actually wired up** — `config/cms-purifier.php` existed but `CmsServiceProvider` never merged it into `config('purifier.settings.cms_rte')`, so every `Purifier::clean($data, 'cms_rte')` call (article/page/category body and description sanitization, `cms:sanitize-content`) silently fell back to Purifier's default HTML profile instead of the strict allowlist (no inline styles, no event handlers, no `<script>`/`<iframe>`, `http`/`https`/`mailto` URIs only) described in that release. Added the missing `mergeConfigFrom()` call.

## [1.1.12] - 2026-08-04

### Security
- **RTE/description content is now sanitized on save** — `CmsArticle.body`, `CmsPage.body`, `CmsSection.description`, and `CmsCategory.description` were previously persisted with no HTML sanitization and rendered unescaped (`{!! !!}`) on the frontend, allowing stored XSS via `<script>`, `on*` event handlers, `style` attributes, and `javascript:`/`data:` URIs. Added `mews/purifier` with a dedicated `cms_rte` profile (strict tag/attribute allowlist, no inline styles, no event handlers, `http`/`https`/`mailto` URIs only) and wired it into `CmsArticleService` (article, section, category) and `CmsPageService` (create/update).
- Added `cms:sanitize-content` (supports `--dry-run`) to re-clean existing rows saved before this fix.

## [1.1.11] - 2026-07-22

### Fixed
- **Mobile-responsive backend list toolbars** — `album-list`, `article-list`, `category-list`, `download-list`, `page-list`, and `section-list` header toolbars (create button + search input) now stack into a column and go full-width on small screens instead of overflowing horizontally.

### Documentation
- `README.md`/`CLAUDE.md` caught up to already-shipped functionality: the Downloads content type, custom view/layout override cascade (`resolveCustomView()`/`resolveCustomLayout()`, `FrontendViewResolver`), the `CmsPageService`/`CmsArticleService`/`CmsGalleryService`/`CmsDownloadService`/`CmsActivityLogger` domain services (introduced v1.1.7), the `<x-cms::widget.gallery>` widget, `template`/`card` override props across widgets, and the `<x-cms::menu>` `template` prop.

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
