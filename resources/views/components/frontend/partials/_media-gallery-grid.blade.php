<div class="cms-media-gallery cms-media-gallery--grid">
    @foreach ($slides as $slide)
        <figure class="cms-media-gallery__item">
            <a
                href="{{ $slide['url'] }}"
                target="_blank"
                rel="noopener"
            >
                <img
                    class="cms-media-gallery__image"
                    src="{{ $slide['url'] }}"
                    alt="{{ $slide['alt'] }}"
                    loading="lazy"
                />
            </a>
            @if ($slide['caption'] !== '')
                <figcaption class="cms-media-gallery__caption">{{ $slide['caption'] }}</figcaption>
            @endif
        </figure>
    @endforeach
</div>
