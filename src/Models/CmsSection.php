<?php

declare(strict_types=1);

namespace VmEngine\Cms\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use VmEngine\Cms\Factories\CmsSectionFactory;
use VmEngine\Synapse\Traits\WithDeleteToken;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string|null $cover_image
 * @property string $status
 * @property int $sort_order
 * @property array|null $config
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $og_image
 * @property int|null $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, CmsCategory> $categories
 * @property-read Collection<int, CmsArticle> $articles
 */
class CmsSection extends Model
{
    use HasFactory;
    use WithDeleteToken;

    protected $table = 'cms_sections';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'cover_image',
        'status',
        'sort_order',
        'config',
        'meta_title',
        'meta_description',
        'og_image',
        'created_by',
    ];

    protected $casts = [
        'config' => 'array',
        'sort_order' => 'integer',
    ];

    protected static function newFactory(): CmsSectionFactory
    {
        return CmsSectionFactory::new();
    }

    #[Scope]
    protected function published(Builder $builder): void
    {
        $builder->where('status', 'published');
    }

    #[Scope]
    protected function search(Builder $builder, string $q): void
    {
        $builder->whereAny(['title', 'slug', 'description'], 'like', "%{$q}%");
    }

    public function categories(): HasMany
    {
        return $this->hasMany(CmsCategory::class, 'section_id')->orderBy('sort_order');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(CmsArticle::class, 'section_id')->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }
}
