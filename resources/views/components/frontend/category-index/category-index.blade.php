@if($category)
<div class="cms-category-index">
    <nav class="cms-breadcrumb" aria-label="breadcrumb">
        <ol class="cms-breadcrumb__list">
            <li class="cms-breadcrumb__item"><a href="/" class="cms-breadcrumb__link">Home</a></li>
            <li class="cms-breadcrumb__item"><a href="/{{ $category->section->slug }}" class="cms-breadcrumb__link">{{ $category->section->title }}</a></li>
            <li class="cms-breadcrumb__item cms-breadcrumb__item--active">{{ $category->title }}</li>
        </ol>
    </nav>

    <header class="cms-category__header">
        @if($category->cover_image)
        <div class="cms-category__cover">
            <img src="{{ $category->cover_image }}" alt="{{ $category->title }}" class="cms-category__cover-image" />
        </div>
        @endif
        <h1 class="cms-category__title">{{ $category->title }}</h1>
        @if($category->description)
        <div class="cms-category__description">{!! $category->description !!}</div>
        @endif
    </header>

    <div class="cms-article-grid">
        @forelse($articles as $article)
        @php
            $url = '/' . $category->section->slug . '/' . $category->slug . '/' . $article->slug;
        @endphp
        <article class="cms-article-card">
            @if($article->cover_image)
            <div class="cms-article-card__image">
                <a href="{{ $url }}">
                    <img src="{{ $article->cover_image }}" alt="{{ $article->title }}" loading="lazy" />
                </a>
            </div>
            @endif
            <div class="cms-article-card__body">
                <h2 class="cms-article-card__title">
                    <a href="{{ $url }}" class="cms-article-card__link">{{ $article->title }}</a>
                </h2>
                <div class="cms-article-card__meta">
                    <time datetime="{{ $article->published_at?->toDateString() }}">{{ $article->published_at?->format('d F Y') }}</time>
                    @if($article->read_time > 0)
                    <span>· {{ $article->read_time }} min read</span>
                    @endif
                </div>
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
    <div class="cms-pagination">{{ $articles->links() }}</div>
    @endif
</div>
@endif
