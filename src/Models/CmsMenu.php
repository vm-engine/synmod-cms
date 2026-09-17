<?php

declare(strict_types=1);

namespace VmEngine\Cms\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use VmEngine\Cms\Factories\CmsMenuFactory;
use VmEngine\Synapse\Traits\WithDeleteToken;

/**
 * @property int $id
 * @property string $name
 * @property string $position
 * @property string|null $description
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, CmsMenuItem> $items
 */
class CmsMenu extends Model
{
    /** @use HasFactory<CmsMenuFactory> */
    use HasFactory;

    use WithDeleteToken;

    protected $table = 'cms_menus';

    protected $fillable = [
        'name',
        'position',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): CmsMenuFactory
    {
        return CmsMenuFactory::new();
    }

    /**
     * @return HasMany<CmsMenuItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CmsMenuItem::class, 'menu_id')->orderBy('sort_order');
    }

    /**
     * @return HasMany<CmsMenuItem, $this>
     */
    public function rootItems(): HasMany
    {
        return $this->hasMany(CmsMenuItem::class, 'menu_id')
            ->whereNull('parent_id')
            ->orderBy('sort_order');
    }
}
