<?php

declare(strict_types=1);

namespace VmEngine\Cms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use VmEngine\Cms\Factories\CmsMediaGalleryImageFactory;
use VmEngine\Cms\Support\TranslatableContent;
use VmEngine\Cms\Traits\HasTranslations;
use VmEngine\Fm\Models\FmFile;

/**
 * @property int $id
 * @property int $gallery_id
 * @property int|null $file_id
 * @property string|null $caption
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read CmsMediaGallery $gallery
 * @property-read FmFile|null $file
 */
class CmsMediaGalleryImage extends Model implements TranslatableContent
{
    /** @use HasFactory<CmsMediaGalleryImageFactory> */
    use HasFactory;

    use HasTranslations;

    protected $table = 'cms_media_gallery_images';

    protected $fillable = ['gallery_id', 'file_id', 'caption', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    protected static function newFactory(): CmsMediaGalleryImageFactory
    {
        return CmsMediaGalleryImageFactory::new();
    }

    /**
     * @return list<string>
     */
    public function translatableFields(): array
    {
        return ['caption'];
    }

    /**
     * @return BelongsTo<CmsMediaGallery, $this>
     */
    public function gallery(): BelongsTo
    {
        return $this->belongsTo(CmsMediaGallery::class, 'gallery_id');
    }

    /**
     * @return BelongsTo<FmFile, $this>
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(FmFile::class, 'file_id');
    }
}
