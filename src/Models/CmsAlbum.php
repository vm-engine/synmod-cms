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
use VmEngine\Cms\Enums\AlbumType;
use VmEngine\Cms\Enums\CmsVisibility;
use VmEngine\Cms\Factories\CmsAlbumFactory;
use VmEngine\Cms\Support\TranslatableContent;
use VmEngine\Cms\Traits\HasCmsAccess;
use VmEngine\Cms\Traits\HasTranslations;
use VmEngine\Synapse\Traits\WithDeleteToken;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string|null $cover_image
 * @property AlbumType $type
 * @property string $status
 * @property CmsVisibility $visibility
 * @property int $sort_order
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property array<string, mixed>|null $config
 * @property string|null $custom_view
 * @property string|null $custom_layout
 * @property int|null $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, CmsAlbumImage> $images
 */
class CmsAlbum extends Model implements TranslatableContent
{
    use HasCmsAccess;

    /** @use HasFactory<CmsAlbumFactory> */
    use HasFactory;
    use HasTranslations;
    use WithDeleteToken;

    protected $table = 'cms_albums';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'cover_image',
        'type',
        'status',
        'visibility',
        'sort_order',
        'meta_title',
        'meta_description',
        'config',
        'custom_view',
        'custom_layout',
        'created_by',
    ];

    protected $casts = [
        'visibility' => CmsVisibility::class,
        'type' => AlbumType::class,
        'sort_order' => 'integer',
        'config' => 'array',
    ];

    protected static function newFactory(): CmsAlbumFactory
    {
        return CmsAlbumFactory::new();
    }

    /**
     * @return list<string>
     */
    public function translatableFields(): array
    {
        return ['title', 'description', 'meta_title', 'meta_description'];
    }

    /**
     * @param  Builder<CmsAlbum>  $builder
     */
    #[Scope]
    protected function active(Builder $builder): void
    {
        $builder->where('status', 'active');
    }

    /**
     * @param  Builder<CmsAlbum>  $builder
     */
    #[Scope]
    protected function normal(Builder $builder): void
    {
        $builder->where('type', AlbumType::Normal->value);
    }

    /**
     * @param  Builder<CmsAlbum>  $builder
     */
    #[Scope]
    protected function banner(Builder $builder): void
    {
        $builder->where('type', AlbumType::Banner->value);
    }

    /**
     * @param  Builder<CmsAlbum>  $builder
     */
    #[Scope]
    protected function search(Builder $builder, string $q): void
    {
        $this->whereLikeWithTranslations($builder, ['title', 'slug', 'description'], $q);
    }

    /**
     * @return HasMany<CmsAlbumImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(CmsAlbumImage::class, 'album_id')->orderBy('sort_order');
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
}
