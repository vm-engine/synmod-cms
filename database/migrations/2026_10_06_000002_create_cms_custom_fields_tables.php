<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cms_fields')) {
            Schema::create('cms_fields', function (Blueprint $table) {
                $table->id();
                $table->string('owner_type');
                $table->unsignedBigInteger('owner_id');
                $table->string('name', 100);
                $table->string('label');
                $table->string('type', 20);
                $table->json('options')->nullable();
                $table->text('default')->nullable();
                $table->boolean('required')->default(false);
                $table->string('help', 500)->nullable();
                $table->boolean('searchable')->default(false);
                $table->boolean('filterable')->default(false);
                $table->boolean('sortable')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['owner_type', 'owner_id', 'name'], 'cms_fields_owner_name_unique');
            });
        }

        if (! Schema::hasTable('cms_field_values')) {
            Schema::create('cms_field_values', function (Blueprint $table) {
                $table->id();
                $table->foreignId('field_id')->constrained('cms_fields')->cascadeOnDelete();
                $table->string('valuable_type');
                $table->unsignedBigInteger('valuable_id');
                $table->string('locale', 10);
                $table->text('value')->nullable();
                $table->string('value_index', 191)->nullable();
                $table->decimal('value_number', 20, 6)->nullable();
                $table->dateTime('value_date')->nullable();
                $table->timestamps();

                $table->index(['valuable_type', 'valuable_id'], 'cms_field_values_valuable');
                $table->index(['field_id', 'locale', 'value_index'], 'cms_field_values_index');
                $table->index(['field_id', 'locale', 'value_number'], 'cms_field_values_number');
                $table->index(['field_id', 'locale', 'value_date'], 'cms_field_values_date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_field_values');
        Schema::dropIfExists('cms_fields');
    }
};
