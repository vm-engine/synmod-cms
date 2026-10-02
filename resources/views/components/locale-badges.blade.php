<span
    class="ml-1 inline-flex gap-1 align-middle"
    data-locale-badges
>
    @foreach ($locales as $locale)
        <x-synapse-badge size="sm">{{ strtoupper($locale) }}</x-synapse-badge>
    @endforeach
</span>
