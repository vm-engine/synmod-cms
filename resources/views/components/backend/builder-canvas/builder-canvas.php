<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Access\Authorizable;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use VmEngine\Cms\Models\CmsBuilderPage;
use VmEngine\Cms\Services\CmsBuilderPageService;
use VmEngine\Cms\Support\CmsBuilderTemplates;
use VmEngine\Cms\Support\FrontendViewResolver;

/*
 * The page builder canvas: loaded inside the editor's iframe, it renders the draft
 * with the real theme template and frontend layout. Mutations here dispatch
 * `cms-builder-changed`, which its Alpine data forwards to the parent editor.
 */
new class extends Component
{
    #[Locked]
    public int $builderPageId;

    /** Render the draft without edit chrome (the editor's Preview button). */
    #[Url()]
    public bool $preview = false;

    public function mount(int $id): void
    {
        $this->authorizeEdit();
        $this->builderPageId = $id;
    }

    #[Computed()]
    public function page(): CmsBuilderPage
    {
        return app(CmsBuilderPageService::class)->findOrFailById($this->builderPageId);
    }

    /**
     * @return array{name: string, view: string, regions: array<string, string>}
     */
    #[Computed()]
    public function template(): array
    {
        return app(CmsBuilderTemplates::class)->find($this->page->template);
    }

    /** wire:sort handler: (item, position, group id). */
    public function moveWidget(string $id, int $position, string $region): void
    {
        $this->mutate(fn (CmsBuilderPageService $service) => $service->moveWidget($this->page, $id, $position, $region));
    }

    public function duplicateWidget(string $id): void
    {
        $this->mutate(fn (CmsBuilderPageService $service) => $service->duplicateWidget($this->page, $id));
    }

    /**
     * @param  callable(CmsBuilderPageService): mixed  $change
     */
    private function mutate(callable $change): void
    {
        $this->authorizeEdit();

        try {
            $change(app(CmsBuilderPageService::class));
        } catch (InvalidArgumentException $e) {
            $this->dispatch('notify', variant: 'danger', title: 'Error', message: $e->getMessage());

            return;
        }

        unset($this->page);
        $this->dispatch('cms-builder-changed');
    }

    private function authorizeEdit(): void
    {
        $user = auth()->user();
        if (! ($user instanceof Authorizable && $user->can('cms.builder.update'))) {
            abort(403);
        }
    }

    public function render()
    {
        return $this->view()
            ->title($this->page->title)
            ->layout(FrontendViewResolver::layout());
    }
};
