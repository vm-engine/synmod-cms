{{-- Shared "Custom Rendering" panel (starts collapsed). Expects: $fields (array of ['model', 'label', 'help', 'placeholder', optional 'id', 'wide']) --}}
<x-synapse-panel
    :title="__('cms::labels.custom_rendering')"
    collapsible
    collapsed
>
    <div class="syn-panel-body grid gap-4 p-5 sm:grid-cols-2 sm:p-6">
        @foreach ($fields as $field)
            <div @class(['sm:col-span-2' => $field['wide'] ?? false])>
                <label class="form-label" @isset($field['id']) for="{{ $field['id'] }}" @endisset>{{ __('cms::labels.'.$field['label']) }}</label>
                <input
                    class="form-input @error($field['model']) has-error @enderror"
                    @isset($field['id']) id="{{ $field['id'] }}" @endisset
                    type="text"
                    wire:model="{{ $field['model'] }}"
                    placeholder="{{ $field['placeholder'] }}"
                />
                @error($field['model'])
                    <p class="form-error">{{ $message }}</p>
                @enderror
                <p class="form-help">{{ __('cms::labels.'.$field['help']) }}</p>
            </div>
        @endforeach
    </div>
</x-synapse-panel>
