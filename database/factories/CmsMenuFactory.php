<?php

declare(strict_types=1);

namespace VmEngine\Cms\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VmEngine\Cms\Models\CmsMenu;

class CmsMenuFactory extends Factory
{
    protected $model = CmsMenu::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'position' => $this->faker->randomElement(['header', 'footer', 'sidebar']),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
