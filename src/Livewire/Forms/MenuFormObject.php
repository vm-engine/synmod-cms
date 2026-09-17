<?php

declare(strict_types=1);

namespace VmEngine\Cms\Livewire\Forms;

use Livewire\Form;
use VmEngine\Cms\Models\CmsMenu;
use VmEngine\Cms\Services\CmsMenuService;

class MenuFormObject extends Form
{
    public ?CmsMenu $menu = null;

    public string $name = '';

    public string $position = '';

    public string $description = '';

    public bool $is_active = true;

    public function setMenu(CmsMenu $menu): void
    {
        $this->menu = $menu;
        $this->name = $menu->name;
        $this->position = $menu->position;
        $this->description = $menu->description ?? '';
        $this->is_active = $menu->is_active;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:100|regex:/^[a-z0-9_-]+$/',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ];
    }

    public function store(): CmsMenu
    {
        $this->validate();

        $menu = app(CmsMenuService::class)->createMenu($this->toData());

        $this->reset();

        return $menu;
    }

    public function update(): void
    {
        $this->validate();

        app(CmsMenuService::class)->updateMenu($this->menu, $this->toData());
    }

    /**
     * @return array<string, mixed>
     */
    private function toData(): array
    {
        return [
            'name' => $this->name,
            'position' => $this->position,
            'description' => $this->description ?: null,
            'is_active' => $this->is_active,
        ];
    }
}
