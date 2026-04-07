<?php

declare(strict_types=1);

namespace VmEngine\Cms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use VmEngine\Fm\Models\FmFile;
use VmEngine\Synapse\Traits\WithDeleteToken;

/**
 * @property int $id
 * @property int $download_id
 * @property int|null $file_id
 * @property string|null $title
 * @property string|null $description
 * @property int $sort_order
 * @property array<string, mixed>|null $config
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read CmsDownload $download
 * @property-read FmFile|null $file
 * @property-read string $delete_token
 */
class CmsDownloadFile extends Model
{
    use HasFactory;
    use WithDeleteToken;

    protected $table = 'cms_download_files';

    protected $fillable = [
        'download_id',
        'file_id',
        'title',
        'description',
        'sort_order',
        'config',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'config' => 'array',
    ];

    public function download(): BelongsTo
    {
        return $this->belongsTo(CmsDownload::class, 'download_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(FmFile::class, 'file_id');
    }
}
