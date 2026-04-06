<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add a temporary string column (no CHECK constraint)
        Schema::table('cms_menu_items', function (Blueprint $table) {
            $table->string('target_type_new')->default('url')->after('label');
        });

        // Copy existing values
        DB::table('cms_menu_items')->update([
            'target_type_new' => DB::raw('"target_type"'),
        ]);

        // Drop the old enum column (removes the CHECK constraint)
        Schema::table('cms_menu_items', function (Blueprint $table) {
            $table->dropColumn('target_type');
        });

        // Rename new column to the original name
        Schema::table('cms_menu_items', function (Blueprint $table) {
            $table->renameColumn('target_type_new', 'target_type');
        });
    }

    public function down(): void
    {
        Schema::table('cms_menu_items', function (Blueprint $table) {
            $table->enum('target_type_old', ['url', 'page', 'section', 'category', 'article', 'album', 'album_image', 'album_list'])->default('url')->after('label');
        });

        DB::table('cms_menu_items')->update([
            'target_type_old' => DB::raw('"target_type"'),
        ]);

        Schema::table('cms_menu_items', function (Blueprint $table) {
            $table->dropColumn('target_type');
        });

        Schema::table('cms_menu_items', function (Blueprint $table) {
            $table->renameColumn('target_type_old', 'target_type');
        });
    }
};
