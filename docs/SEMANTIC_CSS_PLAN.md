# Semantic CSS Plan — synmod-cms

> **Status (2026-09-27): done** for the 8 files below: they use `<x-synapse-panel>`, `<x-synapse-search-box>`, `<x-synapse-badge>` and the shared `.syn-form-action-bar` — no `syn-cms-*` classes or `cms-components.css` were needed, so the "Proposed convention" section is obsolete. `.form-label`/`.form-error` now have styles in synapse, and `!border-red-500` became `has-error`. Kept for history.

Same pattern as `packages/synapse` (see `.claude/plans/2026-08-25-synapse-semantic-css.md`).

## Candidate files (by inline utility-class density, top 8)

| File | `class=` occurrences |
| --- | --- |
| `resources/views/components/backend/page-list/page-list.blade.php` | 72 |
| `resources/views/components/backend/page-form/page-form.blade.php` | 56 |
| `resources/views/components/backend/article-form/article-form.blade.php` | 50 |
| `resources/views/components/backend/article-list/article-list.blade.php` | 41 |
| `resources/views/components/backend/album-image-manager/album-image-manager.blade.php` | 43 |
| `resources/views/components/backend/download-file-manager/download-file-manager.blade.php` | 41 |
| `resources/views/components/backend/download-list/download-list.blade.php` | 37 |
| `resources/views/components/backend/album-list/album-list.blade.php` | 36 |

Note: the `*-list`/`*-form` pairing (page, article, album, download) strongly suggests a shared list/form chrome — check whether `packages/synmod-example`'s `ExampleList`/`ExampleForm` components (the project's reference implementation) already cover these patterns via Synapse Blade components rather than custom CSS, before inventing new `syn-cms-*` classes for things that should just use `<x-synapse::...>`.

## Proposed convention
- Prefix: `syn-cms-*`.
- New file: `packages/synmod-cms/resources/css/cms-components.css`, wired the same way this module currently ships its `tinymce.css` asset (check `packages/synmod-cms/resources/css/` and how it's registered before assuming a new Vite entry is needed).

## Out of scope for this doc
Full class-by-class extraction — follow-up plan once confirmed.
