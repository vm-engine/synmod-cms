@if($sectionModel && $articles->isNotEmpty())
<div class="cms-widget-articles cms-widget-articles--{{ $layout }}">
    <div class="cms-article-grid cms-article-grid--{{ $layout }}">
        @foreach($articles as $article)
        @php
            $url = '/' . $sectionModel->slug . ($article->category_id && $categoryModel ? '/' . $categoryModel->slug : '') . '/' . $article->slug;
        @endphp
        <article class="cms-article-card">
            @if($showCover && $article->cover_image)
            <div class="cms-article-card__image">
                <a href="{{ $url }}">
                    <img src="{{ $article->cover_image }}" alt="{{ $article->title }}" loading="lazy" />
                </a>
            </div>
            @endif
            <div class="cms-article-card__body">
                <h3 class="cms-article-card__title">
                    <a href="{{ $url }}" class="cms-article-card__link">{{ $article->title }}</a>
                </h3>
                @if($showDate && $article->published_at)
                <div class="cms-article-card__meta">
                    <time datetime="{{ $article->published_at->toDateString() }}">
                        {{ $article->published_at->format('d F Y') }}
                    </time>
                    @if($article->read_time > 0)
                    <span class="cms-article-card__read-time">· {{ $article->read_time }} min read</span>
                    @endif
                </div>
                @endif
                @if($showExcerpt && $article->excerpt)
                <p class="cms-article-card__excerpt">{{ $article->excerpt }}</p>
                @endif
            </div>
        </article>
        @endforeach
    </div>
</div>
@endif
