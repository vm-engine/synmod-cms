<?php

declare(strict_types=1);

namespace VmEngine\Cms\Models;

use App\Models\User;
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
 * @property array<string, mixed>|null $config
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $og_image
 * @property string|null $custom_view
 * @property string|null $custom_layout
 * @property string|null $custom_category_view
 * @property string|null $custom_article_view
 * @property int|null $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, CmsCategory> $categories
 * @property-read Collection<int, CmsArticle> $articles
 */
class CmsSection extends Model
{
    /** @use HasFactory<CmsSectionFactory> */
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
        'custom_view',
        'custom_layout',
        'custom_category_view',
        'custom_article_view',
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

    /**
     * @param  Builder<CmsSection>  $builder
     */
    #[Scope]
    protected function published(Builder $builder): void
    {
        $builder->where('status', 'published');
    }

    /**
     * @param  Builder<CmsSection>  $builder
     */
    #[Scope]
    protected function search(Builder $builder, string $q): void
    {
        $builder->whereAny(['title', 'slug', 'description'], 'like', "%{$q}%");
    }

    /**
     * @return HasMany<CmsCategory, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(CmsCategory::class, 'section_id')->orderBy('sort_order');
    }

    /**
     * @return HasMany<CmsArticle, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(CmsArticle::class, 'section_id')->orderBy('sort_order');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function resolveCustomView(): ?string
    {
        return $this->custom_view;
    }

    public function resolveCustomLayout(): ?string
    {
        return $this->custom_layout;
    }

    public function resolveCategoryView(): ?string
    {
        return $this->custom_category_view;
    }

    public function resolveArticleView(): ?string
    {
        return $this->custom_article_view;
    }
}
