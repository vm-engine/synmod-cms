{{-- Shared "Custom Rendering" card. Expects: $fields (array of ['model', 'label', 'help', 'placeholder', optional 'id', 'wide']) --}}
<div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
    <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-100 dark:border-gray-800">
        <h3 class="text-base font-medium">{{ __('cms::labels.custom_rendering') }}</h3>
    </div>
    <div class="grid gap-4 p-5 sm:grid-cols-2 sm:p-6">
        @foreach ($fields as $field)
            <div @class(['sm:col-span-2' => $field['wide'] ?? false])>
                <label class="form-label" @isset($field['id']) for="{{ $field['id'] }}" @endisset>{{ __('cms::labels.'.$field['label']) }}</label>
                <input
                    class="form-input"
                    @isset($field['id']) id="{{ $field['id'] }}" @endisset
                    type="text"
                    wire:model="{{ $field['model'] }}"
                    placeholder="{{ $field['placeholder'] }}"
                />
                <p class="form-help">{{ __('cms::labels.'.$field['help']) }}</p>
            </div>
        @endforeach
    </div>
</div>
