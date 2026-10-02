@php
    $url = \VmEngine\Cms\Support\CmsUrl::to($article);
@endphp
<article class="cms-article-card">
    @if (($showCover ?? true) && $article->cover_image)
        <div class="cms-article-card__image">
            <a href="{{ $url }}">
                <img src="{{ $article->cover_image }}" alt="{{ $article->translate('title') }}" loading="lazy" />
            </a>
        </div>
    @endif
    <div class="cms-article-card__body">
        @if (($showCategory ?? true) && $article->category)
            <div class="cms-article-card__section">
                <a href="{{ \VmEngine\Cms\Support\CmsUrl::to($article->category) }}" class="cms-article-card__section-link">{{ $article->category->translate('title') }}</a>
            </div>
        @endif
        <h3 class="cms-article-card__title">
            <a href="{{ $url }}" class="cms-article-card__link">{{ $article->translate('title') }}</a>
        </h3>
        @if (($showDate ?? true) && $article->published_at)
            <div class="cms-article-card__meta">
                <time datetime="{{ $article->published_at->toDateString() }}">
                    {{ $article->published_at->format('d F Y') }}
                </time>
                @if ($article->read_time > 0)
                    <span class="cms-article-card__read-time">· {{ $article->read_time }} min read</span>
                @endif
            </div>
        @endif
        @if (($showExcerpt ?? true) && $article->translate('excerpt'))
            <p class="cms-article-card__excerpt">{{ $article->translate('excerpt') }}</p>
        @endif
    </div>
</article>
