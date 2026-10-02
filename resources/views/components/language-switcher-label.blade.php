@php
    $icon = $language['icon'];
    $showIcon = str_starts_with($display, 'icon') && $icon !== null;
    $code = strtoupper($language['code']);
@endphp
@if ($asLink)
<li class="cms-language-switcher__item {{ $language['active'] ? 'cms-language-switcher__item--active' : '' }}">
    <a
        class="cms-language-switcher__link"
        href="{{ $language['url'] }}"
        hreflang="{{ str_replace('_', '-', $language['code']) }}"
        lang="{{ str_replace('_', '-', $language['code']) }}"
        @if ($language['active']) aria-current="true" @endif
        @if ($display === 'icon') aria-label="{{ $language['name'] }}" title="{{ $language['name'] }}" @endif
    >
@endif
@if ($showIcon)
    @if ($icon['type'] === 'image')
        <img class="cms-language-switcher__icon" src="{{ $icon['value'] }}" alt="" width="20" height="20" loading="lazy" />
    @elseif ($icon['type'] === 'class')
        <span class="cms-language-switcher__icon {{ $icon['value'] }}" aria-hidden="true"></span>
    @else
        <span class="cms-language-switcher__icon" aria-hidden="true">{{ $icon['value'] }}</span>
    @endif
@endif
@if (in_array($display, ['name', 'icon-name'], true))
    <span class="cms-language-switcher__name">{{ $language['name'] }}</span>
@elseif (in_array($display, ['code', 'icon-code'], true) || ($display === 'icon' && ! $showIcon))
    <span class="cms-language-switcher__code">{{ $code }}</span>
@elseif ($display === 'both')
    <span class="cms-language-switcher__code">{{ $code }}</span>
    <span class="cms-language-switcher__separator" aria-hidden="true">·</span>
    <span class="cms-language-switcher__name">{{ $language['name'] }}</span>
@endif
@if ($asLink)
    </a>
</li>
@endif
