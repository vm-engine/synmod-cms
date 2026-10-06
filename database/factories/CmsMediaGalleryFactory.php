<?php

declare(strict_types=1);

namespace VmEngine\Cms\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VmEngine\Cms\Models\CmsMediaGallery;
use VmEngine\Cms\Models\CmsPage;

/**
 * @extends Factory<CmsMediaGallery>
 */
class CmsMediaGalleryFactory extends Factory
{
    protected $model = CmsMediaGallery::class;

    public function definition(): array
    {
        return [
            'owner_type' => CmsPage::class,
            'owner_id' => CmsPage::factory(),
            'key' => $this->faker->unique()->slug(2),
            'title' => $this->faker->words(3, true),
            'layout' => 'slider',
            'sort_order' => 0,
        ];
    }

    public function grid(): static
    {
        return $this->state(['layout' => 'grid']);
    }
}
