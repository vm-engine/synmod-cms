<?php

declare(strict_types=1);

namespace VmEngine\Cms\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VmEngine\Cms\Models\CmsMenu;
use VmEngine\Cms\Models\CmsMenuItem;

class CmsMenuItemFactory extends Factory
{
    protected $model = CmsMenuItem::class;

    public function definition(): array
    {
        return [
            'menu_id' => CmsMenu::factory(),
            'parent_id' => null,
            'label' => ucwords($this->faker->words(2, true)),
            'target_type' => 'url',
            'target_id' => null,
            'target_url' => $this->faker->url(),
            'open_new_tab' => false,
            'sort_order' => rand(0, 10),
            'is_active' => true,
        ];
    }
}
