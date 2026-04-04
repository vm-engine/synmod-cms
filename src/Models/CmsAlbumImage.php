<?php

declare(strict_types=1);

namespace VmEngine\Cms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use VmEngine\Cms\Factories\CmsAlbumImageFactory;
use VmEngine\Fm\Models\FmFile;
use VmEngine\Synapse\Traits\WithDeleteToken;

/**
 * @property int $id
 * @property int $album_id
 * @property int|null $file_id
 * @property string|null $title
 * @property string|null $caption
 * @property int $sort_order
 * @property array<string, mixed>|null $config
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read CmsAlbum $album
 * @property-read FmFile|null $file
 * @property-read string $delete_token
 */
class CmsAlbumImage extends Model
{
    use HasFactory;
    use WithDeleteToken;

    protected $table = 'cms_album_images';

    protected $fillable = [
        'album_id',
        'file_id',
        'title',
        'caption',
        'sort_order',
        'config',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'config' => 'array',
    ];

    protected static function newFactory(): CmsAlbumImageFactory
    {
        return CmsAlbumImageFactory::new();
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(CmsAlbum::class, 'album_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(FmFile::class, 'file_id');
    }
}
