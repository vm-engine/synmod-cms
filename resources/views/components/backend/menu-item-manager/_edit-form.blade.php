{{-- Edit form shared across all 3 levels --}}
<div class="space-y-3 p-4">
    <div class="grid gap-3 sm:grid-cols-3">
        <div>
            <label class="form-label required">{{ __('cms::labels.label') }}</label>
            <input
                class="form-input @error('editLabel') has-error @enderror"
                type="text"
                wire:model="editLabel"
            />
            @error('editLabel')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="form-label">{{ __('cms::labels.target_type') }}</label>
            <x-synapse-adv-select
                wire-model="editTargetType"
                :live="true"
                :options="$this->targetTypeOptions"
            />
        </div>
        <div>
            @include('cms::components.backend.menu-item-manager._target-selector', [
                'targetType' => $editTargetType,
                'wireModelUrl' => 'editTargetUrl',
                'wireModelTargetId' => 'editTargetId',
            ])
        </div>
        <div>
            <label class="form-label">{{ __('cms::labels.parent') }}</label>
            <x-synapse-adv-select
                wire-model="editParentId"
                :live="true"
                :options="$this->parentOptions"
            />
        </div>
    </div>
    <label class="flex items-center gap-2">
        <input
            type="checkbox"
            wire:model="editOpenNewTab"
        />
        <span class="text-sm">{{ __('cms::labels.open_new_tab') }}</span>
    </label>
    @if (in_array($editTargetType, ['album_list', 'download_list']))
        @include('cms::components.backend.partials._custom-rendering-fields', [
            'viewModel' => 'editCustomView',
            'layoutModel' => 'editCustomLayout',
        ])
    @endif
    <div class="flex gap-2">
        <button
            class="btn sm primary"
            type="button"
            wire:click="saveEdit"
        >
            <span class="fa-solid fa-floppy-disk"></span> {{ __('cms::labels.save') }}
        </button>
        <button
            class="btn sm secondary"
            type="button"
            wire:click="cancelEdit"
        >{{ __('cms::labels.cancel') }}</button>
    </div>
</div>
