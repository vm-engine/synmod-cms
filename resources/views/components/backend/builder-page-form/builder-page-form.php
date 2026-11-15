<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Access\Authorizable;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use VmEngine\Cms\Livewire\Forms\BuilderPageFormObject;
use VmEngine\Cms\Services\CmsBuilderPageService;
use VmEngine\Cms\Support\CmsBuilderTemplates;
use VmEngine\Cms\Support\CmsLocale;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

new class extends Component
{
    public BuilderPageFormObject $form;

    #[Locked]
    public ?int $builderPageId = null;

    public function mount(?int $id = null): void
    {
        $this->builderPageId = $id;
        $this->authorizeAction();

        if ($id) {
            $this->form->setPage(app(CmsBuilderPageService::class)->findOrFailById($id));
        }

        synav()->setActiveMenu('cms.builder');
    }

    public function title(): string
    {
        return $this->builderPageId ? __('cms::builder.edit') : __('cms::builder.add');
    }

    public function primaryLocale(): string
    {
        return CmsLocale::primary();
    }

    public function save(): void
    {
        $this->authorizeAction();
        $creating = $this->builderPageId === null;
        $page = $this->form->save(auth()->id());

        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::builder.saved'));

        if ($creating) {
            $this->redirect(backend_route('cms.builder.editor', ['id' => $page->id]));
        }
    }

    /**
     * @return array<string, string>
     */
    #[Computed()]
    public function templateOptions(): array
    {
        return array_map(fn (array $template): string => (string) __($template['name']), app(CmsBuilderTemplates::class)->all());
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::builder.list'), icon: 'ph ph-layout', url: backend_route('cms.builder.index'))
            ->add(label: $this->title());
    }

    private function authorizeAction(): void
    {
        $user = auth()->user();
        $ability = $this->builderPageId ? 'cms.builder.update' : 'cms.builder.create';

        if (! ($user instanceof Authorizable && $user->can($ability))) {
            abort(403);
        }
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
