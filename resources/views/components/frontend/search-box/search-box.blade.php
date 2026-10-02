<div class="cms-search-box">
    <form
        class="cms-search-box__form"
        role="search"
        method="get"
        action="{{ $this->searchUrl() }}"
        x-data="cmsSearchBox('cms-search-box-{{ $this->getId() }}')"
        @click.outside="close()"
        @keydown.escape="close()"
    >
        <input
            class="cms-search-box__input"
            type="search"
            name="q"
            maxlength="100"
            autocomplete="off"
            placeholder="{{ $placeholder ?: __('cms::labels.search') }}"
            aria-label="{{ __('cms::labels.search') }}"
            role="combobox"
            aria-autocomplete="list"
            aria-controls="cms-search-box-{{ $this->getId() }}"
            :aria-expanded="open ? 'true' : 'false'"
            :aria-activedescendant="activeId()"
            wire:model.live.debounce.300ms="q"
            @focus="show()"
            @keydown.arrow-down.prevent="move(1)"
            @keydown.arrow-up.prevent="move(-1)"
            @keydown.enter="choose($event)"
        />

        <ul
            class="cms-search-box__list"
            id="cms-search-box-{{ $this->getId() }}"
            role="listbox"
            x-ref="list"
            x-show="open"
            style="display: none"
        >
            @if (mb_strlen(trim($q)) >= 2)
                @foreach ($this->suggestions as $suggestion)
                    <li
                        class="cms-search-box__option"
                        id="cms-search-box-{{ $this->getId() }}-{{ $loop->index }}"
                        role="option"
                    >
                        <a href="{{ $suggestion->url }}">
                            @if ($suggestion->image)
                                <img class="cms-search-box__thumb" src="{{ $suggestion->image }}" alt="" loading="lazy" />
                            @endif
                            <span class="cms-search-box__type">{{ __('cms::labels.search_type_'.$suggestion->searchable_type) }}</span>
                            <span class="cms-search-box__title">{{ $suggestion->title }}</span>
                        </a>
                    </li>
                @endforeach
                <li
                    class="cms-search-box__see-all"
                    id="cms-search-box-{{ $this->getId() }}-all"
                    role="option"
                >
                    <a href="{{ $this->searchUrl(trim($q)) }}">{{ __('cms::labels.search_see_all', ['q' => trim($q)]) }}</a>
                </li>
            @elseif ($this->popular !== [])
                <li class="cms-search-box__heading" role="presentation">{{ __('cms::labels.popular_searches') }}</li>
                @foreach ($this->popular as $term)
                    <li
                        class="cms-search-box__popular"
                        id="cms-search-box-{{ $this->getId() }}-p{{ $loop->index }}"
                        role="option"
                    >
                        <a href="{{ $this->searchUrl($term) }}">{{ $term }}</a>
                    </li>
                @endforeach
            @endif
        </ul>
    </form>
</div>
