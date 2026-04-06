<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_articles', function (Blueprint $table): void {
            $table->json('config')->nullable()->after('og_image');
        });

        Schema::table('cms_albums', function (Blueprint $table): void {
            $table->json('config')->nullable()->after('meta_description');
        });

        Schema::table('cms_album_images', function (Blueprint $table): void {
            $table->json('config')->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('cms_articles', function (Blueprint $table): void {
            $table->dropColumn('config');
        });

        Schema::table('cms_albums', function (Blueprint $table): void {
            $table->dropColumn('config');
        });

        Schema::table('cms_album_images', function (Blueprint $table): void {
            $table->dropColumn('config');
        });
    }
};
