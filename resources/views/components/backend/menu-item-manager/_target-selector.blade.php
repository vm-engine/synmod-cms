{{-- $targetType, $wireModelUrl, $wireModelTargetId --}}
@if ($targetType === 'album_list')
    <label class="form-label">{{ __('cms::labels.gallery_slug') }}</label>
    <input
        class="form-input"
        type="text"
        wire:model="{{ $wireModelUrl }}"
        placeholder="gallery"
    />
    <p class="mt-1 text-xs text-gray-400">{{ __('cms::labels.gallery_slug_hint') }}</p>
    @error($wireModelUrl)
        <p class="form-error">{{ $message }}</p>
    @enderror
@elseif ($targetType === 'download_list')
    <label class="form-label">{{ __('cms::labels.download_slug') }}</label>
    <input
        class="form-input"
        type="text"
        wire:model="{{ $wireModelUrl }}"
        placeholder="downloads"
    />
    <p class="mt-1 text-xs text-gray-400">{{ __('cms::labels.download_slug_hint') }}</p>
    @error($wireModelUrl)
        <p class="form-error">{{ $message }}</p>
    @enderror
@elseif ($targetType === 'url')
    <label class="form-label">{{ __('cms::labels.url') }}</label>
    <input
        class="form-input"
        type="text"
        wire:model="{{ $wireModelUrl }}"
        placeholder="https://..."
    />
    @error($wireModelUrl)
        <p class="form-error">{{ $message }}</p>
    @enderror
@else
    <label class="form-label required">{{ __('cms::labels.select_target') }}</label>
    @if ($targetType === 'page')
        <x-synapse-adv-select
            wire-model="{{ $wireModelTargetId }}"
            search-method="searchPages"
            load-method="loadPages"
        />
    @elseif ($targetType === 'section')
        <x-synapse-adv-select
            wire-model="{{ $wireModelTargetId }}"
            search-method="searchSections"
            load-method="loadSections"
        />
    @elseif ($targetType === 'category')
        <x-synapse-adv-select
            wire-model="{{ $wireModelTargetId }}"
            search-method="searchCategories"
            load-method="loadCategories"
        />
    @elseif ($targetType === 'article')
        <x-synapse-adv-select
            wire-model="{{ $wireModelTargetId }}"
            search-method="searchArticles"
            load-method="loadArticles"
        />
    @elseif ($targetType === 'album')
        <x-synapse-adv-select
            wire-model="{{ $wireModelTargetId }}"
            search-method="searchAlbums"
            load-method="loadAlbums"
        />
    @elseif ($targetType === 'album_image')
        <x-synapse-adv-select
            wire-model="{{ $wireModelTargetId }}"
            search-method="searchAlbumImages"
            load-method="loadAlbumImages"
        />
    @elseif ($targetType === 'download')
        <x-synapse-adv-select
            wire-model="{{ $wireModelTargetId }}"
            search-method="searchDownloads"
            load-method="loadDownloads"
        />
    @endif
    @error($wireModelTargetId)
        <p class="form-error">{{ $message }}</p>
    @enderror
@endif
