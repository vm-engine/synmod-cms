@php $errors ??= new \Illuminate\Support\ViewErrorBag; @endphp
<div
    class="syn-tabs mb-4"
    role="tablist"
>
    @foreach ($locales as $locale)
        @php $hasError = $locale !== $primary && $errors->has("{$model}.{$locale}.*"); @endphp
        <button
            class="syn-tab {{ $hasError ? 'text-red-600 dark:text-red-400' : '' }}"
            type="button"
            role="tab"
            :class="is('{{ $locale }}') ? 'syn-tab-active' : ''"
            :aria-selected="is('{{ $locale }}') ? 'true' : 'false'"
            @click="select('{{ $locale }}')"
        >
            {{ strtoupper($locale) }}
            @if ($locale === $primary)
                <span class="text-xs text-gray-400">({{ __('cms::labels.default_language') }})</span>
            @else
                <span
                    class="text-xs"
                    title="{{ $isFilled($locale) ? __('cms::labels.translated') : __('cms::labels.not_translated') }}"
                >{{ $isFilled($locale) ? '●' : '○' }}</span>
            @endif
            @if ($hasError)
                <span class="ph ph-warning-circle"></span>
            @endif
        </button>
    @endforeach
</div>
