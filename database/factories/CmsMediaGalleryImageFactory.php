<?php

declare(strict_types=1);

namespace VmEngine\Cms\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VmEngine\Cms\Models\CmsMediaGallery;
use VmEngine\Cms\Models\CmsMediaGalleryImage;

/**
 * @extends Factory<CmsMediaGalleryImage>
 */
class CmsMediaGalleryImageFactory extends Factory
{
    protected $model = CmsMediaGalleryImage::class;

    public function definition(): array
    {
        return [
            'gallery_id' => CmsMediaGallery::factory(),
            'file_id' => null,
            'caption' => $this->faker->sentence(),
            'sort_order' => 0,
        ];
    }
}
