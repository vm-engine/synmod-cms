<?php

declare(strict_types=1);

namespace VmEngine\Cms\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use VmEngine\Cms\Factories\CmsBuilderPageFactory;
use VmEngine\Cms\Support\TranslatableContent;
use VmEngine\Cms\Traits\HasTranslations;
use VmEngine\Synapse\Traits\WithDeleteToken;

/**
 * A standalone public page composed of widgets placed in a theme template's regions.
 * `layout_draft` is what editors change; `layout` is the published copy visitors see.
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $status
 * @property string $template
 * @property array<string, mixed>|null $layout_draft
 * @property array<string, mixed>|null $layout
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $og_image
 * @property Carbon|null $published_at
 * @property int|null $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class CmsBuilderPage extends Model implements TranslatableContent
{
    /** @use HasFactory<CmsBuilderPageFactory> */
    use HasFactory;

    use HasTranslations;
    use WithDeleteToken;

    public const STATUSES = ['draft', 'published'];

    protected $table = 'cms_builder_pages';

    protected $fillable = [
        'title',
        'slug',
        'status',
        'template',
        'layout_draft',
        'layout',
        'meta_title',
        'meta_description',
        'og_image',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'layout_draft' => 'array',
        'layout' => 'array',
        'published_at' => 'datetime',
    ];

    protected static function newFactory(): CmsBuilderPageFactory
    {
        return CmsBuilderPageFactory::new();
    }

    /**
     * @return list<string>
     */
    public function translatableFields(): array
    {
        return ['title', 'meta_title', 'meta_description'];
    }

    /**
     * @return list<string>
     */
    public function translatableHtmlFields(): array
    {
        return [];
    }

    /** True while the editable draft differs from what visitors see. */
    public function hasUnpublishedChanges(): bool
    {
        return $this->layout_draft != $this->layout;
    }

    /**
     * @param  Builder<CmsBuilderPage>  $builder
     */
    #[Scope]
    protected function published(Builder $builder): void
    {
        $builder->where('status', 'published');
    }

    /**
     * @param  Builder<CmsBuilderPage>  $builder
     */
    #[Scope]
    protected function search(Builder $builder, string $q): void
    {
        $this->whereLikeWithTranslations($builder, ['title', 'slug'], $q);
    }
}
