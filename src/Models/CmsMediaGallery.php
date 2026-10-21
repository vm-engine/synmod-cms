<?php

declare(strict_types=1);

namespace VmEngine\Cms\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use VmEngine\Cms\Factories\CmsMediaGalleryFactory;
use VmEngine\Synapse\Traits\WithDeleteToken;

/**
 * An inline image set owned by one page, article or download file and
 * placed in its rich text with [gallery:key].
 *
 * @property int $id
 * @property string $owner_type
 * @property int $owner_id
 * @property string $key
 * @property string $title
 * @property string $layout
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, CmsMediaGalleryImage> $images
 */
class CmsMediaGallery extends Model
{
    /** @use HasFactory<CmsMediaGalleryFactory> */
    use HasFactory;

    use WithDeleteToken;

    public const LAYOUTS = ['slider', 'grid'];

    protected $table = 'cms_media_galleries';

    protected $fillable = ['owner_type', 'owner_id', 'key', 'title', 'layout', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    protected static function booted(): void
    {
        // Images go by FK cascade (no events), so sweep their caption translations here.
        static::deleted(fn () => CmsTranslation::pruneOrphans());
    }

    protected static function newFactory(): CmsMediaGalleryFactory
    {
        return CmsMediaGalleryFactory::new();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<CmsMediaGalleryImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(CmsMediaGalleryImage::class, 'gallery_id')->orderBy('sort_order')->orderBy('id');
    }

    public function tag(): string
    {
        return '[gallery:'.$this->key.']';
    }

    /**
     * Frontend-ready images (those whose FM file still exists), in order.
     *
     * @return list<array{url: string, caption: string, alt: string}>
     */
    public function slides(): array
    {
        $slides = [];

        foreach ($this->images as $image) {
            if ($image->file === null) {
                continue;
            }

            $caption = (string) $image->translate('caption');
            $slides[] = ['url' => $image->file->getUrl(), 'caption' => $caption, 'alt' => $caption !== '' ? $caption : $this->title];
        }

        return $slides;
    }
}
