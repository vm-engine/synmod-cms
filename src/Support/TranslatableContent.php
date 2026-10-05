<?php

declare(strict_types=1);

namespace VmEngine\Cms\Support;

/**
 * A CMS model whose content fields have per-locale translations
 * (implemented via the HasTranslations trait).
 */
interface TranslatableContent
{
    /**
     * @return list<string>
     */
    public function translatableFields(): array;

    /**
     * @return list<string>
     */
    public function translatableHtmlFields(): array;

    /**
     * @param  array<string, array<string, mixed>>  $byLocale
     */
    public function setTranslations(array $byLocale): void;

    /**
     * @return array<string, array<string, string>>
     */
    public function translationValues(): array;

    public function translatedMetaTitle(): string;

    public function translatedMetaDescription(): ?string;
}
