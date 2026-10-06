<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cms_media_galleries')) {
            Schema::create('cms_media_galleries', function (Blueprint $table) {
                $table->id();
                $table->string('owner_type');
                $table->unsignedBigInteger('owner_id');
                $table->string('key', 100);
                $table->string('title');
                $table->string('layout', 20)->default('slider');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['owner_type', 'owner_id', 'key'], 'cms_media_galleries_owner_key_unique');
            });
        }

        if (! Schema::hasTable('cms_media_gallery_images')) {
            Schema::create('cms_media_gallery_images', function (Blueprint $table) {
                $table->id();
                $table->foreignId('gallery_id')->constrained('cms_media_galleries')->cascadeOnDelete();
                $table->unsignedBigInteger('file_id')->nullable()->index();
                $table->text('caption')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->foreign('file_id')->references('id')->on('fm_files')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_media_gallery_images');
        Schema::dropIfExists('cms_media_galleries');
    }
};
