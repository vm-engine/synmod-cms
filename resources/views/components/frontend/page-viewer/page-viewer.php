<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Services\CmsAccessService;
use VmEngine\Cms\Services\CmsPageService;
use VmEngine\Cms\Support\CmsUrl;
use VmEngine\Cms\Support\FrontendViewResolver;
use VmEngine\Cms\Traits\WithFieldFilters;

new class extends Component
{
    use WithFieldFilters;

    public int $page_id;

    public ?CmsPage $page = null;

    public function mount(int $page_id): void
    {
        $this->page_id = $page_id;
        $this->page = app(CmsPageService::class)->findPublishedOrFailById(
            $page_id,
            ['parent'],
        );

        app(CmsAccessService::class)->authorizeView($this->page);
    }

    public function title(): string
    {
        return $this->page?->translatedMetaTitle() ?? '';
    }

    public function description(): ?string
    {
        return $this->page?->translatedMetaDescription();
    }

    /**
     * Own children when this page enables the sub-menu, else siblings when the parent does.
     *
     * @return array{owner: CmsPage, items: Collection<int, CmsPage>}|null
     */
    #[Computed()]
    public function submenu(): ?array
    {
        $owner = match (true) {
            $this->page === null => null,
            ($this->page->config['show_submenu'] ?? false) && $this->page->children()->exists() => $this->page,
            (bool) ($this->page->parent?->config['show_submenu'] ?? false) => $this->page->parent,
            default => null,
        };

        if ($owner === null) {
            return null;
        }

        $items = app(CmsAccessService::class)->filterVisiblePages($owner->children()->getQuery()->published()->get());

        return $items->isEmpty() ? null : ['owner' => $owner, 'items' => $items];
    }

    /** Anchor on the owner in one-page mode, else the child's own URL. */
    public function submenuUrl(CmsPage $owner, CmsPage $item): string
    {
        return $owner->childrenDisplay() === 'one_page'
            ? CmsUrl::to($owner).'#'.$item->childAnchor()
            : CmsUrl::to($item);
    }

    public function render()
    {
        $filter = $this->fieldFilter($this->page?->fieldSchemaOwner());
        if ($this->page !== null) {
            $children = $filter->apply($this->page->children()->getQuery()->published())->get();
            $this->page->setRelation('children', app(CmsAccessService::class)->filterVisiblePages($children));
        }

        $template = FrontendViewResolver::view(
            $this->page?->resolveCustomView(),
            'cms::components.frontend.page-viewer.page-viewer',
        );

        return view($template, ['page' => $this->page, 'fieldFilter' => $filter])
            ->title($this->title())
            ->layoutData(['description' => $this->description(), 'ogImage' => $this->page?->og_image ?: $this->page?->cover_image])
            ->layout(FrontendViewResolver::layout($this->page?->resolveCustomLayout()));
    }
};
