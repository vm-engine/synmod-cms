@if ($sectionModel)
    @if ($template)
        @include($template, ['section' => $sectionModel, 'articles' => $articles, 'card' => $card] + $with)
    @elseif ($articles->isNotEmpty())
        @if ($bare)
            @foreach ($articles as $article)
                @if ($card)
                    @include($card, ['article' => $article, 'section' => $sectionModel])
                @else
                    @include('cms::components.widget.section-articles._default-card', ['article' => $article, 'section' => $sectionModel])
                @endif
            @endforeach
        @else
            <div class="cms-widget-articles cms-widget-articles--{{ $layout }} {{ $wrapperClass }}">
                <div class="cms-article-grid cms-article-grid--{{ $layout }} {{ $gridClass }}">
                    @foreach ($articles as $article)
                        @if ($card)
                            @include($card, ['article' => $article, 'section' => $sectionModel])
                        @else
                            @include('cms::components.widget.section-articles._default-card', ['article' => $article, 'section' => $sectionModel])
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    @endif
@endif
