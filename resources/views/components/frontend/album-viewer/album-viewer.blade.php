@if ($album)
    @php
        $lgImages = $album->images
            ->filter(fn($i) => $i->file)
            ->map(
                fn($i) => [
                    'id' => $i->id,
                    'url' => $i->file->getUrl(),
                    'thumb' => $i->file->getThumbnailUrl() ?: $i->file->getUrl(),
                    'title' => $i->translate('title') ?? '',
                    'caption' => $i->translate('caption') ?? '',
                ],
            )
            ->values();
    @endphp

    <div class="cms-album-viewer">
        <nav
            class="cms-breadcrumb"
            aria-label="{{ __('cms::labels.breadcrumb') }}"
        >
            <ol class="cms-breadcrumb__list">
                <li class="cms-breadcrumb__item"><a
                        href="{{ \VmEngine\Cms\Support\CmsUrl::to('/') }}"
                        class="cms-breadcrumb__link"
                    >{{ __('cms::labels.home') }}</a></li>
                <li class="cms-breadcrumb__item"><a
                        href="{{ $galleryUrl }}"
                        class="cms-breadcrumb__link"
                    >{{ __('cms::labels.gallery') }}</a></li>
                <li
                    class="cms-breadcrumb__item cms-breadcrumb__item--active"
                    aria-current="page"
                >{{ $album->translate('title') }}</li>
            </ol>
        </nav>

        <header class="cms-album__header">
            <h1 class="cms-album__title">{{ $album->translate('title') }}</h1>
            @if ($album->translate('description'))
                <div class="cms-album__description">{{ $album->translate('description') }}</div>
            @endif
        </header>

        <x-cms::field-filters :filter="$fieldFilter" />

        <div
            x-data="cmsAlbumViewer({{ json_encode($lgImages) }})"
            @keydown.escape.window="open && close()"
            @keydown.arrow-left.window="open && prev()"
            @keydown.arrow-right.window="open && next()"
        >
            <div class="cms-gallery-grid">
                @forelse($lgImages as $idx => $img)
                    <figure
                        class="cms-gallery-item"
                        id="item-{{ $img['id'] }}"
                        role="button"
                        tabindex="0"
                        @click="show({{ $idx }})"
                        @keydown.enter="show({{ $idx }})"
                    >
                        <div class="cms-gallery-item__link">
                            <img
                                src="{{ $img['thumb'] }}"
                                alt="{{ $img['title'] ?: $album->translate('title') }}"
                                class="cms-gallery-item__image"
                                loading="lazy"
                            />
                        </div>
                        @if ($img['caption'])
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
                <button
                    class="cms-lightbox__close"
                    type="button"
                    @click="close()"
                    aria-label="{{ __('cms::labels.close') }}"
                >
                    <span class="ph ph-x"></span>
                </button>

                {{-- Counter --}}
                <div
                    class="cms-lightbox__counter"
                    x-text="(current + 1) + ' / ' + images.length"
                ></div>

                {{-- Main image area --}}
                <div class="cms-lightbox__stage">
                    {{-- Prev button --}}
                    <button
                        class="cms-lightbox__nav cms-lightbox__nav--prev"
                        type="button"
                        @click.stop="prev()"
                        x-show="images.length > 1"
                        aria-label="{{ __('cms::labels.previous') }}"
                    >
                        <span class="ph ph-caret-left"></span>
                    </button>

                    <div class="cms-lightbox__image-wrap">
                        <template
                            x-for="(img, idx) in images"
                            :key="idx"
                        >
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
                        aria-label="{{ __('cms::labels.next') }}"
                    >
                        <span class="ph ph-caret-right"></span>
                    </button>
                </div>

                {{-- Caption bar --}}
                <div class="cms-lightbox__caption-bar">
                    <p
                        class="cms-lightbox__title"
                        x-text="currentTitle()"
                        x-show="currentTitle()"
                    ></p>
                    <p
                        class="cms-lightbox__desc"
                        x-text="currentCaption()"
                        x-show="currentCaption()"
                    ></p>
                </div>
            </div>
        </div>
    </div>

    <script nonce="{{ csp_nonce() }}">
    (() => {
        const register = () => {
            Alpine.data('cmsAlbumViewer', (images) => ({
                open: false,
                current: 0,
                images,
                init() {
                    this.openFromHash();
                    window.addEventListener('hashchange', () => this.openFromHash());
                },
                // Search results link to #item-{id}: open that image.
                openFromHash() {
                    const match = window.location.hash.match(/^#item-(\d+)$/);
                    if (!match) {
                        return;
                    }
                    const index = this.images.findIndex((image) => image.id === Number(match[1]));
                    if (index >= 0) {
                        this.show(index);
                    }
                },
                show(index) {
                    this.current = index;
                    this.open = true;
                    document.body.style.overflow = 'hidden';
                },
                close() {
                    this.open = false;
                    document.body.style.overflow = '';
                },
                prev() {
                    this.current = (this.current - 1 + this.images.length) % this.images.length;
                },
                next() {
                    this.current = (this.current + 1) % this.images.length;
                },
                // CSP build: no ?. in attributes, so the caption bar reads through these.
                currentTitle() {
                    const image = this.images[this.current];
                    return image ? image.title : '';
                },
                currentCaption() {
                    const image = this.images[this.current];
                    return image ? image.caption : '';
                },
            }));
        };
    document.addEventListener('alpine:init', register);
    if (window.Alpine) {
        register();
    }
    })();
    </script>
@endif
