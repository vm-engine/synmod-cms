@if($category)
<div class="cms-category-index">
    <nav class="cms-breadcrumb" aria-label="{{ __('cms::labels.breadcrumb') }}">
        <ol class="cms-breadcrumb__list">
            <li class="cms-breadcrumb__item"><a href="{{ \VmEngine\Cms\Support\CmsUrl::to('/') }}" class="cms-breadcrumb__link">{{ __('cms::labels.home') }}</a></li>
            <li class="cms-breadcrumb__item"><a href="{{ \VmEngine\Cms\Support\CmsUrl::to($category->section) }}" class="cms-breadcrumb__link">{{ $category->section->translate('title') }}</a></li>
            <li class="cms-breadcrumb__item cms-breadcrumb__item--active">{{ $category->translate('title') }}</li>
        </ol>
    </nav>

    <header class="cms-category__header">
        @if($category->cover_image)
        <div class="cms-category__cover">
            <img src="{{ $category->cover_image }}" alt="{{ $category->translate('title') }}" class="cms-category__cover-image" />
        </div>
        @endif
        <h1 class="cms-category__title">{{ $category->translate('title') }}</h1>
        @if($category->translate('description'))
        <div class="cms-category__description">{!! $category->translate('description') !!}</div>
        @endif
    </header>

    <div class="cms-article-grid">
        @forelse($articles as $article)
        @php
            $url = \VmEngine\Cms\Support\CmsUrl::to($article);
        @endphp
        <article class="cms-article-card">
            @if($article->cover_image)
            <div class="cms-article-card__image">
                <a href="{{ $url }}">
                    <img src="{{ $article->cover_image }}" alt="{{ $article->translate('title') }}" loading="lazy" />
                </a>
            </div>
            @endif
            <div class="cms-article-card__body">
                <h2 class="cms-article-card__title">
                    <a href="{{ $url }}" class="cms-article-card__link">{{ $article->translate('title') }}</a>
                </h2>
                <div class="cms-article-card__meta">
                    <time datetime="{{ $article->published_at?->toDateString() }}">{{ $article->published_at?->translatedFormat('d F Y') }}</time>
                    @if($article->read_time > 0)
                    <span>· {{ __('cms::labels.min_read', ['minutes' => $article->read_time]) }}</span>
                    @endif
                </div>
                @if($article->translate('excerpt'))
                <p class="cms-article-card__excerpt">{{ $article->translate('excerpt') }}</p>
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
