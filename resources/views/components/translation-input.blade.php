@php $errors ??= new \Illuminate\Support\ViewErrorBag; @endphp
@foreach ($locales as $locale)
    @php
        $name = "{$model}.{$locale}.{$field}";
        $inputId = str_replace('.', '-', $name);
    @endphp
    <div
        x-show="is('{{ $locale }}')"
        style="display: none"
    >
        <label
            class="form-label"
            for="{{ $inputId }}"
        >{{ $label }} <span class="text-xs text-gray-400">({{ strtoupper($locale) }})</span></label>
        @if ($type === 'rte')
            <x-cms::rte
                :name="$name"
                :value="$values[$locale][$field] ?? ''"
                :height="$height"
            />
        @elseif ($type === 'textarea')
            <textarea
                class="form-input @error($name) has-error @enderror"
                id="{{ $inputId }}"
                rows="{{ $rows }}"
                placeholder="{{ $placeholder }}"
                wire:model="{{ $name }}"
            ></textarea>
        @else
            <input
                class="form-input @error($name) has-error @enderror"
                id="{{ $inputId }}"
                type="text"
                placeholder="{{ $placeholder }}"
                wire:model="{{ $name }}"
            />
        @endif
        @error($name)
            <p class="form-error">{{ $message }}</p>
        @enderror
        <p class="form-help">{{ __('cms::labels.translation_fallback_hint') }}</p>
    </div>
@endforeach
