@if($page)
<div class="cms-widget-page-children">
    <div class="cms-widget-page-children__header">
        @if($showTitle)
        <h2 class="cms-widget__heading">
            <a href="{{ \VmEngine\Cms\Support\CmsUrl::to($page) }}" class="cms-widget__heading-link">{{ $page->translate('title') }}</a>
        </h2>
        @endif
        @if($showExcerpt && $page->translate('excerpt'))
        <p class="cms-widget-page-children__excerpt">{{ $page->translate('excerpt') }}</p>
        @endif
    </div>

    @if($children->isNotEmpty())
    <div class="cms-page__children-grid cms-page__children-grid--{{ $layout }}">
        @foreach($children as $child)
        <div class="cms-page-card">
            @if($showCover && $child->cover_image)
            <div class="cms-page-card__image">
                <a href="{{ \VmEngine\Cms\Support\CmsUrl::to($child) }}">
                    <img src="{{ $child->cover_image }}" alt="{{ $child->translate('title') }}" loading="lazy" />
                </a>
            </div>
            @endif
            <div class="cms-page-card__body">
                <h3 class="cms-page-card__title">
                    <a href="{{ \VmEngine\Cms\Support\CmsUrl::to($child) }}" class="cms-page-card__link">{{ $child->translate('title') }}</a>
                </h3>
                @if($showExcerpt && $child->translate('excerpt'))
                <p class="cms-page-card__excerpt">{{ $child->translate('excerpt') }}</p>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endif
