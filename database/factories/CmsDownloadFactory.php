<?php

declare(strict_types=1);

namespace VmEngine\Cms\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use VmEngine\Cms\Models\CmsDownload;

class CmsDownloadFactory extends Factory
{
    protected $model = CmsDownload::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->words(3, true);

        return [
            'title' => ucwords($title),
            'slug' => Str::slug($title),
            'description' => $this->faker->sentence(),
            'status' => 'active',
            'access' => 'public',
            'sort_order' => rand(0, 10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }

    public function private(): static
    {
        return $this->state(['access' => 'private']);
    }
}
