<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cms_downloads')) {
            Schema::create('cms_downloads', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('cover_image')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->enum('access', ['public', 'private'])->default('public');
                $table->unsignedInteger('sort_order')->default(0);
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->json('config')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('cms_download_files')) {
            Schema::create('cms_download_files', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('download_id')->index();
                $table->unsignedBigInteger('file_id')->nullable()->index();
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->json('config')->nullable();
                $table->timestamps();

                $table->foreign('download_id')->references('id')->on('cms_downloads')->cascadeOnDelete();
                $table->foreign('file_id')->references('id')->on('fm_files')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_download_files');
        Schema::dropIfExists('cms_downloads');
    }
};
