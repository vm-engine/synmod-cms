<?php

declare(strict_types=1);

namespace VmEngine\Cms\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use VmEngine\Cms\Factories\CmsTagFactory;
use VmEngine\Synapse\Traits\WithDeleteToken;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, CmsArticle> $articles
 */
class CmsTag extends Model
{
    /** @use HasFactory<CmsTagFactory> */
    use HasFactory;

    use WithDeleteToken;

    protected $table = 'cms_tags';

    protected $fillable = [
        'name',
        'slug',
    ];

    protected static function newFactory(): CmsTagFactory
    {
        return CmsTagFactory::new();
    }

    /**
     * @return BelongsToMany<CmsArticle, $this>
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(CmsArticle::class, 'cms_article_tag', 'tag_id', 'article_id');
    }
}
