<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cms_searchables')) {
            return;
        }

        Schema::create('cms_searchables', function (Blueprint $table) {
            $table->id();
            $table->string('searchable_type', 30);
            $table->unsignedBigInteger('searchable_id');
            $table->string('locale', 10);
            $table->string('title');
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('url', 2048);
            $table->string('image', 2048)->nullable();
            $table->string('parent_label')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['searchable_type', 'searchable_id', 'locale'], 'cms_searchables_entry_unique');
            $table->index(['locale', 'searchable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_searchables');
    }
};
