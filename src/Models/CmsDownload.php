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
use VmEngine\Cms\Enums\DownloadAccess;
use VmEngine\Cms\Factories\CmsDownloadFactory;
use VmEngine\Cms\Support\TranslatableContent;
use VmEngine\Cms\Traits\HasTranslations;
use VmEngine\Synapse\Traits\WithDeleteToken;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string|null $cover_image
 * @property string $status
 * @property DownloadAccess $access
 * @property int $sort_order
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property array<string, mixed>|null $config
 * @property string|null $custom_view
 * @property string|null $custom_layout
 * @property int|null $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, CmsDownloadFile> $files
 */
class CmsDownload extends Model implements TranslatableContent
{
    /** @use HasFactory<CmsDownloadFactory> */
    use HasFactory;

    use HasTranslations;
    use WithDeleteToken;

    protected $table = 'cms_downloads';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'cover_image',
        'status',
        'access',
        'sort_order',
        'meta_title',
        'meta_description',
        'config',
        'custom_view',
        'custom_layout',
        'created_by',
    ];

    protected $casts = [
        'access' => DownloadAccess::class,
        'sort_order' => 'integer',
        'config' => 'array',
    ];

    protected static function newFactory(): CmsDownloadFactory
    {
        return CmsDownloadFactory::new();
    }

    /**
     * @return list<string>
     */
    public function translatableFields(): array
    {
        return ['title', 'description', 'meta_title', 'meta_description'];
    }

    /**
     * @param  Builder<CmsDownload>  $builder
     */
    #[Scope]
    protected function active(Builder $builder): void
    {
        $builder->where('status', 'active');
    }

    /**
     * @param  Builder<CmsDownload>  $builder
     */
    #[Scope]
    protected function publicAccess(Builder $builder): void
    {
        $builder->where('access', DownloadAccess::Public->value);
    }

    /**
     * @param  Builder<CmsDownload>  $builder
     */
    #[Scope]
    protected function search(Builder $builder, string $q): void
    {
        $this->whereLikeWithTranslations($builder, ['title', 'slug', 'description'], $q);
    }

    /**
     * @return HasMany<CmsDownloadFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(CmsDownloadFile::class, 'download_id')->orderBy('sort_order');
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
