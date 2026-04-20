<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $columnsByTable = [
        'cms_pages' => ['custom_view', 'custom_layout'],
        'cms_sections' => ['custom_view', 'custom_layout', 'custom_category_view', 'custom_article_view'],
        'cms_categories' => ['custom_view', 'custom_layout', 'custom_article_view'],
        'cms_articles' => ['custom_view', 'custom_layout'],
        'cms_albums' => ['custom_view', 'custom_layout'],
        'cms_downloads' => ['custom_view', 'custom_layout'],
        'cms_menu_items' => ['custom_view', 'custom_layout'],
    ];

    public function up(): void
    {
        foreach ($this->columnsByTable as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table, $columns): void {
                foreach ($columns as $column) {
                    if (! Schema::hasColumn($table, $column)) {
                        $t->string($column)->nullable();
                    }
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->columnsByTable as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table, $columns): void {
                foreach ($columns as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $t->dropColumn($column);
                    }
                }
            });
        }
    }
};
