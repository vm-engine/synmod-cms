<?php

declare(strict_types=1);

namespace VmEngine\Cms\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsAlbumImage;

class CmsAlbumImageFactory extends Factory
{
    protected $model = CmsAlbumImage::class;

    public function definition(): array
    {
        return [
            'album_id' => CmsAlbum::factory(),
            'file_id' => null,
            'title' => $this->faker->words(3, true),
            'caption' => $this->faker->sentence(),
            'sort_order' => rand(0, 10),
        ];
    }
}
