<?php

namespace VmEngine\Cms\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use VmEngine\Cms\Enums\AlbumType;
use VmEngine\Cms\Models\CmsAlbum;

class CmsAlbumFactory extends Factory
{
    protected $model = CmsAlbum::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->words(3, true);

        return [
            'title' => ucwords($title),
            'slug' => Str::slug($title),
            'description' => $this->faker->sentence(),
            'type' => AlbumType::Normal,
            'status' => 'active',
            'sort_order' => rand(0, 10),
        ];
    }

    public function banner(): static
    {
        return $this->state(['type' => AlbumType::Banner]);
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }
}
