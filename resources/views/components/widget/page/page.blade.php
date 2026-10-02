@if ($page)
    @if ($template)
        @include($template, ['page' => $page] + $with)
    @elseif ($bare)
        @if ($showTitle)
            <h2 class="cms-widget-page__title">{{ $page->translate('title') }}</h2>
        @endif
        @if ($showCover && $page->cover_image)
            <div class="cms-widget-page__cover">
                <img src="{{ $page->cover_image }}" alt="{{ $page->translate('title') }}" class="cms-widget-page__cover-image" loading="lazy" />
            </div>
        @endif
        <div class="cms-page__body">
            {!! $page->translate('body') !!}
        </div>
    @else
        <div class="cms-widget-page {{ $wrapperClass }}">
            @if ($showTitle)
                <h2 class="cms-widget-page__title">{{ $page->translate('title') }}</h2>
            @endif
            @if ($showCover && $page->cover_image)
                <div class="cms-widget-page__cover">
                    <img src="{{ $page->cover_image }}" alt="{{ $page->translate('title') }}" class="cms-widget-page__cover-image" loading="lazy" />
                </div>
            @endif
            <div class="cms-page__body">
                {!! $page->translate('body') !!}
            </div>
        </div>
    @endif
@endif
