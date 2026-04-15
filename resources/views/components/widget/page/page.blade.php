@if ($page)
    @if ($template)
        @include($template, ['page' => $page] + $with)
    @elseif ($bare)
        @if ($showTitle)
            <h2 class="cms-widget-page__title">{{ $page->title }}</h2>
        @endif
        @if ($showCover && $page->cover_image)
            <div class="cms-widget-page__cover">
                <img src="{{ $page->cover_image }}" alt="{{ $page->title }}" class="cms-widget-page__cover-image" loading="lazy" />
            </div>
        @endif
        <div class="cms-page__body">
            {!! $page->body !!}
        </div>
    @else
        <div class="cms-widget-page {{ $wrapperClass }}">
            @if ($showTitle)
                <h2 class="cms-widget-page__title">{{ $page->title }}</h2>
            @endif
            @if ($showCover && $page->cover_image)
                <div class="cms-widget-page__cover">
                    <img src="{{ $page->cover_image }}" alt="{{ $page->title }}" class="cms-widget-page__cover-image" loading="lazy" />
                </div>
            @endif
            <div class="cms-page__body">
                {!! $page->body !!}
            </div>
        </div>
    @endif
@endif
