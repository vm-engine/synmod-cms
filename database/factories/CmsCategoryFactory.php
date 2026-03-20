<?php

namespace VmEngine\Cms\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsSection;

class CmsCategoryFactory extends Factory
{
    protected $model = CmsCategory::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->words(2, true);

        return [
            'section_id' => CmsSection::factory(),
            'title' => ucwords($title),
            'slug' => Str::slug($title),
            'description' => $this->faker->sentence(),
            'status' => 'published',
            'sort_order' => rand(0, 10),
            'inherit_config' => true,
        ];
    }
}
