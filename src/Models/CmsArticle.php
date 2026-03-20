<?php

declare(strict_types=1);

namespace VmEngine\Cms\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use VmEngine\Cms\Enums\ArticleStatus;
use VmEngine\Cms\Factories\CmsArticleFactory;
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
 * @property Carbon|null $published_at
 * @property Carbon|null $expires_at
 * @property bool $is_featured
 * @property int $sort_order
 * @property int $read_time
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $og_image
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read CmsSection $section
 * @property-read CmsCategory|null $category
 * @property-read Collection<int, CmsTag> $tags
 */
class CmsArticle extends Model
{
    use HasFactory;
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
        'published_at',
        'expires_at',
        'is_featured',
        'sort_order',
        'read_time',
        'meta_title',
        'meta_description',
        'og_image',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => ArticleStatus::class,
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

    #[Scope]
    protected function featured(Builder $builder): void
    {
        $builder->where('is_featured', true);
    }

    #[Scope]
    protected function search(Builder $builder, string $q): void
    {
        $builder->whereAny(['title', 'slug', 'excerpt'], 'like', "%{$q}%");
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CmsSection::class, 'section_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CmsCategory::class, 'category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(CmsTag::class, 'cms_article_tag', 'article_id', 'tag_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'updated_by');
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
