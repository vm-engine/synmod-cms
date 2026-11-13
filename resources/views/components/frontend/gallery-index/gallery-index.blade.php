<div class="cms-gallery-index">
    <nav class="cms-breadcrumb" aria-label="{{ __('cms::labels.breadcrumb') }}">
        <ol class="cms-breadcrumb__list">
            <li class="cms-breadcrumb__item"><a href="{{ \VmEngine\Cms\Support\CmsUrl::to('/') }}" class="cms-breadcrumb__link">{{ __('cms::labels.home') }}</a></li>
            <li class="cms-breadcrumb__item cms-breadcrumb__item--active">{{ __('cms::labels.gallery') }}</li>
        </ol>
    </nav>

    <header class="cms-gallery-index__header">
        <h1 class="cms-gallery-index__title">{{ __('cms::labels.gallery') }}</h1>
    </header>

    <div class="cms-gallery-grid">
        @forelse($albums as $album)
        <a
            href="{{ \VmEngine\Cms\Support\CmsUrl::to($album) }}"
            class="cms-gallery-album-card"
        >
            @if($album->cover_image)
            <div class="cms-gallery-album-card__image">
                <img
                    src="{{ $album->cover_image }}"
                    alt="{{ $album->translate('title') }}"
                    loading="lazy"
                />
            </div>
            @else
            <div class="cms-gallery-album-card__image cms-gallery-album-card__image--empty">
                <span class="ph ph-images"></span>
            </div>
            @endif
            <div class="cms-gallery-album-card__body">
                <h2 class="cms-gallery-album-card__title">{{ $album->translate('title') }}</h2>
                @if($album->translate('description'))
                <p class="cms-gallery-album-card__desc">{{ $album->translate('description') }}</p>
                @endif
                <span class="cms-gallery-album-card__count">
                    {{ $album->images->count() }} {{ __('cms::labels.photos') }}
                </span>
            </div>
        </a>
        @empty
        <div class="cms-empty">{{ __('cms::labels.no_albums') }}</div>
        @endforelse
    </div>
</div>
