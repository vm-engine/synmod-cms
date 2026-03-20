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
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read CmsMenu $menu
 * @property-read CmsMenuItem|null $parentItem
 * @property-read Collection<int, CmsMenuItem> $children
 */
class CmsMenuItem extends Model
{
    use HasFactory;
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

    public function menu(): BelongsTo
    {
        return $this->belongsTo(CmsMenu::class, 'menu_id');
    }

    public function parentItem(): BelongsTo
    {
        return $this->belongsTo(CmsMenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(CmsMenuItem::class, 'parent_id')->orderBy('sort_order');
    }

    public function resolveUrl(): string
    {
        if ($this->target_type === 'url') {
            return $this->target_url ?? '#';
        }

        if ($this->target_type === 'album_list') {
            return '/gallery';
        }

        if ($this->target_id === null) {
            return '#';
        }

        return match ($this->target_type) {
            'page' => (function () {
                $page = CmsPage::find($this->target_id);

                return $page ? '/'.$page->full_slug : '#';
            })(),
            'section' => (function () {
                $section = CmsSection::find($this->target_id);

                return $section ? '/'.$section->slug : '#';
            })(),
            'category' => (function () {
                $cat = CmsCategory::with('section')->find($this->target_id);

                return $cat ? '/'.$cat->section->slug.'/'.$cat->slug : '#';
            })(),
            'article' => (function () {
                $art = CmsArticle::with(['section', 'category'])->find($this->target_id);
                if (! $art) {
                    return '#';
                }
                $base = '/'.$art->section->slug;
                if ($art->category_id) {
                    $base .= '/'.$art->category->slug;
                }

                return $base.'/'.$art->slug;
            })(),
            'album' => (function () {
                $album = CmsAlbum::find($this->target_id);

                return $album ? '/gallery/'.$album->slug : '#';
            })(),
            'album_image' => (function () {
                $image = CmsAlbumImage::with('album')->find($this->target_id);
                if (! $image) {
                    return '#';
                }

                return '/gallery/'.$image->album->slug.'#image-'.$image->id;
            })(),
            default => '#',
        };
    }
}
