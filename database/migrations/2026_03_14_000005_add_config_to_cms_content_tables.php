<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cms_articles') && ! Schema::hasColumn('cms_articles', 'config')) {
            Schema::table('cms_articles', function (Blueprint $table): void {
                $table->json('config')->nullable()->after('og_image');
            });
        }

        if (Schema::hasTable('cms_albums') && ! Schema::hasColumn('cms_albums', 'config')) {
            Schema::table('cms_albums', function (Blueprint $table): void {
                $table->json('config')->nullable()->after('meta_description');
            });
        }

        if (Schema::hasTable('cms_album_images') && ! Schema::hasColumn('cms_album_images', 'config')) {
            Schema::table('cms_album_images', function (Blueprint $table): void {
                $table->json('config')->nullable()->after('sort_order');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cms_articles') && Schema::hasColumn('cms_articles', 'config')) {
            Schema::table('cms_articles', function (Blueprint $table): void {
                $table->dropColumn('config');
            });
        }

        if (Schema::hasTable('cms_albums') && Schema::hasColumn('cms_albums', 'config')) {
            Schema::table('cms_albums', function (Blueprint $table): void {
                $table->dropColumn('config');
            });
        }

        if (Schema::hasTable('cms_album_images') && Schema::hasColumn('cms_album_images', 'config')) {
            Schema::table('cms_album_images', function (Blueprint $table): void {
                $table->dropColumn('config');
            });
        }
    }
};
