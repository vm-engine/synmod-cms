<div x-data="{ pageName: '{{ $this->title() }}' }">
    <x-synapse-breadcrumbs :breadcrumbs="$this->breadcrumbs" />

    @if ($this->isDummyMode)
        <div class="mb-6">
            <x-synapse-alert
                type="warning"
                :message="__('cms::labels.analytics_dummy_notice')"
                :dismissible="false"
            />
        </div>
    @endif

    <div class="mb-6 flex flex-wrap items-center justify-end gap-3">
        <div class="w-48">
            <select class="form-input" aria-label="{{ __('cms::labels.analytics') }}" wire:model.live="range">
                @foreach ($this->rangeOptions as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </select>
        </div>

        @canAccess('cms.analytics.update')
        <button class="btn secondary" type="button" wire:click="refresh" wire:loading.attr="disabled">
            <span class="ph ph-arrows-clockwise" wire:loading.class="animate-spin" wire:target="refresh"></span>
            {{ __('cms::labels.analytics_refresh') }}
        </button>
        @endcanAccess
    </div>

    <div class="space-y-6">
        <livewire:cms::backend.analytics-panel lazy source="ga" :range="$range" wire:key="ga-{{ $range }}-{{ $version }}" />
        <livewire:cms::backend.analytics-panel lazy source="gsc" :range="$range" wire:key="gsc-{{ $range }}-{{ $version }}" />
    </div>
</div>
