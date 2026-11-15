<?php

declare(strict_types=1);

namespace VmEngine\Cms\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use VmEngine\Cms\Models\CmsBuilderPage;

/**
 * @extends Factory<CmsBuilderPage>
 */
class CmsBuilderPageFactory extends Factory
{
    protected $model = CmsBuilderPage::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->words(3, true);

        return [
            'title' => ucfirst($title),
            'slug' => Str::slug($title),
            'status' => 'draft',
            'template' => 'full',
            'layout_draft' => ['regions' => []],
            'layout' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'published',
            'published_at' => now(),
            'layout' => $attributes['layout_draft'] ?? ['regions' => []],
        ]);
    }
}
