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
use VmEngine\Cms\Factories\CmsCategoryFactory;
use VmEngine\Synapse\Traits\WithDeleteToken;

/**
 * @property int $id
 * @property int $section_id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string|null $cover_image
 * @property string $status
 * @property int $sort_order
 * @property bool $inherit_config
 * @property array<string, mixed>|null $config
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $og_image
 * @property string|null $custom_view
 * @property string|null $custom_layout
 * @property string|null $custom_article_view
 * @property int|null $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read CmsSection $section
 * @property-read Collection<int, CmsArticle> $articles
 */
class CmsCategory extends Model
{
    /** @use HasFactory<CmsCategoryFactory> */
    use HasFactory;

    use WithDeleteToken;

    protected $table = 'cms_categories';

    protected $fillable = [
        'section_id',
        'title',
        'slug',
        'description',
        'cover_image',
        'status',
        'sort_order',
        'inherit_config',
        'config',
        'meta_title',
        'meta_description',
        'og_image',
        'custom_view',
        'custom_layout',
        'custom_article_view',
        'created_by',
    ];

    protected $casts = [
        'inherit_config' => 'boolean',
        'config' => 'array',
        'sort_order' => 'integer',
    ];

    protected static function newFactory(): CmsCategoryFactory
    {
        return CmsCategoryFactory::new();
    }

    /**
     * @param  Builder<CmsCategory>  $builder
     */
    #[Scope]
    protected function published(Builder $builder): void
    {
        $builder->where('status', 'published');
    }

    /**
     * @param  Builder<CmsCategory>  $builder
     */
    #[Scope]
    protected function search(Builder $builder, string $q): void
    {
        $builder->whereAny(['title', 'slug', 'description'], 'like', "%{$q}%");
    }

    /**
     * @return BelongsTo<CmsSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(CmsSection::class, 'section_id');
    }

    /**
     * @return HasMany<CmsArticle, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(CmsArticle::class, 'category_id')->orderBy('sort_order');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return array<string, mixed>
     */
    public function effectiveConfig(): array
    {
        if ($this->inherit_config || empty($this->config)) {
            return $this->section->config ?? [];
        }

        return $this->config;
    }

    public function resolveCustomView(): ?string
    {
        return $this->custom_view
            ?: optional($this->section)->resolveCategoryView();
    }

    public function resolveCustomLayout(): ?string
    {
        return $this->custom_layout
            ?: optional($this->section)->resolveCustomLayout();
    }

    public function resolveArticleView(): ?string
    {
        return $this->custom_article_view
            ?: optional($this->section)->resolveArticleView();
    }
}
