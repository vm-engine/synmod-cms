@if ($image->file)
    <figure class="cms-gallery-card">
        <img src="{{ $image->file->getUrl() }}" alt="{{ $image->title ?? '' }}" loading="lazy" class="cms-gallery-card__image" />
        @if (($showTitle ?? true) && $image->title)
            <figcaption class="cms-gallery-card__title">{{ $image->title }}</figcaption>
        @endif
        @if (($showCaption ?? true) && $image->caption)
            <p class="cms-gallery-card__caption">{{ $image->caption }}</p>
        @endif
    </figure>
@endif
