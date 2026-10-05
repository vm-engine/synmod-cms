<?php

declare(strict_types=1);

namespace VmEngine\Cms\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;
use VmEngine\Cms\Models\CmsTranslation;
use VmEngine\Cms\Support\CmsLocale;

/**
 * Per-locale translations stored in cms_translations, falling back per field
 * to the model's own (primary-locale) columns.
 *
 * Read with translate(); never override getAttribute: backend forms must keep
 * reading the raw primary columns or a save would overwrite them.
 */
trait HasTranslations
{
    /**
     * Base columns that have a translation in cms_translations.
     *
     * @return list<string>
     */
    abstract public function translatableFields(): array;

    /**
     * Translatable fields holding rich-text HTML (purified with cms_rte on write).
     *
     * @return list<string>
     */
    public function translatableHtmlFields(): array
    {
        return [];
    }

    public static function bootHasTranslations(): void
    {
        static::deleted(function (): void {
            // ponytail: global orphan sweep on every delete — also catches DB-level
            // cascades (album→images, menu→items) that fire no events. Scope it
            // per type if deletes ever get hot.
            CmsTranslation::pruneOrphans();
        });
    }

    /**
     * When multi-language is on, eager-load translations with every query of
     * this model (and nested relations), and keep them out of toArray()/JSON
     * so activity-log diffs and payloads are unchanged.
     */
    public function initializeHasTranslations(): void
    {
        if (CmsLocale::isMultiLanguage() && ! in_array('translations', $this->with, true)) {
            $this->with[] = 'translations';
        }

        if (! in_array('translations', $this->hidden, true)) {
            $this->hidden[] = 'translations';
        }
    }

    /**
     * @return MorphMany<CmsTranslation, $this>
     */
    public function translations(): MorphMany
    {
        return $this->morphMany(CmsTranslation::class, 'translatable');
    }

    /**
     * The field's value in $locale (default: app locale). With $fallback (the
     * default) an empty translation falls back to the base column; without it,
     * a non-primary locale with no value returns null.
     */
    public function translate(string $field, ?string $locale = null, bool $fallback = true): ?string
    {
        $base = $this->getAttribute($field);
        $base = $base === null ? null : (string) $base;
        $locale ??= app()->getLocale();

        if ($locale === CmsLocale::primary()
            || ! CmsLocale::isMultiLanguage()
            || ! in_array($field, $this->translatableFields(), true)) {
            return $base;
        }

        $value = $this->loadedTranslations()->firstWhere('locale', $locale)?->getAttribute($field);

        if (is_string($value) && $value !== '') {
            return $value;
        }

        return $fallback ? $base : null;
    }

    /**
     * Page <title>: this locale's own meta_title, else its (fallback-resolved)
     * title — never the default language's meta_title on a translated page.
     */
    public function translatedMetaTitle(): string
    {
        return $this->translate('meta_title', fallback: false) ?: (string) $this->translate('title');
    }

    /**
     * <meta name="description">: this locale's own meta_description, else its
     * (fallback-resolved) excerpt/description as plain text (160 chars) —
     * never the default language's meta_description on a translated page.
     */
    public function translatedMetaDescription(): ?string
    {
        $meta = $this->translate('meta_description', fallback: false);

        if ($meta !== null && trim($meta) !== '') {
            return $meta;
        }

        foreach (array_intersect(['excerpt', 'description'], $this->translatableFields()) as $field) {
            $text = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $this->translate($field))));

            if ($text !== '') {
                return Str::limit($text, 160);
            }
        }

        return null;
    }

    /**
     * Upsert translations keyed by locale: ['id' => ['title' => ..., 'body' => ...]].
     * The primary locale and disabled locales are ignored; a locale whose
     * fields are all empty has its row deleted.
     *
     * @param  array<string, array<string, mixed>>  $byLocale
     */
    public function setTranslations(array $byLocale): void
    {
        $secondary = CmsLocale::secondary();
        $changed = false;

        foreach ($byLocale as $locale => $fields) {
            if (! in_array($locale, $secondary, true)) {
                continue;
            }

            $values = [];
            foreach ($this->translatableFields() as $field) {
                $values[$field] = $this->normalizeTranslationValue($field, $fields[$field] ?? null);
            }

            if (array_filter($values) === []) {
                $changed = $this->translations()->where('locale', $locale)->delete() > 0 || $changed;

                continue;
            }

            $translation = $this->translations()->updateOrCreate(['locale' => $locale], $values);
            $changed = $translation->wasRecentlyCreated || $translation->wasChanged() || $changed;
        }

        $this->unsetRelation('translations');

        // A translation-only edit leaves the base row clean, so no `updated`
        // event fires on its own — and touch() fires none either when
        // updated_at didn't change (same second as the base save). Bump the
        // timestamp quietly, then notify observers (fallback + search index) once.
        if ($changed && $this->exists) {
            $this->touchQuietly();
            $this->fireModelEvent('updated', false);
        }
    }

    /**
     * Stored secondary-locale values for form editing: [locale => [field => value]].
     *
     * @return array<string, array<string, string>>
     */
    public function translationValues(): array
    {
        $secondary = CmsLocale::secondary();
        $values = [];

        foreach ($this->loadedTranslations() as $translation) {
            if (! in_array($translation->locale, $secondary, true)) {
                continue;
            }

            foreach ($this->translatableFields() as $field) {
                $values[$translation->locale][$field] = (string) $translation->getAttribute($field);
            }
        }

        return $values;
    }

    /**
     * Backend search: LIKE over the base columns, OR over the same columns
     * in any translation, grouped so callers' other conditions still apply.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $builder
     * @param  list<string>  $columns
     */
    protected function whereLikeWithTranslations(Builder $builder, array $columns, string $term): void
    {
        $like = "%{$term}%";
        $translated = array_values(array_intersect($columns, $this->translatableFields()));
        $withTranslations = $translated !== [] && CmsLocale::isMultiLanguage();

        $builder->where(function (Builder $query) use ($columns, $translated, $like, $withTranslations): void {
            $query->whereAny($columns, 'like', $like);

            if ($withTranslations) {
                $query->orWhereHas('translations', fn (Builder $t) => $t->whereAny($translated, 'like', $like));
            }
        });
    }

    /**
     * @return Collection<int, CmsTranslation>
     */
    private function loadedTranslations(): Collection
    {
        if (! $this->relationLoaded('translations')) {
            $this->setRelation('translations', $this->translations()->get());
        }

        /** @var Collection<int, CmsTranslation> */
        return $this->getRelation('translations');
    }

    private function normalizeTranslationValue(string $field, mixed $value): ?string
    {
        $value = trim(is_scalar($value) ? (string) $value : '');

        if ($value !== '' && in_array($field, $this->translatableHtmlFields(), true)) {
            $value = trim((string) Purifier::clean($value, 'cms_rte'));

            // Editors post "<p><br></p>" for an empty document.
            if (trim(strip_tags($value)) === '' && ! preg_match('/<(img|video)\b/i', $value)) {
                $value = '';
            }
        }

        return $value === '' ? null : $value;
    }
}
