@if ($albumRecord && $images->isNotEmpty())
    @if ($template)
        @include($template, ['images' => $images, 'album' => $albumRecord] + $with)
    @elseif ($bare)
        @foreach ($images as $image)
            @if ($card)
                @include($card, ['image' => $image, 'album' => $albumRecord] + $with)
            @else
                @include('cms::components.widget.gallery._default-card', ['image' => $image, 'album' => $albumRecord, 'showTitle' => $showTitle, 'showCaption' => $showCaption])
            @endif
        @endforeach
    @else
        <div class="cms-widget-gallery {{ $wrapperClass }}">
            <div class="cms-gallery-grid {{ $gridClass }}">
                @foreach ($images as $image)
                    @if ($card)
                        @include($card, ['image' => $image, 'album' => $albumRecord] + $with)
                    @else
                        @include('cms::components.widget.gallery._default-card', ['image' => $image, 'album' => $albumRecord, 'showTitle' => $showTitle, 'showCaption' => $showCaption])
                    @endif
                @endforeach
            </div>
        </div>
    @endif
@endif
