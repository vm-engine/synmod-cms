# Strict Types Plan — synmod-cms

Same pattern as `packages/synapse` (done — see that package's `phpstan.neon`, now level 6, 0 errors, 503 tests green). Not implemented here yet — inventory only.

## Scope

- 129 in-repo PHP files (excludes vendor/build/.backups/blade views).
- 40 missing `declare(strict_types=1)`, concentrated in `tests/Feature` (13), `database/migrations` (9), `database/factories` (9).
- No `phpstan.neon` in this package — per `CLAUDE.md`, run PHPStan/Pint/Pest for this module **from the main Laravel project root**, not from inside `packages/synmod-cms/`. Main root's `phpstan.neon` already scans `packages/` at level 7 — this module has never been run against that level, so expect real findings, not just missing-declare noise.

## Steps (mirror the synapse execution)

1. Add `declare(strict_types=1);` to the 40 files above (mechanical, `<?php` blank-line insert).
2. `php -l` sweep + `php artisan test --compact packages/synmod-cms` (or the module's Pest suite path) to catch any coercion breakage the same way `is_decimal()`/`floor()` broke in synapse.
3. `vendor/bin/phpstan analyse packages/synmod-cms --error-format=raw --no-progress` from the main root at level 7 (matches the root config already in place — no level bump needed here since there's no separate package-level config to raise).
4. Fix findings root-cause style: prefer the shared `read_json_file()` helper (`packages/synapse/src/helper.php`) over ad-hoc `json_decode(file_get_contents(...), true)` if this module has that pattern — check first with `grep -rn "json_decode(file_get_contents" packages/synmod-cms`.
5. `vendor/bin/pint packages/synmod-cms --format agent` from main root.
6. Re-run this module's Pest suite to confirm green.

## Out of scope for this doc

Actually applying the fixes — this is inventory only, matching the CSS-plan precedent (`docs/SEMANTIC_CSS_PLAN.md`).
