{{-- Shared add-item form body. Props: targetType, wireModel* --}}
<div class="grid gap-4 sm:grid-cols-3">
    <div>
        <label
            class="form-label required"
            for="newLabel"
        >{{ __('cms::labels.label') }}</label>
        <input
            class="form-input @error('newLabel') !border-red-500 @enderror"
            id="newLabel"
            type="text"
            wire:model="newLabel"
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
    <div class="flex items-end sm:col-start-3">
        <button
            class="btn primary w-full"
            type="button"
            wire:click="addItem"
        >
            <span class="fa-solid fa-plus"></span> {{ __('cms::labels.add_item') }}
        </button>
    </div>
</div>
