{{-- Inline add sub-item form. Props: parentLabel --}}
<div class="border-t border-brand-100 bg-brand-50/40 p-4 dark:border-brand-900/50 dark:bg-brand-900/10">
    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-brand-600 dark:text-brand-400">
        {{ __('cms::labels.add_sub_item') }}: {{ $parentLabel }}
    </p>
    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <label class="form-label required">{{ __('cms::labels.label') }}</label>
            <input
                class="form-input @error('newLabel') !border-red-500 @enderror"
                type="text"
                wire:model="newLabel"
                autofocus
            />
            @error('newLabel')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="form-label">{{ __('cms::labels.target_type') }}</label>
            <x-synapse-select
                wire:model.live="newTargetType"
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
        <div class="flex items-end gap-2 sm:col-start-3">
            <button
                class="btn primary w-full"
                type="button"
                wire:click="addItem"
            >
                <span class="fa-solid fa-plus"></span> {{ __('cms::labels.add_item') }}
            </button>
            <button
                class="btn"
                type="button"
                wire:click="cancelAddChild"
            >{{ __('cms::labels.cancel') }}</button>
        </div>
    </div>
</div>
