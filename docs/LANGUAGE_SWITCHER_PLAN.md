# Language Switcher Plan — synmod-cms

Frontend language switcher component. **Implemented 2026-10-02** — see README › Links & language switcher. Answers to the open questions below: names come from intl (`Locale::getDisplayName`) with `module.json` `language_names` overrides; the default is a native `<details>` dropdown with a `variant="list"` option (plus icons via `language_icons`). The bare `/{locale}` home and the `/` → `/{app.locale}` redirect live in synapps-fallback.

## Context

`synapps-fallback` is gaining locale-prefixed URLs (`/en/about-us`, `/id/about-us`) driven by the existing Synapse config:

- `synapps.apps.multi_language` — on/off
- `synapps.apps.enabled_languages` — valid URL prefixes
- `app.locale` — default locale (unprefixed registered slugs 301 to `/{app.locale}/...`)

The fallback middleware sets `app()->setLocale()` and `app('dblang')->setLanguage()` from the prefix. Backend URLs (`synapps.auth.routes.backend_prefix`) are never prefixed.

## Dependency

Build on `Fallback::url(string $slug, ?string $locale = null): string` from `synapps-fallback` (returns the prefixed URL when multi-language is on, plain `url($slug)` otherwise). Do not hand-build prefixes in this module.

## Link building migration (do first)

CMS currently hand-builds root-relative links, which will 301 to `app.locale` and drop the visitor's chosen language. Route all of them through `Fallback::url()`:

- ~20 hardcoded `href="/{{ ... }}"` links in 12 frontend/widget views (`grep -rn 'href="/' resources/views`)
- `src/Models/CmsMenuItem.php` URL resolver (`'/'.$slug` concatenation)
- `src/Services/CmsSitemapService.php` — emit one `<loc>` per enabled locale

No global `url()` override — explicit `Fallback::url()` calls only.

## Switcher scope

- Render only when `synapps.apps.multi_language` is true and more than one language is enabled.
- One link per `enabled_languages` entry, pointing to the current page's slug under that locale; mark the active locale (`app()->getLocale()`).
- Current slug = request path with the locale segment stripped.
- Frontend layouts only — never render in the backend layout.

## Open questions (decide when building)

- Language labels: native names (`English`, `Bahasa Indonesia`) — source from `dblang` or a static map?
- Dropdown vs inline list; placement in theme header.
