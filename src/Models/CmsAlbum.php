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
use VmEngine\Cms\Enums\AlbumType;
use VmEngine\Cms\Factories\CmsAlbumFactory;
use VmEngine\Synapse\Traits\WithDeleteToken;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string|null $cover_image
 * @property AlbumType $type
 * @property string $status
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
class CmsAlbum extends Model
{
    use HasFactory;
    use WithDeleteToken;

    protected $table = 'cms_albums';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'cover_image',
        'type',
        'status',
        'sort_order',
        'meta_title',
        'meta_description',
        'config',
        'custom_view',
        'custom_layout',
        'created_by',
    ];

    protected $casts = [
        'type' => AlbumType::class,
        'sort_order' => 'integer',
        'config' => 'array',
    ];

    protected static function newFactory(): CmsAlbumFactory
    {
        return CmsAlbumFactory::new();
    }

    #[Scope]
    protected function active(Builder $builder): void
    {
        $builder->where('status', 'active');
    }

    #[Scope]
    protected function normal(Builder $builder): void
    {
        $builder->where('type', AlbumType::Normal->value);
    }

    #[Scope]
    protected function banner(Builder $builder): void
    {
        $builder->where('type', AlbumType::Banner->value);
    }

    #[Scope]
    protected function search(Builder $builder, string $q): void
    {
        $builder->whereAny(['title', 'slug', 'description'], 'like', "%{$q}%");
    }

    public function images(): HasMany
    {
        return $this->hasMany(CmsAlbumImage::class, 'album_id')->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
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
