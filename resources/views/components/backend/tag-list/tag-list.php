<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use VmEngine\Cms\Livewire\Forms\TagFormObject;
use VmEngine\Cms\Models\CmsTag;
use VmEngine\Cms\Services\CmsActivityLogger;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

new class extends Component
{
    use WithPagination;

    public TagFormObject $form;

    #[Url()]
    public string $q = '';

    #[Url()]
    public int $limit = 20;

    public function mount(): void
    {
        synav()->setActiveMenu('cms.tag');
    }

    public function title(): string
    {
        return __('cms::labels.tag_list');
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    #[On('load-tag')]
    public function loadTag(int $id): void
    {
        $tag = CmsTag::findOrFail($id);
        $this->form->setTag($tag);
    }

    #[On('reset-tag-form')]
    public function resetForm(): void
    {
        $this->form->reset();
    }

    public function save(): void
    {
        if ($this->form->tag) {
            $this->form->update();
            $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.tag_updated'));
        } else {
            $this->form->store();
            $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.tag_created'));
        }
        $this->dispatch('close-modal-tag-form');
        unset($this->tagList);
    }

    public function delete(string $token): void
    {
        try {
            $id = CmsTag::validateDeleteToken($token);
            if (! $id) {
                throw new Exception(__('cms::labels.invalid_delete_token'));
            }
            $tag = CmsTag::findOrFail($id);
            app(CmsActivityLogger::class)->logDelete('tag', $tag);
            $tag->delete();
        } catch (Exception $e) {
            $this->dispatch('notify', variant: 'danger', title: 'Error', message: $e->getMessage());

            return;
        }

        $this->dispatch('synapse-confirmed');
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.tag_deleted'));
        unset($this->tagList);
    }

    #[Computed()]
    public function tagList()
    {
        $model = CmsTag::withCount('articles');

        if (! empty($this->q) && strlen($this->q) > 1) {
            $model->where('name', 'like', "%{$this->q}%")
                ->orWhere('slug', 'like', "%{$this->q}%");
        }

        return $model->orderBy('name')->paginate($this->limit)->onEachSide(1);
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::labels.tag_list'), icon: 'fa-solid fa-tags');
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
