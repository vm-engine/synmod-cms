<?php

declare(strict_types=1);

namespace VmEngine\Cms\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use VmEngine\Cms\Models\CmsMediaGallery;
use VmEngine\Cms\Services\CmsMediaGalleryRenderer;

/**
 * Content item (page, article, download file) that owns inline media
 * galleries. The feature switch lives on its container (root page,
 * section, download) as config['media_gallery'], set by devs only.
 */
trait HasMediaGalleries
{
    public static function bootHasMediaGalleries(): void
    {
        // Articles/download files removed by a section/download FK cascade fire no
        // event; `cms:prune-media-galleries` sweeps the galleries they leave behind.
        static::deleted(function (Model $model): void {
            CmsMediaGallery::where('owner_type', $model->getMorphClass())
                ->where('owner_id', $model->getKey())
                ->get()
                ->each(fn (CmsMediaGallery $gallery) => $gallery->delete());
        });
    }

    /** The container whose settings apply to this item (root page / section / download). */
    abstract public function fieldSchemaOwner(): ?Model;

    /**
     * @return MorphMany<CmsMediaGallery, $this>
     */
    public function mediaGalleries(): MorphMany
    {
        return $this->morphMany(CmsMediaGallery::class, 'owner')->orderBy('sort_order')->orderBy('id');
    }

    public function mediaGalleriesEnabled(): bool
    {
        $config = $this->fieldSchemaOwner()?->getAttribute('config');

        return is_array($config) && ($config['media_gallery'] ?? false) === true;
    }

    /** The (translated) rich-text field with its [gallery:key] tags rendered. */
    public function renderWithMediaGalleries(string $field): string
    {
        return app(CmsMediaGalleryRenderer::class)->render($this, (string) $this->translate($field));
    }
}
