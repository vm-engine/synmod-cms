@if ($image->file)
    <figure class="cms-gallery-card">
        <img src="{{ $image->file->getUrl() }}" alt="{{ $image->translate('title') ?? '' }}" loading="lazy" class="cms-gallery-card__image" />
        @if (($showTitle ?? true) && $image->translate('title'))
            <figcaption class="cms-gallery-card__title">{{ $image->translate('title') }}</figcaption>
        @endif
        @if (($showCaption ?? true) && $image->translate('caption'))
            <p class="cms-gallery-card__caption">{{ $image->translate('caption') }}</p>
        @endif
    </figure>
@endif
