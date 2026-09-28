<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use VmEngine\Cms\Enums\RteDriver;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;
use VmEngine\SynAuth\Facades\SynAuth;

new class extends Component
{
    public string $editorDriver = '';

    public function mount(): void
    {
        $this->editorDriver = RteDriver::current()->value;

        synav()->setActiveMenu('cms.settings');
    }

    public function title(): string
    {
        return __('cms::labels.settings');
    }

    public function save(): void
    {
        $user = Auth::user();

        if (! ($user instanceof Authorizable && $user->can('cms.settings.update'))) {
            $this->dispatch('notify', variant: 'danger', title: 'Error', message: __('cms::labels.no_permission'));

            return;
        }

        $this->validate([
            'editorDriver' => ['required', Rule::enum(RteDriver::class)],
        ]);

        dbconf(['cms.editor.driver' => $this->editorDriver]);

        SynAuth::logActivity(
            (int) Auth::id(),
            'cms.settings.update',
            'User '.Auth::user()->name.' set the rich-text editor to '.$this->editorDriver,
            'settings',
            'cms',
        );

        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.settings_saved'));
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    #[Computed]
    public function editorOptions(): array
    {
        return RteDriver::options();
    }

    #[Computed]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::labels.settings'), icon: 'fa-solid fa-gear');
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
