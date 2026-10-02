<div class="cms-search">
    <form
        class="cms-search__form"
        role="search"
        wire:submit.prevent
    >
        <label
            class="cms-search__label"
            for="cms-search-q"
        >{{ __('cms::labels.search') }}</label>
        <input
            class="cms-search__input"
            id="cms-search-q"
            type="search"
            maxlength="100"
            autocomplete="off"
            wire:model.live.debounce.400ms="q"
        />
    </form>

    {{-- Resolve results first: the rate limiter sets $this->rateLimited while computing them. --}}
    @php $results = $this->results; @endphp
    @if ($this->rateLimited)
        <p class="cms-search__notice" role="status">{{ __('cms::labels.search_rate_limited') }}</p>
    @elseif ($results === null)
        @if ($this->popular !== [])
            <div class="cms-search__popular">
                <h2 class="cms-search__popular-title">{{ __('cms::labels.popular_searches') }}</h2>
                @foreach ($this->popular as $term)
                    <button class="cms-search__chip" type="button" wire:click="$set('q', '{{ addslashes($term) }}')">{{ $term }}</button>
                @endforeach
            </div>
        @endif
    @else
        <nav class="cms-search__tabs" aria-label="{{ __('cms::labels.search_filter') }}">
            @foreach (['' => 'all', ...array_combine(array_keys(\VmEngine\Cms\Services\CmsSearchService::FILTERS), array_keys(\VmEngine\Cms\Services\CmsSearchService::FILTERS))] as $value => $key)
                <button
                    class="cms-search__tab {{ $type === $value ? 'cms-search__tab--active' : '' }}"
                    type="button"
                    aria-pressed="{{ $type === $value ? 'true' : 'false' }}"
                    wire:click="$set('type', '{{ $value }}')"
                >
                    {{ __('cms::labels.search_filter_'.$key) }}
                    <span class="cms-search__tab-count" data-count="{{ $this->counts[$key] ?? 0 }}">{{ $this->counts[$key] ?? 0 }}</span>
                </button>
            @endforeach
        </nav>

        @forelse ($this->results as $result)
            <article class="cms-search-result">
                @if ($result->image)
                    <img class="cms-search-result__image" src="{{ $result->image }}" alt="" loading="lazy" />
                @endif
                <div class="cms-search-result__body">
                    <span class="cms-search-result__type">{{ __('cms::labels.search_type_'.$result->searchable_type) }}</span>
                    <h3 class="cms-search-result__title">
                        <a href="{{ $result->url }}">{{ \VmEngine\Cms\Services\CmsSearchService::highlight($result->title, $this->term()) }}</a>
                    </h3>
                    <p class="cms-search-result__snippet">{{ \VmEngine\Cms\Services\CmsSearchService::highlight(\VmEngine\Cms\Services\CmsSearchService::snippet($result->excerpt ?: $result->content, $this->term()), $this->term()) }}</p>
                    @if ($result->parent_label)
                        <p class="cms-search-result__parent">{{ __('cms::labels.search_in', ['parent' => $result->parent_label]) }}</p>
                    @endif
                    @if ($result->published_at)
                        <time class="cms-search-result__date" datetime="{{ $result->published_at->toDateString() }}">{{ $result->published_at->translatedFormat('d F Y') }}</time>
                    @endif
                </div>
            </article>
        @empty
            <p class="cms-search__empty">{{ __('cms::labels.search_no_results', ['q' => $this->term()]) }}</p>
        @endforelse

        {{ $this->results->links() }}
    @endif
</div>
