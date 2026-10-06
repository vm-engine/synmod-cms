<?php

declare(strict_types=1);

namespace VmEngine\Cms\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Container form state for the dev-only media gallery switch. Non-devs
 * never change it: saves re-write the stored value, whatever they post.
 */
trait HasMediaGalleryFlag
{
    public bool $mediaGallery = false;

    protected function fillMediaGalleryFlag(Model $container): void
    {
        $this->mediaGallery = self::storedMediaGalleryFlag($container);
    }

    /**
     * Merge into the container's `config` on save.
     *
     * @return array{media_gallery: bool}
     */
    protected function mediaGalleryConfig(?Model $existing): array
    {
        return ['media_gallery' => is_dev() ? $this->mediaGallery : self::storedMediaGalleryFlag($existing)];
    }

    private static function storedMediaGalleryFlag(?Model $container): bool
    {
        $config = $container?->getAttribute('config');

        return is_array($config) && ($config['media_gallery'] ?? false) === true;
    }
}
