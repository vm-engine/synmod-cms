@if ($template)
    @include($template, ['languages' => $languages, 'current' => $current, 'display' => $display] + $with)
@elseif ($variant === 'list')
    <nav {{ $attributes->merge(['class' => trim('cms-language-switcher cms-language-switcher--list '.$wrapperClass)]) }} aria-label="{{ __('cms::labels.language') }}">
        <ul class="cms-language-switcher__list">
            @foreach ($items() as $language)
                @include('cms::components.language-switcher-label', ['language' => $language, 'display' => $display, 'asLink' => true])
            @endforeach
        </ul>
    </nav>
@else
    <details {{ $attributes->merge(['class' => trim('cms-language-switcher cms-language-switcher--dropdown '.$wrapperClass)]) }}>
        <summary
            class="cms-language-switcher__current"
            aria-label="{{ __('cms::labels.language') }}: {{ $current['name'] ?? '' }}"
        >
            @if ($trigger === 'globe' || $current === null)
                <span class="{{ $globeIcon }}" aria-hidden="true"></span>
            @else
                @include('cms::components.language-switcher-label', ['language' => $current, 'display' => $display, 'asLink' => false])
            @endif
        </summary>
        <ul class="cms-language-switcher__list">
            @foreach ($items() as $language)
                @include('cms::components.language-switcher-label', ['language' => $language, 'display' => $display, 'asLink' => true])
            @endforeach
        </ul>
    </details>
@endif
