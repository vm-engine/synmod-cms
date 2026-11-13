{{-- Shared add-item form body. Props: targetType, wireModel* --}}
<div class="grid gap-4 sm:grid-cols-3">
    <div>
        <label
            class="form-label required"
            for="newLabel"
        >{{ __('cms::labels.label') }}</label>
        <input
            class="form-input @error('newLabel') has-error @enderror"
            id="newLabel"
            type="text"
            wire:model="newLabel"
        />
        @error('newLabel')
            <p class="form-error">{{ $message }}</p>
        @enderror
        @foreach (\VmEngine\Cms\Support\CmsLocale::secondary() as $locale)
            <input
                class="form-input mt-2 @error('newLabelTranslations.' . $locale) has-error @enderror"
                type="text"
                placeholder="{{ __('cms::labels.label') }} ({{ strtoupper($locale) }})"
                aria-label="{{ __('cms::labels.label') }} ({{ strtoupper($locale) }})"
                wire:model="newLabelTranslations.{{ $locale }}"
            />
            @error('newLabelTranslations.' . $locale)
                <p class="form-error">{{ $message }}</p>
            @enderror
        @endforeach
    </div>
    <div>
        <label class="form-label">{{ __('cms::labels.target_type') }}</label>
        <x-synapse-adv-select
            wire-model="newTargetType"
            :live="true"
            :options="$this->targetTypeOptions"
        />
    </div>
    <div>
        @include('cms::components.backend.menu-item-manager._target-selector', [
            'targetType' => $newTargetType,
            'wireModelUrl' => 'newTargetUrl',
            'wireModelTargetId' => 'newTargetId',
        ])
    </div>
    <div class="flex items-end">
        <label class="flex items-center gap-2">
            <input
                type="checkbox"
                wire:model="newOpenNewTab"
            />
            <span class="text-sm">{{ __('cms::labels.open_new_tab') }}</span>
        </label>
    </div>
    @if (in_array($newTargetType, ['album_list', 'download_list']))
        @include('cms::components.backend.partials._custom-rendering-fields', [
            'viewModel' => 'newCustomView',
            'layoutModel' => 'newCustomLayout',
            'wrapperClass' => 'sm:col-span-3',
        ])
    @endif
    <div class="flex items-end sm:col-start-3">
        <button
            class="btn primary w-full"
            type="button"
            wire:click="addItem"
        >
            <span class="ph ph-plus"></span> {{ __('cms::labels.add_item') }}
        </button>
    </div>
</div>
