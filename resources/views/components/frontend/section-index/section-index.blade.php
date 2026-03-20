@if($section)
<div class="cms-section-index">
    <header class="cms-section__header">
        @if($section->cover_image)
        <div class="cms-section__cover">
            <img src="{{ $section->cover_image }}" alt="{{ $section->title }}" class="cms-section__cover-image" />
        </div>
        @endif
        <h1 class="cms-section__title">{{ $section->title }}</h1>
        @if($section->description)
        <div class="cms-section__description">{!! $section->description !!}</div>
        @endif
    </header>

    @if($section->categories->isNotEmpty())
    <nav class="cms-section__categories">
        @foreach($section->categories->where('status', 'published') as $cat)
        <a href="/{{ $section->slug }}/{{ $cat->slug }}" class="cms-section__category-link">{{ $cat->title }}</a>
        @endforeach
    </nav>
    @endif

    <div class="cms-article-grid">
        @forelse($articles as $article)
        @php
            $url = '/' . $section->slug . ($article->category_id ? '/' . $article->category->slug : '') . '/' . $article->slug;
        @endphp
        <article class="cms-article-card">
            @if(($section->config['show_thumbnail'] ?? true) && $article->cover_image)
            <div class="cms-article-card__image">
                <a href="{{ $url }}">
                    <img src="{{ $article->cover_image }}" alt="{{ $article->title }}" loading="lazy" />
                </a>
            </div>
            @endif
            <div class="cms-article-card__body">
                @if($article->is_featured)
                <span class="cms-article-card__featured">Featured</span>
                @endif
                <h2 class="cms-article-card__title">
                    <a href="{{ $url }}" class="cms-article-card__link">{{ $article->title }}</a>
                </h2>
                @if(($section->config['show_date'] ?? true))
                <div class="cms-article-card__meta">
                    <time datetime="{{ $article->published_at?->toDateString() }}">
                        {{ $article->published_at?->format('d F Y') }}
                    </time>
                    @if($article->read_time > 0)
                    <span class="cms-article-card__read-time">· {{ $article->read_time }} min read</span>
                    @endif
                </div>
                @endif
                @if($article->excerpt)
                <p class="cms-article-card__excerpt">{{ $article->excerpt }}</p>
                @endif
            </div>
        </article>
        @empty
        <div class="cms-empty">{{ __('cms::labels.no_articles') }}</div>
        @endforelse
    </div>

    @if($articles->hasPages())
    <div class="cms-pagination">
        {{ $articles->links() }}
    </div>
    @endif
</div>
@endif
