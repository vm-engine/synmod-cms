<?php

declare(strict_types=1);

namespace VmEngine\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A public search term with its hit count, per locale (popular searches).
 *
 * @property int $id
 * @property string $locale
 * @property string $term
 * @property int $hits
 * @property bool $is_hidden
 * @property Carbon|null $last_searched_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class CmsSearchTerm extends Model
{
    protected $table = 'cms_search_terms';

    protected $fillable = ['locale', 'term', 'hits', 'is_hidden', 'last_searched_at'];

    protected $casts = [
        'hits' => 'integer',
        'is_hidden' => 'boolean',
        'last_searched_at' => 'datetime',
    ];
}
