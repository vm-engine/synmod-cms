<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, string> table => default visibility */
    private array $tables = [
        'cms_pages' => 'public',
        'cms_sections' => 'public',
        'cms_articles' => 'inherit',
        'cms_albums' => 'public',
        'cms_downloads' => 'public',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table => $default) {
            if (! Schema::hasColumn($table, 'visibility')) {
                Schema::table($table, function (Blueprint $t) use ($default) {
                    $t->string('visibility', 16)->default($default)->after('status');
                });
            }
        }

        // Existing child pages follow their parent (all were public before).
        DB::table('cms_pages')->whereNotNull('parent_id')->update(['visibility' => 'inherit']);

        // Downloads: old "private" meant "login required" = protected.
        if (Schema::hasColumn('cms_downloads', 'access')) {
            DB::table('cms_downloads')->where('access', 'private')->update(['visibility' => 'protected']);
            Schema::table('cms_downloads', fn (Blueprint $t) => $t->dropColumn('access'));
        }

        if (! Schema::hasTable('cms_access_grants')) {
            Schema::create('cms_access_grants', function (Blueprint $table) {
                $table->id();
                $table->string('grantable_type');
                $table->unsignedBigInteger('grantable_id');
                $table->string('ability', 16);
                $table->string('principal_type', 8);
                $table->unsignedBigInteger('principal_id');
                $table->boolean('is_base')->default(false);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->unique(
                    ['grantable_type', 'grantable_id', 'ability', 'principal_type', 'principal_id'],
                    'cms_access_grants_unique'
                );
                $table->index(['principal_type', 'principal_id', 'ability'], 'cms_access_grants_principal');
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_access_grants');

        if (! Schema::hasColumn('cms_downloads', 'access')) {
            Schema::table('cms_downloads', function (Blueprint $t) {
                $t->enum('access', ['public', 'private'])->default('public')->after('status');
            });
            DB::table('cms_downloads')->where('visibility', '!=', 'public')->update(['access' => 'private']);
        }

        foreach (array_keys($this->tables) as $table) {
            if (Schema::hasColumn($table, 'visibility')) {
                Schema::table($table, fn (Blueprint $t) => $t->dropColumn('visibility'));
            }
        }
    }
};
