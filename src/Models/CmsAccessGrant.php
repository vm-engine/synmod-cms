<?php

declare(strict_types=1);

namespace VmEngine\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * One user/role entry on an item's view or manage list.
 *
 * @property int $id
 * @property string $grantable_type
 * @property int $grantable_id
 * @property string $ability 'view'|'manage'
 * @property string $principal_type 'user'|'role'
 * @property int $principal_id
 * @property bool $is_base Added by a dev; only devs may remove it.
 * @property int|null $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class CmsAccessGrant extends Model
{
    public const VIEW = 'view';

    public const MANAGE = 'manage';

    protected $table = 'cms_access_grants';

    protected $fillable = [
        'grantable_type',
        'grantable_id',
        'ability',
        'principal_type',
        'principal_id',
        'is_base',
        'created_by',
    ];

    protected $casts = [
        'grantable_id' => 'integer',
        'principal_id' => 'integer',
        'is_base' => 'boolean',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function grantable(): MorphTo
    {
        return $this->morphTo();
    }

    /** "user:5" / "role:2" — the key used by the access picker. */
    public function principalKey(): string
    {
        return $this->principal_type.':'.$this->principal_id;
    }
}
