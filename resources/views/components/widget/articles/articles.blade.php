@if($articles->isNotEmpty())
<div class="cms-widget-articles cms-widget-articles--{{ $layout }}">
    <div class="cms-article-grid cms-article-grid--{{ $layout }}">
        @foreach($articles as $article)
        @php
            $url = $article->section
                ? '/' . $article->section->slug . ($article->category_id ? '/' . $article->category->slug : '') . '/' . $article->slug
                : '#';
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
                @if($showFeaturedBadge && $article->is_featured)
                <span class="cms-article-card__featured">{{ __('cms::labels.featured') }}</span>
                @endif
                @if($showSection && $article->section)
                <div class="cms-article-card__section">
                    <a href="/{{ $article->section->slug }}" class="cms-article-card__section-link">{{ $article->section->title }}</a>
                </div>
                @endif
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
