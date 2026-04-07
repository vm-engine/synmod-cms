@if($download)
<div class="cms-download-viewer">
    <nav class="cms-breadcrumb" aria-label="breadcrumb">
        <ol class="cms-breadcrumb__list">
            <li class="cms-breadcrumb__item"><a href="/" class="cms-breadcrumb__link">{{ __('cms::labels.home') }}</a></li>
            <li class="cms-breadcrumb__item"><a href="{{ $indexUrl }}" class="cms-breadcrumb__link">{{ __('cms::labels.downloads') }}</a></li>
            <li class="cms-breadcrumb__item cms-breadcrumb__item--active" aria-current="page">{{ $download->title }}</li>
        </ol>
    </nav>

    <header class="cms-download-viewer__header">
        <h1 class="cms-download-viewer__title">{{ $download->title }}</h1>
        @if($download->access->value === 'private')
        <span class="cms-download-viewer__badge cms-download-viewer__badge--private">
            <span class="fa-solid fa-lock"></span> {{ $download->access->label() }}
        </span>
        @endif
        @if($download->description)
        <div class="cms-download-viewer__description">{{ $download->description }}</div>
        @endif
    </header>

    <div class="cms-download-list">
        @forelse($download->files as $file)
        <div class="cms-download-item">
            <div class="cms-download-item__icon">
                @if($file->file?->isImage())
                    <img src="{{ $file->file->getThumbnailUrl() }}" alt="" class="cms-download-item__thumb" />
                @else
                    <span class="fa-solid fa-file cms-download-item__file-icon"></span>
                @endif
            </div>
            <div class="cms-download-item__info">
                <p class="cms-download-item__name">{{ $file->title ?: ($file->file?->original_name ?? $file->file?->filename ?? 'File') }}</p>
                @if($file->description)
                <p class="cms-download-item__desc">{{ $file->description }}</p>
                @endif
                @if($file->file)
                <p class="cms-download-item__meta">
                    {{ strtoupper($file->file->extension) }} &bull; {{ $file->file->getHumanSize() }}
                </p>
                @endif
            </div>
            <div class="cms-download-item__action">
                @if($file->file)
                <a
                    href="{{ route('cms.download.stream', ['id' => $file->id]) }}"
                    class="cms-download-btn"
                    rel="nofollow"
                >
                    <span class="fa-solid fa-download"></span>
                    {{ __('cms::labels.download') }}
                </a>
                @else
                <span class="cms-download-btn cms-download-btn--disabled">
                    <span class="fa-solid fa-ban"></span>
                    {{ __('cms::labels.unavailable') }}
                </span>
                @endif
            </div>
        </div>
        @empty
        <div class="cms-empty">{{ __('cms::labels.no_files') }}</div>
        @endforelse
    </div>
</div>
@endif
