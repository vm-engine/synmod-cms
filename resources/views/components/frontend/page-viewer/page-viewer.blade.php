@if($page)
<div class="cms-page-viewer">
    @if(($page->config['show_breadcrumb'] ?? true) && $page->parent_id)
    <nav class="cms-breadcrumb" aria-label="{{ __('cms::labels.breadcrumb') }}">
        <ol class="cms-breadcrumb__list">
            <li class="cms-breadcrumb__item"><a href="{{ \VmEngine\Cms\Support\CmsUrl::to('/') }}" class="cms-breadcrumb__link">{{ __('cms::labels.home') }}</a></li>
            @if($page->parent)
            <li class="cms-breadcrumb__item"><a href="{{ \VmEngine\Cms\Support\CmsUrl::to($page->parent) }}" class="cms-breadcrumb__link">{{ $page->parent->translate('title') }}</a></li>
            @endif
            <li class="cms-breadcrumb__item cms-breadcrumb__item--active" aria-current="page">{{ $page->translate('title') }}</li>
        </ol>
    </nav>
    @endif

    <article class="cms-page">
        @if($page->config['show_title'] ?? true)
        <header class="cms-page__header">
            <h1 class="cms-page__title">{{ $page->translate('title') }}</h1>
        </header>
        @endif

        @if($page->cover_image)
        <div class="cms-page__cover">
            <img src="{{ $page->cover_image }}" alt="{{ $page->translate('title') }}" class="cms-page__cover-image" />
        </div>
        @endif

        <div class="cms-page__body">
            {!! $page->translate('body') !!}
        </div>
    </article>

    @if($page->config['show_children'] ?? false)
    <x-cms::field-filters :filter="$fieldFilter" />
    @endif

    @if(($page->config['show_children'] ?? false) && $page->children->isNotEmpty())
    <section class="cms-page__children">
        <h2 class="cms-page__children-title">{{ __('cms::labels.subpages') }}</h2>
        <div class="cms-page__children-grid cms-page__children-grid--{{ $page->config['children_layout'] ?? 'grid' }}">
            @foreach($page->children as $child)
            <div class="cms-page-card">
                @if($child->cover_image)
                <div class="cms-page-card__image">
                    <img src="{{ $child->cover_image }}" alt="{{ $child->translate('title') }}" loading="lazy" />
                </div>
                @endif
                <div class="cms-page-card__body">
                    <h3 class="cms-page-card__title">
                        <a href="{{ \VmEngine\Cms\Support\CmsUrl::to($child) }}" class="cms-page-card__link">{{ $child->translate('title') }}</a>
                    </h3>
                    @if($child->translate('excerpt'))
                    <p class="cms-page-card__excerpt">{{ $child->translate('excerpt') }}</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </section>
    @endif
</div>
@else
<div class="cms-not-found">
    <p>{{ __('cms::labels.page_not_found') }}</p>
</div>
@endif
