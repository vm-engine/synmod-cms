<?php

declare(strict_types=1);

namespace VmEngine\Cms\Livewire\Forms;

use Livewire\Form;
use VmEngine\Cms\Models\CmsTag;
use VmEngine\Cms\Services\CmsArticleService;

class TagFormObject extends Form
{
    public ?CmsTag $tag = null;

    public string $name = '';

    public string $slug = '';

    public function setTag(CmsTag $tag): void
    {
        $this->tag = $tag;
        $this->name = $tag->name;
        $this->slug = $tag->slug;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|regex:/^[a-z0-9-]+$/|unique:cms_tags,slug'.($this->tag ? ','.$this->tag->id : ''),
        ];
    }

    public function store(): CmsTag
    {
        $this->validate();

        $tag = app(CmsArticleService::class)->createTag([
            'name' => $this->name,
            'slug' => $this->slug,
        ]);

        $this->reset();

        return $tag;
    }

    public function update(): void
    {
        $this->validate();

        app(CmsArticleService::class)->updateTag($this->tag, [
            'name' => $this->name,
            'slug' => $this->slug,
        ]);
    }
}
