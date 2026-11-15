<?php

declare(strict_types=1);

namespace VmEngine\Cms\Livewire\Forms;

use Closure;
use Livewire\Form;
use VmEngine\Cms\Models\CmsBuilderPage;
use VmEngine\Cms\Services\CmsBuilderPageService;
use VmEngine\Cms\Support\CmsBuilderTemplates;
use VmEngine\Cms\Traits\HasTranslationFields;

/**
 * Page builder page settings: title, URL, template, SEO (+ translations).
 * The layout itself is edited in the builder editor, not here.
 */
class BuilderPageFormObject extends Form
{
    use HasTranslationFields;

    public ?CmsBuilderPage $page = null;

    public string $title = '';

    public string $slug = '';

    public string $template = CmsBuilderTemplates::DEFAULT;

    public string $meta_title = '';

    public string $meta_description = '';

    public string $og_image = '';

    public function setPage(CmsBuilderPage $page): void
    {
        $this->page = $page;
        $this->title = $page->title;
        $this->slug = $page->slug;
        $this->template = $page->template;
        $this->meta_title = $page->meta_title ?? '';
        $this->meta_description = $page->meta_description ?? '';
        $this->og_image = $page->og_image ?? '';
        $this->fillTranslations($page);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+(\/[a-z0-9-]+)*$/',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (app(CmsBuilderPageService::class)->slugIsTaken((string) $value, $this->page?->id)) {
                        $fail(__('cms::builder.slug_taken'));
                    }
                },
            ],
            'template' => ['required', 'string', 'in:'.implode(',', array_keys(app(CmsBuilderTemplates::class)->all()))],
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'og_image' => 'nullable|string|max:255',
        ];

        $translationRules = $this->translationRules(
            ['title' => 'string|max:255', 'meta_title' => 'string|max:255', 'meta_description' => 'string|max:500'],
            $this->page ?? new CmsBuilderPage,
        );

        return [...$rules, ...$translationRules];
    }

    public function save(?int $userId = null): CmsBuilderPage
    {
        $this->validate();

        $data = [
            'title' => $this->title,
            'slug' => $this->slug,
            'template' => $this->template,
            'meta_title' => $this->meta_title !== '' ? $this->meta_title : null,
            'meta_description' => $this->meta_description !== '' ? $this->meta_description : null,
            'og_image' => $this->og_image !== '' ? $this->og_image : null,
        ];
        $service = app(CmsBuilderPageService::class);
        $existing = $this->page;

        /** @var CmsBuilderPage $page */
        $page = $this->saveWithTranslations(fn (): CmsBuilderPage => $existing
            ? $service->update($existing, $data)
            : $service->create($data, $userId));

        $this->page = $page;

        return $page;
    }
}
