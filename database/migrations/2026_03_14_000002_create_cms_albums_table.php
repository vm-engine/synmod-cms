<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cms_albums')) {
            Schema::create('cms_albums', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('cover_image')->nullable();
                $table->enum('type', ['normal', 'banner'])->default('normal');
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->unsignedInteger('sort_order')->default(0);
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('cms_album_images')) {
            Schema::create('cms_album_images', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('album_id')->index();
                $table->unsignedBigInteger('file_id')->nullable()->index();
                $table->string('title')->nullable();
                $table->text('caption')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->foreign('album_id')->references('id')->on('cms_albums')->cascadeOnDelete();
                $table->foreign('file_id')->references('id')->on('fm_files')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_album_images');
        Schema::dropIfExists('cms_albums');
    }
};
