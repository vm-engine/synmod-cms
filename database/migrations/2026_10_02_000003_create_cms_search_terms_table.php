<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cms_search_terms')) {
            return;
        }

        Schema::create('cms_search_terms', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 10);
            $table->string('term', 100);
            $table->unsignedInteger('hits')->default(0);
            $table->boolean('is_hidden')->default(false);
            $table->timestamp('last_searched_at')->nullable();
            $table->timestamps();

            $table->unique(['locale', 'term']);
            $table->index(['locale', 'is_hidden', 'hits']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_search_terms');
    }
};
