@if($article)
<div class="cms-article-viewer">
    <nav class="cms-breadcrumb" aria-label="breadcrumb">
        <ol class="cms-breadcrumb__list">
            <li class="cms-breadcrumb__item"><a href="/" class="cms-breadcrumb__link">Home</a></li>
            <li class="cms-breadcrumb__item">
                <a href="/{{ $article->section->slug }}" class="cms-breadcrumb__link">{{ $article->section->title }}</a>
            </li>
            @if($article->category)
            <li class="cms-breadcrumb__item">
                <a href="/{{ $article->section->slug }}/{{ $article->category->slug }}" class="cms-breadcrumb__link">{{ $article->category->title }}</a>
            </li>
            @endif
            <li class="cms-breadcrumb__item cms-breadcrumb__item--active" aria-current="page">{{ $article->title }}</li>
        </ol>
    </nav>

    <article class="cms-article">
        <header class="cms-article__header">
            @if($article->is_featured)
            <span class="cms-article__badge cms-article__badge--featured">Featured</span>
            @endif

            <h1 class="cms-article__title">{{ $article->title }}</h1>

            <div class="cms-article__meta">
                <time class="cms-article__date" datetime="{{ $article->published_at?->toDateString() }}">
                    {{ $article->published_at?->format('d F Y') }}
                </time>
                @if($article->read_time > 0)
                <span class="cms-article__read-time">· {{ $article->read_time }} min read</span>
                @endif
                @if($article->creator)
                <span class="cms-article__author">· {{ $article->creator->name }}</span>
                @endif
            </div>

            @if($article->tags->isNotEmpty())
            <div class="cms-article__tags">
                @foreach($article->tags as $tag)
                <span class="cms-article__tag">{{ $tag->name }}</span>
                @endforeach
            </div>
            @endif
        </header>

        @if($article->cover_image)
        <div class="cms-article__cover">
            <img src="{{ $article->cover_image }}" alt="{{ $article->title }}" class="cms-article__cover-image" />
        </div>
        @endif

        @if($article->excerpt)
        <div class="cms-article__excerpt">
            <p>{{ $article->excerpt }}</p>
        </div>
        @endif

        <div class="cms-article__body">
            {!! $article->body !!}
        </div>
    </article>
</div>
@endif
