<?php

declare(strict_types=1);

namespace VmEngine\Cms\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsDownloadFile;

class CmsDownloadFileFactory extends Factory
{
    protected $model = CmsDownloadFile::class;

    public function definition(): array
    {
        return [
            'download_id' => CmsDownload::factory(),
            'file_id' => null,
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'sort_order' => rand(0, 10),
        ];
    }
}
