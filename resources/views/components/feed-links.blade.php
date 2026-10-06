@foreach ($feeds as $feed)
    <link
        rel="alternate"
        type="application/rss+xml"
        title="{{ $feed['title'] }}"
        href="{{ $feed['href'] }}"
    />
@endforeach
