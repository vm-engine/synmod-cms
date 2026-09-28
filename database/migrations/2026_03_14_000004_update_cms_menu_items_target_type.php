<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cms_menu_items')) {
            return;
        }

        // If target_type_new doesn't exist yet, add it and copy data
        if (! Schema::hasColumn('cms_menu_items', 'target_type_new')) {
            Schema::table('cms_menu_items', function (Blueprint $table) {
                $table->string('target_type_new')->default('url')->after('label');
            });

            DB::table('cms_menu_items')->update([
                'target_type_new' => DB::raw('"target_type"'),
            ]);
        }

        // Drop the old enum column if it still exists
        if (Schema::hasColumn('cms_menu_items', 'target_type')) {
            Schema::table('cms_menu_items', function (Blueprint $table) {
                $table->dropColumn('target_type');
            });
        }

        // Rename new column to the original name if rename hasn't happened yet
        if (Schema::hasColumn('cms_menu_items', 'target_type_new')) {
            Schema::table('cms_menu_items', function (Blueprint $table) {
                $table->renameColumn('target_type_new', 'target_type');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('cms_menu_items')) {
            return;
        }

        if (! Schema::hasColumn('cms_menu_items', 'target_type_old')) {
            Schema::table('cms_menu_items', function (Blueprint $table) {
                $table->enum('target_type_old', ['url', 'page', 'section', 'category', 'article', 'album', 'album_image', 'album_list'])->default('url')->after('label');
            });

            DB::table('cms_menu_items')->update([
                'target_type_old' => DB::raw('"target_type"'),
            ]);
        }

        if (Schema::hasColumn('cms_menu_items', 'target_type')) {
            Schema::table('cms_menu_items', function (Blueprint $table) {
                $table->dropColumn('target_type');
            });
        }

        if (Schema::hasColumn('cms_menu_items', 'target_type_old')) {
            Schema::table('cms_menu_items', function (Blueprint $table) {
                $table->renameColumn('target_type_old', 'target_type');
            });
        }
    }
};
