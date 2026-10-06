<?php

declare(strict_types=1);

namespace VmEngine\Cms\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use VmEngine\Cms\Factories\CmsMenuItemFactory;
use VmEngine\Cms\Support\CmsUrl;
use VmEngine\Cms\Support\TranslatableContent;
use VmEngine\Cms\Traits\HasTranslations;
use VmEngine\Synapse\Traits\WithDeleteToken;

/**
 * @property int $id
 * @property int $menu_id
 * @property int|null $parent_id
 * @property string $label
 * @property string $target_type
 * @property int|null $target_id
 * @property string|null $target_url
 * @property bool $open_new_tab
 * @property int $sort_order
 * @property bool $is_active
 * @property string|null $custom_view
 * @property string|null $custom_layout
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read CmsMenu $menu
 * @property-read CmsMenuItem|null $parentItem
 * @property-read Collection<int, CmsMenuItem> $children
 */
class CmsMenuItem extends Model implements TranslatableContent
{
    /** @use HasFactory<CmsMenuItemFactory> */
    use HasFactory;

    use HasTranslations;
    use WithDeleteToken;

    protected $table = 'cms_menu_items';

    protected $fillable = [
        'menu_id',
        'parent_id',
        'label',
        'target_type',
        'target_id',
        'target_url',
        'open_new_tab',
        'sort_order',
        'is_active',
        'custom_view',
        'custom_layout',
    ];

    protected $casts = [
        'open_new_tab' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function newFactory(): CmsMenuItemFactory
    {
        return CmsMenuItemFactory::new();
    }

    /**
     * @return list<string>
     */
    public function translatableFields(): array
    {
        return ['label'];
    }

    /**
     * @return BelongsTo<CmsMenu, $this>
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(CmsMenu::class, 'menu_id');
    }

    /**
     * @return BelongsTo<CmsMenuItem, $this>
     */
    public function parentItem(): BelongsTo
    {
        return $this->belongsTo(CmsMenuItem::class, 'parent_id');
    }

    /**
     * @return HasMany<CmsMenuItem, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(CmsMenuItem::class, 'parent_id')->orderBy('sort_order');
    }

    public function resolveCustomView(): ?string
    {
        return $this->custom_view;
    }

    public function resolveCustomLayout(): ?string
    {
        return $this->custom_layout;
    }

    public function resolveUrl(): string
    {
        return match ($this->target_type) {
            'url' => $this->target_url !== null ? CmsUrl::localizeHref($this->target_url) : '#',
            'album_list' => CmsUrl::to($this->target_url ?: 'gallery'),
            'download_list' => CmsUrl::to($this->target_url ?: 'downloads'),
            default => $this->resolveContentUrl(),
        };
    }

    /** The content model this item links to, or null for url/list items and missing targets. */
    public function resolveTarget(): ?Model
    {
        if ($this->target_id === null) {
            return null;
        }

        return match ($this->target_type) {
            'page' => CmsPage::find($this->target_id),
            'section' => CmsSection::find($this->target_id),
            'category' => CmsCategory::with('section')->find($this->target_id),
            'article' => CmsArticle::with(['section', 'category'])->find($this->target_id),
            'album' => CmsAlbum::find($this->target_id),
            'album_image' => CmsAlbumImage::with('album')->find($this->target_id),
            'download' => CmsDownload::find($this->target_id),
            default => null,
        };
    }

    private function resolveContentUrl(): string
    {
        $target = $this->resolveTarget();

        if ($target === null) {
            return '#';
        }

        // Album images deep-link into the album lightbox (#item-{id}, as search results do).
        return CmsUrl::to($target, fragment: $target instanceof CmsAlbumImage ? 'item-'.$target->id : null);
    }
}
