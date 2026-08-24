<div x-data="cmsAlbumImagePreview('{{ $this->title() }}')">
    <x-synapse-confirm-dialog />
    @include('synapps::components.layouts.partials.breadcrumbs', ['breadcrumbs' => $this->breadcrumbs])

    <div class="space-y-5 sm:space-y-6">

        {{-- Add image --}}
        @canAccess('cms.gallery.image.create')
        <div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
            <div class="border-b border-gray-100 px-5 py-4 sm:px-6 sm:py-5 dark:border-gray-800">
                <h3 class="text-base font-medium">{{ __('cms::labels.add_image') }}</h3>
            </div>
            <div class="p-5 sm:p-6">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-3">
                        <label class="form-label required">{{ __('cms::labels.image_url') }}</label>
                        <livewire:fm::file-picker
                            wire:model="newImageUrl"
                            accept="image/*"
                            pickerKey="album-add"
                            wire:key="album-fm-add"
                        />
                        @error('newImageUrl')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label
                            class="form-label"
                            for="newImageTitle"
                        >{{ __('cms::labels.image_title') }}</label>
                        <input
                            class="form-input"
                            id="newImageTitle"
                            type="text"
                            wire:model="newImageTitle"
                        />
                    </div>
                    <div>
                        <label
                            class="form-label"
                            for="newImageCaption"
                        >{{ __('cms::labels.caption') }}</label>
                        <input
                            class="form-input"
                            id="newImageCaption"
                            type="text"
                            wire:model="newImageCaption"
                        />
                    </div>
                    <div class="flex items-end">
                        <button
                            class="btn primary w-full"
                            type="button"
                            wire:click="addImage"
                        >
                            <span class="fa-solid fa-plus"></span> {{ __('cms::labels.add_image') }}
                        </button>
                    </div>
                </div>

                <livewire:cms::backend.custom-var-manager
                    wire:model="newImageCustomVars"
                    :allow-add="$newImageCustomVarAllowAdd"
                    wire:key="cvm-new-image-{{ $albumId }}"
                />
            </div>
        </div>
        @endcanAccess

        {{-- Image grid --}}
        <div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
            <div class="border-b border-gray-100 px-5 py-4 sm:px-6 sm:py-5 dark:border-gray-800">
                <h3 class="text-base font-medium">{{ __('cms::labels.images') }} ({{ $this->images->count() }})</h3>
            </div>
            <div
                class="grid grid-cols-2 gap-4 p-5 sm:grid-cols-3 sm:p-6 lg:grid-cols-4"
                x-data="cmsSortableAlbumImages()"
            >
                @forelse($this->images as $image)
                    <div
                        class="group relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700"
                        data-id="{{ $image->id }}"
                    >
                        <div class="aspect-square bg-gray-100 dark:bg-gray-800">
                            @if ($image->file)
                                <img
                                    class="h-full w-full cursor-zoom-in object-cover"
                                    src="{{ $image->file->getUrl() }}"
                                    alt="{{ $image->title }}"
                                    loading="lazy"
                                    @click.stop="showPreview('{{ $image->file->getUrl() }}', '{{ addslashes((string) $image->title) }}')"
                                />
                            @else
                                <div class="flex h-full items-center justify-center text-gray-400">
                                    <span class="fa-solid fa-image text-3xl"></span>
                                </div>
                            @endif
                        </div>
                        @if ($image->title)
                            <div class="truncate px-2 py-1 text-xs">{{ $image->title }}</div>
                        @endif
                        <div class="absolute right-1 top-1 hidden gap-1 group-hover:flex">
                            @canAccess('cms.gallery.image.delete')
                            <button
                                class="btn xs danger"
                                type="button"
                                data-title="{{ __('cms::labels.delete') }}"
                                data-message="{{ __('cms::labels.delete_image_confirm') }}"
                                data-confirm="{{ __('cms::labels.yes_delete') }}"
                                data-id="{{ $image->id }}"
                                data-component="{{ $this->getId() }}"
                                @click.stop="$dispatch('confirm-dialog', {
                                    title: $el.dataset.title,
                                    message: $el.dataset.message,
                                    confirmText: $el.dataset.confirm,
                                    confirmColor: 'danger',
                                    icon: 'fa-solid fa-trash',
                                    wireMethod: 'deleteImage',
                                    wireParams: [parseInt($el.dataset.id)],
                                    wireComponent: $el.dataset.component
                                })"
                            >
                                <span class="fa-solid fa-trash"></span>
                            </button>
                            @endcanAccess
                            @canAccess('cms.gallery.image.update')
                            <a
                                class="btn xs secondary"
                                href="{{ backend_route('cms.album.image.form', ['albumId' => $albumId, 'id' => $image->id]) }}"
                                title="{{ __('cms::labels.edit') }}"
                                wire:navigate
                            >
                                <span class="fa-solid fa-pen"></span>
                            </a>
                            @endcanAccess
                            <span
                                class="btn xs secondary cursor-grab"
                                title="Drag to reorder"
                            >
                                <span class="fa-solid fa-grip-vertical"></span>
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="col-span-4 py-8 text-center text-gray-400">
                        {{ __('cms::labels.no_images') }}
                    </div>
                @endforelse
            </div>
        </div>

        <div class="flex justify-start">
            <a
                class="btn secondary"
                href="{{ backend_route('cms.album.index') }}"
                wire:navigate
            >
                <span class="fa-solid fa-arrow-left"></span> {{ __('cms::labels.back') }}
            </a>
        </div>
    </div>

    {{-- Image preview lightbox --}}
    <div
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/80"
        x-show="previewUrl !== null"
        x-cloak
        x-transition
        @click="closePreview()"
        @keydown.escape.window="closePreview()"
    >
        <div
            class="relative max-h-[90vh] max-w-[90vw]"
            @click.stop
        >
            <img
                class="max-h-[85vh] max-w-[85vw] rounded-lg object-contain shadow-2xl"
                :src="previewUrl"
                :alt="previewTitle"
            />
            <div
                class="mt-2 text-center text-sm text-white/80"
                x-show="previewTitle"
                x-text="previewTitle"
            ></div>
            <button
                class="absolute -right-3 -top-3 flex h-8 w-8 items-center justify-center rounded-full bg-white text-gray-800 shadow-lg hover:bg-gray-100"
                type="button"
                @click="closePreview()"
            >
                <span class="fa-solid fa-times text-sm"></span>
            </button>
        </div>
    </div>

</div>

<script nonce="{{ csp_nonce() }}">
    (() => {
        const register = () => {
        Alpine.data('cmsAlbumImagePreview', (pageName) => ({
            pageName,
            previewUrl: null,
            previewTitle: null,
            showPreview(url, title) {
                this.previewUrl = url;
                this.previewTitle = title;
            },
            closePreview() {
                this.previewUrl = null;
                this.previewTitle = null;
            },
        }));

        Alpine.data('cmsSortableAlbumImages', () => ({
            init() {
                new Sortable(this.$el, {
                    animation: 150,
                    onEnd: () => {
                        const ids = Array.from(this.$el.querySelectorAll('[data-id]'))
                            .map(el =>
                                parseInt(el.dataset.id));
                        this.$wire.updateOrder(ids);
                    }
                });
            }
        }));
        };
    document.addEventListener('alpine:init', register);
    if (window.Alpine) {
        register();
    }
    })();
</script>
