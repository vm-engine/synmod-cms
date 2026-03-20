<?php

namespace VmEngine\Cms\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use VmEngine\Cms\Enums\ArticleStatus;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsSection;

class CmsArticleFactory extends Factory
{
    protected $model = CmsArticle::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(4);
        $body = $this->faker->paragraphs(5, true);

        return [
            'section_id' => CmsSection::factory(),
            'category_id' => null,
            'title' => $title,
            'slug' => Str::slug($title),
            'body' => $body,
            'excerpt' => $this->faker->sentence(15),
            'status' => ArticleStatus::Published,
            'published_at' => now()->subDays(rand(1, 30)),
            'is_featured' => $this->faker->boolean(20),
            'sort_order' => rand(0, 10),
            'read_time' => CmsArticle::computeReadTime($body),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => ArticleStatus::Draft, 'published_at' => null]);
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }
}
