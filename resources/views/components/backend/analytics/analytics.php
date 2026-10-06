<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use VmEngine\Cms\Enums\AnalyticsRange;
use VmEngine\Cms\Services\Analytics\FakeGoogleApiClient;
use VmEngine\Cms\Services\Analytics\GoogleApiClient;
use VmEngine\Cms\Support\AnalyticsCache;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

new class extends Component
{
    #[Url]
    public string $range = '28';

    /** Part of the panels' wire:key, so a refresh remounts them. */
    public int $version = 1;

    public function mount(): void
    {
        $this->range = (AnalyticsRange::tryFrom($this->range) ?? AnalyticsRange::Last28)->value;
        $this->version = AnalyticsCache::version();

        synav()->setActiveMenu('cms.analytics');
    }

    public function updatedRange(): void
    {
        $this->range = (AnalyticsRange::tryFrom($this->range) ?? AnalyticsRange::Last28)->value;
    }

    public function title(): string
    {
        return __('cms::labels.analytics');
    }

    public function refresh(): void
    {
        $user = Auth::user();

        if (! ($user instanceof Authorizable && $user->can('cms.analytics.update'))) {
            $this->dispatch('notify', variant: 'danger', title: 'Error', message: __('cms::labels.no_permission'));

            return;
        }

        AnalyticsCache::flush();
        $this->version = AnalyticsCache::version();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    #[Computed]
    public function rangeOptions(): array
    {
        return AnalyticsRange::options();
    }

    #[Computed]
    public function isDummyMode(): bool
    {
        return app(GoogleApiClient::class) instanceof FakeGoogleApiClient;
    }

    #[Computed]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::labels.analytics'), icon: 'ph ph-chart-line');
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
