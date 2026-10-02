@foreach ($languages as $language)
<link rel="alternate" hreflang="{{ str_replace('_', '-', $language['code']) }}" href="{{ $language['absolute'] }}" />
@endforeach
@if ($default !== '')
<link rel="alternate" hreflang="x-default" href="{{ $default }}" />
@endif
