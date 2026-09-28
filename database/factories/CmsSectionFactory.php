<?php

declare(strict_types=1);

namespace VmEngine\Cms\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use VmEngine\Cms\Models\CmsSection;

class CmsSectionFactory extends Factory
{
    protected $model = CmsSection::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->words(2, true);

        return [
            'title' => ucwords($title),
            'slug' => Str::slug($title),
            'description' => $this->faker->paragraph(),
            'status' => 'published',
            'sort_order' => rand(0, 10),
            'config' => [
                'order_by' => 'published_at',
                'order_dir' => 'desc',
                'show_date' => true,
                'show_author' => true,
                'show_thumbnail' => true,
                'items_per_page' => 12,
            ],
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }
}
