<?php

declare(strict_types=1);

namespace VmEngine\Cms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use VmEngine\Cms\Factories\CmsDownloadFileFactory;
use VmEngine\Cms\Support\CustomFieldContent;
use VmEngine\Cms\Support\TranslatableContent;
use VmEngine\Cms\Traits\HasCustomFields;
use VmEngine\Cms\Traits\HasMediaGalleries;
use VmEngine\Cms\Traits\HasTranslations;
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
class CmsDownloadFile extends Model implements CustomFieldContent, TranslatableContent
{
    use HasCustomFields;

    /** @use HasFactory<CmsDownloadFileFactory> */
    use HasFactory;

    use HasMediaGalleries;
    use HasTranslations;
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

    protected static function newFactory(): CmsDownloadFileFactory
    {
        return CmsDownloadFileFactory::new();
    }

    /**
     * @return list<string>
     */
    public function translatableFields(): array
    {
        return ['title', 'description'];
    }

    /**
     * @return list<string>
     */
    public function translatableHtmlFields(): array
    {
        return ['description'];
    }

    /**
     * @return BelongsTo<CmsDownload, $this>
     */
    public function download(): BelongsTo
    {
        return $this->belongsTo(CmsDownload::class, 'download_id');
    }

    /**
     * @return BelongsTo<FmFile, $this>
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(FmFile::class, 'file_id');
    }

    /** The container whose custom field definitions apply to this item. */
    public function fieldSchemaOwner(): ?CmsDownload
    {
        return $this->download;
    }
}
