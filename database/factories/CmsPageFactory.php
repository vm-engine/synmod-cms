<?php

namespace VmEngine\Cms\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use VmEngine\Cms\Models\CmsPage;

class CmsPageFactory extends Factory
{
    protected $model = CmsPage::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(3);
        $slug = Str::slug($title);

        return [
            'parent_id' => null,
            'title' => $title,
            'slug' => $slug,
            'full_slug' => $slug,
            'body' => $this->faker->paragraphs(3, true),
            'excerpt' => $this->faker->sentence(),
            'status' => 'published',
            'published_at' => now()->subDays(rand(1, 30)),
            'sort_order' => rand(0, 10),
            'config' => [
                'show_title' => true,
                'show_breadcrumb' => true,
                'show_children' => false,
                'children_layout' => 'grid',
            ],
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft', 'published_at' => null]);
    }

    public function published(): static
    {
        return $this->state(['status' => 'published', 'published_at' => now()->subDays(rand(1, 30))]);
    }
}
