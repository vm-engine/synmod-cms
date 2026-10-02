<?php

declare(strict_types=1);

namespace VmEngine\Cms\Traits;

use Illuminate\Support\Facades\DB;
use VmEngine\Cms\Models\CmsTranslation;
use VmEngine\Cms\Support\TranslatableContent;

/**
 * Translation state for a Livewire form object editing a TranslatableContent
 * model: wire inputs to `form.translations.{locale}.{field}`.
 */
trait HasTranslationFields
{
    /** @var array<string, array<string, string>> */
    public array $translations = [];

    protected function fillTranslations(TranslatableContent $model): void
    {
        $this->translations = $model->translationValues();
    }

    /**
     * Each translatable field's base rule, made optional (only the primary
     * locale is ever required).
     *
     * @param  array<string, string>  $rules
     * @return array<string, string>
     */
    protected function translationRules(array $rules, TranslatableContent $model): array
    {
        $translationRules = ['translations' => 'array'];

        foreach ($model->translatableFields() as $field) {
            $base = array_diff(explode('|', $rules[$field] ?? 'string'), ['required', 'nullable', 'sometimes']);
            $translationRules["translations.*.{$field}"] = implode('|', ['nullable', ...$base]);
        }

        return $translationRules;
    }

    /**
     * Name translated fields by their label ("Title") instead of the raw key
     * ("translations.id.title") in validation messages.
     *
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        $attributes = [];

        foreach (CmsTranslation::FIELDS as $field) {
            $attributes["translations.*.{$field}"] = (string) __("cms::labels.{$field}");
        }

        return $attributes;
    }

    /**
     * Run the service create/update and write the translations atomically.
     *
     * @template T of TranslatableContent
     *
     * @param  callable(): T  $save
     * @return T
     */
    protected function saveWithTranslations(callable $save): TranslatableContent
    {
        return DB::transaction(function () use ($save): TranslatableContent {
            $model = $save();
            $model->setTranslations($this->translations);

            return $model;
        });
    }
}
