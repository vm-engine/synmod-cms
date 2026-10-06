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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use VmEngine\Cms\Enums\ArticleStatus;
use VmEngine\Cms\Enums\CmsVisibility;
use VmEngine\Cms\Factories\CmsArticleFactory;
use VmEngine\Cms\Support\TranslatableContent;
use VmEngine\Cms\Traits\HasCmsAccess;
use VmEngine\Cms\Traits\HasTranslations;
use VmEngine\Synapse\Traits\WithDeleteToken;

/**
 * @property int $id
 * @property int $section_id
 * @property int|null $category_id
 * @property string $title
 * @property string $slug
 * @property string|null $body
 * @property string|null $excerpt
 * @property string|null $cover_image
 * @property ArticleStatus $status
 * @property CmsVisibility $visibility
 * @property Carbon|null $published_at
 * @property Carbon|null $expires_at
 * @property bool $is_featured
 * @property int $sort_order
 * @property int $read_time
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $og_image
 * @property array<string, mixed>|null $config
 * @property string|null $custom_view
 * @property string|null $custom_layout
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read CmsSection $section
 * @property-read CmsCategory|null $category
 * @property-read Collection<int, CmsTag> $tags
 */
class CmsArticle extends Model implements TranslatableContent
{
    use HasCmsAccess;

    /** @use HasFactory<CmsArticleFactory> */
    use HasFactory;
    use HasTranslations;
    use WithDeleteToken;

    protected $table = 'cms_articles';

    protected $fillable = [
        'section_id',
        'category_id',
        'title',
        'slug',
        'body',
        'excerpt',
        'cover_image',
        'status',
        'visibility',
        'published_at',
        'expires_at',
        'is_featured',
        'sort_order',
        'read_time',
        'meta_title',
        'meta_description',
        'og_image',
        'config',
        'custom_view',
        'custom_layout',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'visibility' => CmsVisibility::class,
        'status' => ArticleStatus::class,
        'config' => 'array',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'read_time' => 'integer',
    ];

    protected static function newFactory(): CmsArticleFactory
    {
        return CmsArticleFactory::new();
    }

    /**
     * @return list<string>
     */
    public function translatableFields(): array
    {
        return ['title', 'excerpt', 'body', 'meta_title', 'meta_description'];
    }

    /**
     * @return list<string>
     */
    public function translatableHtmlFields(): array
    {
        return ['body'];
    }

    /**
     * @param  Builder<CmsArticle>  $builder
     */
    #[Scope]
    protected function published(Builder $builder): void
    {
        $builder->where('status', ArticleStatus::Published->value)
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * @param  Builder<CmsArticle>  $builder
     */
    #[Scope]
    protected function featured(Builder $builder): void
    {
        $builder->where('is_featured', true);
    }

    /**
     * @param  Builder<CmsArticle>  $builder
     */
    #[Scope]
    protected function search(Builder $builder, string $q): void
    {
        $this->whereLikeWithTranslations($builder, ['title', 'slug', 'excerpt'], $q);
    }

    /**
     * @return BelongsTo<CmsSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(CmsSection::class, 'section_id');
    }

    /**
     * @return BelongsTo<CmsCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CmsCategory::class, 'category_id');
    }

    /**
     * @return BelongsToMany<CmsTag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(CmsTag::class, 'cms_article_tag', 'article_id', 'tag_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function resolveCustomView(): ?string
    {
        return $this->custom_view
            ?: optional($this->category)->resolveArticleView()
            ?: optional($this->section)->resolveArticleView();
    }

    public function resolveCustomLayout(): ?string
    {
        return $this->custom_layout
            ?: optional($this->category)->resolveCustomLayout()
            ?: optional($this->section)->resolveCustomLayout();
    }

    public static function computeReadTime(?string $body): int
    {
        if (empty($body)) {
            return 0;
        }
        $wordCount = str_word_count(strip_tags($body));

        return (int) max(1, ceil($wordCount / 200));
    }
}
