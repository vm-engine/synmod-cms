<div class="cms-download-index">
    <nav class="cms-breadcrumb" aria-label="{{ __('cms::labels.breadcrumb') }}">
        <ol class="cms-breadcrumb__list">
            <li class="cms-breadcrumb__item"><a href="{{ \VmEngine\Cms\Support\CmsUrl::to('/') }}" class="cms-breadcrumb__link">{{ __('cms::labels.home') }}</a></li>
            <li class="cms-breadcrumb__item cms-breadcrumb__item--active">{{ __('cms::labels.downloads') }}</li>
        </ol>
    </nav>

    <header class="cms-download-index__header">
        <h1 class="cms-download-index__title">{{ __('cms::labels.downloads') }}</h1>
    </header>

    <div class="cms-download-grid">
        @forelse($downloads as $download)
        <a
            href="{{ $indexUrl }}/{{ $download->slug }}"
            class="cms-download-card"
        >
            @if($download->cover_image)
            <div class="cms-download-card__image">
                <img
                    src="{{ $download->cover_image }}"
                    alt="{{ $download->translate('title') }}"
                    loading="lazy"
                />
            </div>
            @else
            <div class="cms-download-card__image cms-download-card__image--empty">
                <span class="fa-solid fa-folder-open"></span>
            </div>
            @endif
            <div class="cms-download-card__body">
                <div class="cms-download-card__meta">
                    @if($download->access->value === 'private')
                    <span class="cms-download-card__badge cms-download-card__badge--private">
                        <span class="fa-solid fa-lock"></span> {{ $download->access->label() }}
                    </span>
                    @endif
                </div>
                <h2 class="cms-download-card__title">{{ $download->translate('title') }}</h2>
                @if($download->translate('description'))
                <p class="cms-download-card__desc">{{ $download->translate('description') }}</p>
                @endif
                <span class="cms-download-card__count">
                    {{ $download->files_count }} {{ __('cms::labels.files') }}
                </span>
            </div>
        </a>
        @empty
        <div class="cms-empty">{{ __('cms::labels.no_downloads') }}</div>
        @endforelse
    </div>
</div>
