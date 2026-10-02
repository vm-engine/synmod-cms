<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cms_translations')) {
            return;
        }

        Schema::create('cms_translations', function (Blueprint $table) {
            $table->id();
            $table->string('translatable_type');
            $table->unsignedBigInteger('translatable_id');
            $table->string('locale', 10);
            $table->string('title')->nullable();
            $table->string('label')->nullable();
            $table->text('excerpt')->nullable();
            $table->text('description')->nullable();
            $table->text('caption')->nullable();
            $table->longText('body')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();

            $table->unique(['translatable_type', 'translatable_id', 'locale'], 'cms_translations_owner_locale_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_translations');
    }
};
