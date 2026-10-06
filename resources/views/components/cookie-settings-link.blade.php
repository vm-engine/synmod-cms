<button
    {{ $attributes->merge(['class' => 'cms-consent-link', 'type' => 'button']) }}
    x-data
    x-on:click="$dispatch('cms-consent-open')"
>{{ __('cms::labels.consent_settings') }}</button>
