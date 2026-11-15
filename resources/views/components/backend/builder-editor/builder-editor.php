<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Access\Authorizable;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use VmEngine\Cms\Models\CmsBuilderPage;
use VmEngine\Cms\Services\CmsBuilderPageService;
use VmEngine\Cms\Support\CmsBuilderClassPalette;
use VmEngine\Cms\Support\CmsBuilderTemplates;
use VmEngine\Cms\Support\CmsWidgetRegistry;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

/*
 * Page builder editor shell: toolbar, the canvas iframe, the widget picker and the
 * settings drawer. The canvas posts "select/add/region/changed" messages; every
 * change here dispatches `cms-builder-refresh-canvas` so the iframe re-renders.
 */
new class extends Component
{
    #[Locked]
    public int $builderPageId;

    public string $template = '';

    /** Region the widget picker adds into. */
    public ?string $addRegion = null;

    public ?string $selectedWidgetId = null;

    /** @var array<string, mixed> */
    public array $widgetProps = [];

    /** @var list<string> */
    public array $widgetClasses = [];

    public string $widgetCustomClass = '';

    public string $widgetHeading = '';

    public bool $widgetShowHeading = true;

    public ?string $selectedRegion = null;

    /** @var list<string> */
    public array $regionClasses = [];

    public string $regionCustomClass = '';

    public function mount(int $id): void
    {
        $this->authorizeAbility('cms.builder.update');
        $this->builderPageId = $id;
        $this->template = $this->page->template;
        synav()->setActiveMenu('cms.builder');
    }

    #[Computed()]
    public function page(): CmsBuilderPage
    {
        return $this->service()->findOrFailById($this->builderPageId);
    }

    /**
     * @return array<string, string>
     */
    #[Computed()]
    public function templateOptions(): array
    {
        return array_map(fn (array $template): string => (string) __($template['name']), app(CmsBuilderTemplates::class)->all());
    }

    /**
     * @return array<string, array{label: string, icon: string, component: string, props: array<string, array<string, mixed>>}>
     */
    #[Computed()]
    public function widgets(): array
    {
        return app(CmsWidgetRegistry::class)->all();
    }

    /**
     * @return array<string, array{label: string, classes: list<string>}>
     */
    #[Computed()]
    public function paletteGroups(): array
    {
        return app(CmsBuilderClassPalette::class)->groups();
    }

    /**
     * Registry definition of the widget open in the drawer, or null.
     *
     * @return array{label: string, icon: string, component: string, props: array<string, array<string, mixed>>}|null
     */
    #[Computed()]
    public function selectedWidgetDefinition(): ?array
    {
        $widget = $this->selectedWidgetId !== null ? $this->service()->findDraftWidget($this->page, $this->selectedWidgetId) : null;

        return $widget !== null ? ($this->widgets[$widget['type']] ?? null) : null;
    }

    /**
     * adv-select options for a `source` prop, led by a "none" option so a choice can be cleared.
     *
     * @return list<array{value: string, label: string}>
     */
    public function pickerOptions(string $source): array
    {
        return [['value' => '', 'label' => __('cms::builder.none')], ...$this->service()->sourceOptions($source)];
    }

    /**
     * Palette groups plus how many of their classes the open widget/region uses
     * (a group with a selection starts expanded).
     *
     * @return list<array{label: string, classes: list<string>, selected: int}>
     */
    #[Computed()]
    public function paletteGroupsForSelection(): array
    {
        $chosen = $this->selectedRegion !== null ? $this->regionClasses : $this->widgetClasses;

        return array_values(array_map(
            fn (array $group): array => [...$group, 'selected' => count(array_intersect($group['classes'], $chosen))],
            $this->paletteGroups,
        ));
    }

    public function updatedTemplate(string $value): void
    {
        $this->mutate(fn () => $this->service()->switchTemplate($this->page, $value));
    }

    public function openPicker(string $region): void
    {
        if (! array_key_exists($region, app(CmsBuilderTemplates::class)->find($this->page->template)['regions'])) {
            return;
        }

        $this->addRegion = $region;
        $this->dispatch('open-modal-builder-widget-picker');
    }

    public function addWidget(string $type): void
    {
        if ($this->addRegion === null) {
            return;
        }

        $region = $this->addRegion;
        $newId = null;
        $this->mutate(function () use ($region, $type, &$newId): void {
            $newId = $this->service()->addWidget($this->page, $region, $type);
        });
        $this->dispatch('close-modal-builder-widget-picker');

        if (is_string($newId)) {
            $this->selectWidget($newId);
        }
    }

    public function selectWidget(string $id): void
    {
        $widget = $this->service()->findDraftWidget($this->page, $id);
        if ($widget === null) {
            $this->dispatch('notify', variant: 'danger', title: 'Error', message: __('cms::builder.widget_missing'));

            return;
        }

        $this->selectedRegion = null;
        $this->selectedWidgetId = $id;
        $this->widgetProps = $widget['props'];
        $this->widgetClasses = $widget['class'];
        $this->widgetCustomClass = $widget['custom_class'];
        $this->widgetHeading = $widget['heading'];
        $this->widgetShowHeading = $widget['show_heading'];
        unset($this->selectedWidgetDefinition, $this->paletteGroupsForSelection);
        $this->dispatch('open-drawer-builder-settings');
    }

    public function saveWidget(): void
    {
        if ($this->selectedWidgetId === null) {
            return;
        }

        $id = $this->selectedWidgetId;
        $this->mutate(fn () => $this->service()->updateWidget(
            $this->page,
            $id,
            $this->widgetProps,
            $this->widgetClasses,
            $this->widgetCustomClass,
            $this->widgetHeading,
            $this->widgetShowHeading,
        ));
    }

    public function removeWidget(string $id): void
    {
        // Closes x-synapse-confirm-dialog on every path, failures included.
        $this->dispatch('synapse-confirmed');

        $this->mutate(fn () => $this->service()->removeWidget($this->page, $id));

        if ($this->selectedWidgetId === $id) {
            $this->selectedWidgetId = null;
        }
    }

    public function selectRegion(string $region): void
    {
        $regions = $this->service()->normalize($this->page->layout_draft ?? [], $this->page->template)['regions'];
        if (! array_key_exists($region, $regions)) {
            return;
        }

        $this->selectedWidgetId = null;
        $this->selectedRegion = $region;
        $this->regionClasses = $regions[$region]['class'];
        $this->regionCustomClass = $regions[$region]['custom_class'];
        unset($this->paletteGroupsForSelection);
        $this->dispatch('open-drawer-builder-settings');
    }

    public function saveRegion(): void
    {
        if ($this->selectedRegion === null) {
            return;
        }

        $region = $this->selectedRegion;
        $this->mutate(fn () => $this->service()->updateRegionClasses($this->page, $region, $this->regionClasses, $this->regionCustomClass));
    }

    public function publish(): void
    {
        $this->authorizeAbility('cms.builder.publish.update');
        $this->service()->publish($this->page);
        unset($this->page);
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::builder.published'));
    }

    public function unpublish(): void
    {
        $this->authorizeAbility('cms.builder.publish.update');
        $this->service()->unpublish($this->page);
        unset($this->page);
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::builder.unpublished'));
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::builder.list'), icon: 'ph ph-layout', url: backend_route('cms.builder.index'))
            ->add(label: $this->page->title);
    }

    /**
     * @param  callable(): mixed  $change
     */
    private function mutate(callable $change): void
    {
        $this->authorizeAbility('cms.builder.update');

        try {
            $change();
        } catch (InvalidArgumentException $e) {
            $this->dispatch('notify', variant: 'danger', title: 'Error', message: $e->getMessage());

            return;
        }

        unset($this->page, $this->selectedWidgetDefinition);
        $this->dispatch('cms-builder-refresh-canvas');
    }

    private function service(): CmsBuilderPageService
    {
        return app(CmsBuilderPageService::class);
    }

    private function authorizeAbility(string $ability): void
    {
        $user = auth()->user();
        if (! ($user instanceof Authorizable && $user->can($ability))) {
            abort(403);
        }
    }

    public function render()
    {
        return $this->view()->title(page_title($this->page->title));
    }
};
