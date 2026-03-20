@if($album)
<div class="cms-album-viewer">
    <nav class="cms-breadcrumb" aria-label="breadcrumb">
        <ol class="cms-breadcrumb__list">
            <li class="cms-breadcrumb__item"><a href="/" class="cms-breadcrumb__link">Home</a></li>
            <li class="cms-breadcrumb__item cms-breadcrumb__item--active">{{ $album->title }}</li>
        </ol>
    </nav>

    <header class="cms-album__header">
        <h1 class="cms-album__title">{{ $album->title }}</h1>
        @if($album->description)
        <div class="cms-album__description">{{ $album->description }}</div>
        @endif
    </header>

    <div class="cms-gallery-grid">
        @forelse($album->images as $image)
        @php
            $imgUrl = $image->file ? $image->file->getUrl() : null;
        @endphp
        @if($imgUrl)
        <figure class="cms-gallery-item">
            <a href="{{ $imgUrl }}" class="cms-gallery-item__link" target="_blank">
                <img
                    src="{{ $image->file->getThumbnailUrl() }}"
                    alt="{{ $image->title ?? $album->title }}"
                    class="cms-gallery-item__image"
                    loading="lazy"
                />
            </a>
            @if($image->caption)
            <figcaption class="cms-gallery-item__caption">{{ $image->caption }}</figcaption>
            @endif
        </figure>
        @endif
        @empty
        <div class="cms-empty">{{ __('cms::labels.no_images') }}</div>
        @endforelse
    </div>
</div>
@endif
