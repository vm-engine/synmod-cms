@if($album)
@php
$lgImages = $album->images->filter(fn($i) => $i->file)->map(fn($i) => [
    'url'     => $i->file->getUrl(),
    'thumb'   => $i->file->getThumbnailUrl() ?: $i->file->getUrl(),
    'title'   => $i->title ?? '',
    'caption' => $i->caption ?? '',
])->values();
@endphp

<div class="cms-album-viewer">
    <nav class="cms-breadcrumb" aria-label="breadcrumb">
        <ol class="cms-breadcrumb__list">
            <li class="cms-breadcrumb__item"><a href="/" class="cms-breadcrumb__link">{{ __('cms::labels.home') }}</a></li>
            <li class="cms-breadcrumb__item"><a href="{{ $galleryUrl }}" class="cms-breadcrumb__link">{{ __('cms::labels.gallery') }}</a></li>
            <li class="cms-breadcrumb__item cms-breadcrumb__item--active" aria-current="page">{{ $album->title }}</li>
        </ol>
    </nav>

    <header class="cms-album__header">
        <h1 class="cms-album__title">{{ $album->title }}</h1>
        @if($album->description)
        <div class="cms-album__description">{{ $album->description }}</div>
        @endif
    </header>

    <div
        x-data="{
            open: false,
            current: 0,
            images: @js($lgImages),
            show(index) { this.current = index; this.open = true; document.body.style.overflow = 'hidden'; },
            close() { this.open = false; document.body.style.overflow = ''; },
            prev() { this.current = (this.current - 1 + this.images.length) % this.images.length; },
            next() { this.current = (this.current + 1) % this.images.length; },
        }"
        @keydown.escape.window="open && close()"
        @keydown.arrow-left.window="open && prev()"
        @keydown.arrow-right.window="open && next()"
    >
        <div class="cms-gallery-grid">
            @forelse($lgImages as $idx => $img)
            <figure
                class="cms-gallery-item"
                role="button"
                tabindex="0"
                @click="show({{ $idx }})"
                @keydown.enter="show({{ $idx }})"
            >
                <div class="cms-gallery-item__link">
                    <img
                        src="{{ $img['thumb'] }}"
                        alt="{{ $img['title'] ?: $album->title }}"
                        class="cms-gallery-item__image"
                        loading="lazy"
                    />
                </div>
                @if($img['caption'])
                <figcaption class="cms-gallery-item__caption">{{ $img['caption'] }}</figcaption>
                @endif
            </figure>
            @empty
            <div class="cms-empty">{{ __('cms::labels.no_images') }}</div>
            @endforelse
        </div>

        {{-- Lightbox --}}
        <div
            class="cms-lightbox"
            x-show="open"
            x-cloak
            x-transition:enter="cms-lightbox--enter"
            x-transition:enter-start="cms-lightbox--hidden"
            x-transition:enter-end="cms-lightbox--visible"
            x-transition:leave="cms-lightbox--enter"
            x-transition:leave-start="cms-lightbox--visible"
            x-transition:leave-end="cms-lightbox--hidden"
            @click.self="close()"
            role="dialog"
            aria-modal="true"
        >
            {{-- Close button --}}
            <button class="cms-lightbox__close" type="button" @click="close()" aria-label="Close">
                <span class="fa-solid fa-times"></span>
            </button>

            {{-- Counter --}}
            <div class="cms-lightbox__counter" x-text="(current + 1) + ' / ' + images.length"></div>

            {{-- Main image area --}}
            <div class="cms-lightbox__stage">
                {{-- Prev button --}}
                <button
                    class="cms-lightbox__nav cms-lightbox__nav--prev"
                    type="button"
                    @click.stop="prev()"
                    x-show="images.length > 1"
                    aria-label="Previous"
                >
                    <span class="fa-solid fa-chevron-left"></span>
                </button>

                <div class="cms-lightbox__image-wrap">
                    <template x-for="(img, idx) in images" :key="idx">
                        <img
                            class="cms-lightbox__image"
                            :src="img.url"
                            :alt="img.title || ''"
                            x-show="idx === current"
                            x-transition:enter="cms-lightbox__image--enter"
                            x-transition:enter-start="cms-lightbox__image--hidden"
                            x-transition:enter-end="cms-lightbox__image--visible"
                            loading="lazy"
                        />
                    </template>
                </div>

                {{-- Next button --}}
                <button
                    class="cms-lightbox__nav cms-lightbox__nav--next"
                    type="button"
                    @click.stop="next()"
                    x-show="images.length > 1"
                    aria-label="Next"
                >
                    <span class="fa-solid fa-chevron-right"></span>
                </button>
            </div>

            {{-- Caption bar --}}
            <div class="cms-lightbox__caption-bar">
                <p class="cms-lightbox__title" x-text="images[current]?.title" x-show="images[current]?.title"></p>
                <p class="cms-lightbox__desc" x-text="images[current]?.caption" x-show="images[current]?.caption"></p>
            </div>
        </div>
    </div>
</div>
@endif
