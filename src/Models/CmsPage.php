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
use VmEngine\Cms\Factories\CmsPageFactory;
use VmEngine\Cms\Support\TranslatableContent;
use VmEngine\Cms\Traits\HasTranslations;
use VmEngine\Synapse\Traits\WithDeleteToken;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $title
 * @property string $slug
 * @property string $full_slug
 * @property string|null $body
 * @property string|null $excerpt
 * @property string|null $cover_image
 * @property string $status
 * @property Carbon|null $published_at
 * @property int $sort_order
 * @property array<string, mixed>|null $config
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $og_image
 * @property string|null $custom_view
 * @property string|null $custom_layout
 * @property int|null $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read CmsPage|null $parent
 * @property-read Collection<int, CmsPage> $children
 */
class CmsPage extends Model implements TranslatableContent
{
    /** @use HasFactory<CmsPageFactory> */
    use HasFactory;

    use HasTranslations;
    use WithDeleteToken;

    protected $table = 'cms_pages';

    protected $fillable = [
        'parent_id',
        'title',
        'slug',
        'full_slug',
        'body',
        'excerpt',
        'cover_image',
        'status',
        'published_at',
        'sort_order',
        'config',
        'meta_title',
        'meta_description',
        'og_image',
        'custom_view',
        'custom_layout',
        'created_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'config' => 'array',
        'sort_order' => 'integer',
    ];

    protected static function newFactory(): CmsPageFactory
    {
        return CmsPageFactory::new();
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
     * @param  Builder<CmsPage>  $builder
     */
    #[Scope]
    protected function published(Builder $builder): void
    {
        $builder->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * @param  Builder<CmsPage>  $builder
     */
    #[Scope]
    protected function search(Builder $builder, string $q): void
    {
        $this->whereLikeWithTranslations($builder, ['title', 'slug', 'excerpt'], $q);
    }

    /**
     * @param  Builder<CmsPage>  $builder
     */
    #[Scope]
    protected function roots(Builder $builder): void
    {
        $builder->whereNull('parent_id');
    }

    /**
     * @return BelongsTo<CmsPage, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'parent_id');
    }

    /**
     * @return HasMany<CmsPage, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(CmsPage::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDepth(): int
    {
        $depth = 0;
        $page = $this;
        while ($page->parent_id !== null) {
            $depth++;
            $page = $page->parent;
        }

        return $depth;
    }

    public function resolveCustomView(): ?string
    {
        return $this->custom_view
            ?: optional($this->parent)->resolveCustomView();
    }

    public function resolveCustomLayout(): ?string
    {
        return $this->custom_layout
            ?: optional($this->parent)->resolveCustomLayout();
    }

    public function computeFullSlug(): string
    {
        if ($this->parent_id === null) {
            return $this->slug;
        }

        $parent = CmsPage::find($this->parent_id);
        if ($parent === null) {
            return $this->slug;
        }

        return $parent->full_slug.'/'.$this->slug;
    }
}
