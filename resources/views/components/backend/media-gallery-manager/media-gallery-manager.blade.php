<div class="mt-6">
    <x-synapse-confirm-dialog />

    <x-synapse-panel :title="__('cms::labels.media_galleries')">
        <div class="syn-panel-body space-y-4 p-5 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <p class="form-help">{{ __('cms::labels.media_galleries_hint') }}</p>
                <button
                    class="btn xs primary"
                    type="button"
                    wire:click="create"
                >
                    <span class="fa-solid fa-plus"></span> {{ __('cms::labels.media_gallery_add') }}
                </button>
            </div>

            @forelse ($this->galleries as $gallery)
                <div
                    class="flex flex-wrap items-center gap-3 rounded border border-gray-200 p-3 dark:border-gray-700"
                    wire:key="mg-{{ $gallery->id }}"
                >
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">{{ $gallery->title }}</p>
                        <p class="text-xs text-gray-400">
                            {{ __('cms::labels.media_gallery_layout_' . $gallery->layout) }} &bull;
                            {{ $gallery->images_count }} {{ __('cms::labels.media_gallery_images') }}
                        </p>
                    </div>
                    <div
                        class="flex items-center gap-1"
                        x-data="cmsCopyGalleryTag({{ json_encode($gallery->tag()) }})"
                    >
                        <code
                            class="rounded bg-gray-100 px-2 py-1 text-xs dark:bg-gray-800">{{ $gallery->tag() }}</code>
                        <button
                            class="btn xs secondary"
                            type="button"
                            title="{{ __('cms::labels.media_gallery_copy_tag') }}"
                            @click="copy()"
                        >
                            <span
                                class="fa-solid fa-copy"
                                x-show="! copied"
                            ></span>
                            <span
                                class="fa-solid fa-check"
                                x-show="copied"
                                x-cloak
                            ></span>
                        </button>
                    </div>
                    <div class="flex gap-1">
                        <button
                            class="btn xs secondary"
                            type="button"
                            title="{{ __('cms::labels.media_gallery_images') }}"
                            wire:click="openImages({{ $gallery->id }})"
                        >
                            <span class="fa-solid fa-images"></span>
                        </button>
                        <button
                            class="btn xs secondary"
                            type="button"
                            title="{{ __('cms::labels.edit') }}"
                            wire:click="edit({{ $gallery->id }})"
                        >
                            <span class="fa-solid fa-pen"></span>
                        </button>
                        <button
                            class="btn xs danger"
                            type="button"
                            data-title="{{ __('cms::labels.delete') }}"
                            data-message="{{ __('cms::labels.media_gallery_delete_confirm') }}"
                            data-confirm="{{ __('cms::labels.yes_delete') }}"
                            data-component="{{ $this->getId() }}"
                            @click="$dispatch('confirm-dialog', {
                                title: $el.dataset.title,
                                message: $el.dataset.message,
                                confirmText: $el.dataset.confirm,
                                confirmColor: 'danger',
                                icon: 'fa-solid fa-trash',
                                wireMethod: 'deleteGallery',
                                wireParams: ['{{ $gallery->delete_token }}'],
                                wireComponent: $el.dataset.component
                            })"
                        >
                            <span class="fa-solid fa-trash"></span>
                        </button>
                    </div>
                </div>
            @empty
                <p class="syn-empty-state-text">{{ __('cms::labels.media_gallery_empty') }}</p>
            @endforelse

            @if ($this->activeGallery)
                <div
                    class="space-y-4 rounded border border-gray-200 p-4 dark:border-gray-700"
                    wire:key="mg-images-{{ $this->activeGallery->id }}"
                >
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-medium">
                            {{ $this->activeGallery->title }}: {{ __('cms::labels.media_gallery_images') }}
                        </h4>
                        <button
                            class="btn xs secondary"
                            type="button"
                            wire:click="closeImages"
                        >
                            <span class="fa-solid fa-xmark"></span>
                        </button>
                    </div>

                    <div>
                        <label class="form-label">{{ __('cms::labels.media_gallery_add_image') }}</label>
                        <div class="flex items-start gap-2">
                            <div class="flex-1">
                                <livewire:fm::file-picker
                                    wire:model="newImageUrl"
                                    accept="image/*"
                                    pickerKey="media-gallery-add"
                                    wire:key="mg-fm-add-{{ $this->activeGallery->id }}"
                                />
                            </div>
                            <button
                                class="btn primary"
                                type="button"
                                wire:click="addImage"
                            >
                                <span class="fa-solid fa-plus"></span>
                            </button>
                        </div>
                        @error('newFileId')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                        @error('newImageUrl')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div
                        class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                        x-data="cmsSortableMediaGalleryImages()"
                    >
                        @forelse ($this->activeGallery->images as $image)
                            <div
                                class="rounded-lg border border-gray-200 dark:border-gray-700"
                                data-id="{{ $image->id }}"
                                wire:key="mg-img-{{ $image->id }}"
                            >
                                <div class="relative aspect-video bg-gray-100 dark:bg-gray-800">
                                    @if ($image->file)
                                        <img
                                            class="h-full w-full rounded-t-lg object-cover"
                                            src="{{ $image->file->getThumbnailUrl() }}"
                                            alt=""
                                            loading="lazy"
                                        />
                                    @endif
                                    <div class="absolute right-1 top-1 flex gap-1">
                                        <span
                                            class="btn xs secondary mg-drag-handle cursor-grab"
                                            title="Drag to reorder"
                                        >
                                            <span class="fa-solid fa-grip-vertical"></span>
                                        </span>
                                        <button
                                            class="btn xs danger"
                                            type="button"
                                            data-title="{{ __('cms::labels.delete') }}"
                                            data-message="{{ __('cms::labels.media_gallery_image_delete_confirm') }}"
                                            data-confirm="{{ __('cms::labels.yes_delete') }}"
                                            data-component="{{ $this->getId() }}"
                                            @click="$dispatch('confirm-dialog', {
                                                title: $el.dataset.title,
                                                message: $el.dataset.message,
                                                confirmText: $el.dataset.confirm,
                                                confirmColor: 'danger',
                                                icon: 'fa-solid fa-trash',
                                                wireMethod: 'removeImage',
                                                wireParams: ['{{ $image->delete_token }}'],
                                                wireComponent: $el.dataset.component
                                            })"
                                        >
                                            <span class="fa-solid fa-trash"></span>
                                        </button>
                                    </div>
                                </div>
                                <div class="space-y-2 p-2">
                                    @foreach ($this->locales as $locale)
                                        <input
                                            class="form-input text-sm"
                                            type="text"
                                            placeholder="{{ __('cms::labels.caption') }} ({{ strtoupper($locale) }})"
                                            wire:model="captions.{{ $image->id }}.{{ $locale }}"
                                        />
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="syn-empty-state-text">{{ __('cms::labels.media_gallery_no_images') }}</p>
                        @endforelse
                    </div>

                    @if ($this->activeGallery->images->isNotEmpty())
                        <div class="flex justify-end">
                            <button
                                class="btn sm primary"
                                type="button"
                                wire:click="saveCaptions"
                            >
                                {{ __('cms::labels.media_gallery_save_captions') }}
                            </button>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </x-synapse-panel>

    <x-synapse-modal
        name="media-gallery-form"
        maxWidth="lg"
    >
        <div class="syn-modal-header">
            <h3 class="syn-modal-title">
                {{ $editingId ? __('cms::labels.media_gallery_edit') : __('cms::labels.media_gallery_add') }}
            </h3>
            <button
                class="syn-modal-close"
                type="button"
                @click="$dispatch('close-modal-media-gallery-form')"
            >
                <span class="fa-solid fa-xmark"></span>
            </button>
        </div>
        <div class="syn-modal-body space-y-4">
            <div>
                <label
                    class="form-label required"
                    for="mg_title"
                >{{ __('cms::labels.title') }}</label>
                <input
                    class="form-input"
                    id="mg_title"
                    type="text"
                    wire:model.live.debounce.400ms="title"
                />
                @error('title')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label
                    class="form-label required"
                    for="mg_key"
                >{{ __('cms::labels.media_gallery_key') }}</label>
                <input
                    class="form-input font-mono text-sm"
                    id="mg_key"
                    type="text"
                    wire:model="key"
                />
                @error('key')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label
                    class="form-label required"
                    for="mg_layout"
                >{{ __('cms::labels.media_gallery_layout') }}</label>
                <select
                    class="form-select"
                    id="mg_layout"
                    wire:model="layout"
                >
                    <option value="slider">{{ __('cms::labels.media_gallery_layout_slider') }}</option>
                    <option value="grid">{{ __('cms::labels.media_gallery_layout_grid') }}</option>
                </select>
                @error('layout')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div class="syn-modal-footer">
            <button
                class="btn secondary"
                type="button"
                @click="$dispatch('close-modal-media-gallery-form')"
            >{{ __('cms::labels.cancel') }}</button>
            <button
                class="btn primary"
                type="button"
                wire:click="saveGallery"
            >{{ __('cms::labels.save') }}</button>
        </div>
    </x-synapse-modal>
</div>

<script nonce="{{ csp_nonce() }}">
    (() => {
        const register = () => {
            Alpine.data('cmsCopyGalleryTag', (tag) => ({
                tag,
                copied: false,
                copy() {
                    navigator.clipboard.writeText(this.tag);
                    this.copied = true;
                    setTimeout(() => {
                        this.copied = false;
                    }, 1500);
                },
            }));

            Alpine.data('cmsSortableMediaGalleryImages', () => ({
                init() {
                    new Sortable(this.$el, {
                        animation: 150,
                        handle: '.mg-drag-handle',
                        onEnd: () => {
                            const ids = Array.from(this.$el.querySelectorAll(
                                    '[data-id]'))
                                .map(el => parseInt(el.dataset.id));
                            this.$wire.updateOrder(ids);
                        },
                    });
                },
            }));
        };
        document.addEventListener('alpine:init', register);
        if (window.Alpine) {
            register();
        }
    })();
</script>
