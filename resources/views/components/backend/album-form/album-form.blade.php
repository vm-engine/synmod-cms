<div x-data="cmsPageSlugForm('{{ $this->title() }}', {{ $albumId ? 'false' : 'true' }})">
    @include('synapps::components.layouts.partials.breadcrumbs', ['breadcrumbs' => $this->breadcrumbs])

    <form x-data="cmsLocaleTabs('{{ \VmEngine\Cms\Support\CmsLocale::primary() }}')" wire:submit.prevent="save">
        <div class="space-y-5 sm:space-y-6">
            <div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
                <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-base font-medium">{{ __('cms::labels.album_info') }}</h3>
                </div>
                <div class="p-5 sm:p-6 space-y-4">
                    <x-cms::locale-tabs :values="$form->translations" />
                    <div x-show="isPrimary()">
                        <label
                            class="form-label required"
                            for="title"
                        >{{ __('cms::labels.title') }}</label>
                        <input
                            class="form-input @error('form.title') has-error @enderror"
                            id="title"
                            type="text"
                            wire:model.blur="form.title"
                            @blur="handleTitleBlur($event.target.value)"
                        />
                        @error('form.title')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <x-cms::translation-input
                        field="title"
                        :label="__('cms::labels.title')"
                        :placeholder="$form->title"
                    />

                    <div>
                        <label
                            class="form-label required"
                            for="slug"
                        >{{ __('cms::labels.slug') }}</label>
                        <div class="input-group">
                            <input
                                class="form-input grow @error('form.slug') has-error @enderror"
                                id="slug"
                                type="text"
                                @input="autoSlug = false"
                                wire:model.live="form.slug"
                            />
                            <button
                                class="input-group-item right btn"
                                type="button"
                                @click="fillSlugFromTitle()"
                                title="{{ __('cms::labels.fill_from_title') }}"
                            >
                                <span class="fa-solid fa-wand-magic-sparkles"></span>
                            </button>
                        </div>
                        @error('form.slug')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-show="isPrimary()">
                        <label
                            class="form-label"
                            for="description"
                        >{{ __('cms::labels.description') }}</label>
                        <textarea
                            class="form-input"
                            id="description"
                            rows="4"
                            wire:model="form.description"
                        ></textarea>
                    </div>
                    <x-cms::translation-input
                        field="description"
                        type="textarea"
                        :label="__('cms::labels.description')"
                        :rows="4"
                        :placeholder="Str::limit($form->description, 120)"
                    />

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label
                                class="form-label required"
                                for="type"
                            >{{ __('cms::labels.type') }}</label>
                            <x-synapse-adv-select
                                wire-model="form.type"
                                :live="true"
                                :options="$this->typeOptions"
                            />
                        </div>
                        <div>
                            <label
                                class="form-label required"
                                for="status"
                            >{{ __('cms::labels.status') }}</label>
                            <x-synapse-adv-select
                                wire-model="form.status"
                                :live="true"
                                :options="[
                                    ['value' => 'active', 'label' => 'Active'],
                                    ['value' => 'inactive', 'label' => 'Inactive'],
                                ]"
                            />
                        </div>
                        <div>
                            <label
                                class="form-label"
                                for="sort_order"
                            >{{ __('cms::labels.sort_order') }}</label>
                            <input
                                class="form-input"
                                id="sort_order"
                                type="number"
                                min="0"
                                wire:model="form.sort_order"
                            />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="form-label">{{ __('cms::labels.cover_image') }}</label>
                            <livewire:fm::file-picker
                                wire:model="form.cover_image"
                                accept="image/*"
                                pickerKey="album-cover"
                                wire:key="album-fm-cover"
                            />
                        </div>
                    </div>

                    <div x-show="isPrimary()">
                        <label
                            class="form-label"
                            for="meta_title"
                        >{{ __('cms::labels.meta_title') }}</label>
                        <input
                            class="form-input"
                            id="meta_title"
                            type="text"
                            wire:model="form.meta_title"
                        />
                    </div>
                    <x-cms::translation-input
                        field="meta_title"
                        :label="__('cms::labels.meta_title')"
                        :placeholder="$form->meta_title"
                    />
                    <div x-show="isPrimary()">
                        <label
                            class="form-label"
                            for="meta_description"
                        >{{ __('cms::labels.meta_description') }}</label>
                        <textarea
                            class="form-input"
                            id="meta_description"
                            rows="3"
                            wire:model="form.meta_description"
                        ></textarea>
                    </div>
                    <x-cms::translation-input
                        field="meta_description"
                        type="textarea"
                        :label="__('cms::labels.meta_description')"
                        :placeholder="Str::limit($form->meta_description, 120)"
                    />
                </div>
            </div>

            @include('cms::components.backend.partials._custom-rendering-card', [
                'fields' => [
                    [
                        'id' => 'custom_view',
                        'model' => 'form.custom_view',
                        'label' => 'custom_view',
                        'help' => 'custom_view_album_help',
                        'placeholder' => 'home::templates.my-album',
                    ],
                    [
                        'id' => 'custom_layout',
                        'model' => 'form.custom_layout',
                        'label' => 'custom_layout',
                        'help' => 'custom_layout_album_help',
                        'placeholder' => 'home::layouts.my-layout',
                    ],
                ],
            ])
        </div>

        {{-- Custom Variables --}}
        <livewire:cms::backend.custom-var-manager
            wire:model="form.customVars"
            :allow-add="$form->customVarAllowAdd"
            wire:key="cvm-album-{{ $albumId ?? 'new' }}"
        />

        <div
            class="fixed bottom-0 left-0 right-0 z-9 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 px-5 py-3 flex justify-between items-center">
            <a
                class="btn secondary"
                href="{{ backend_route('cms.album.index') }}"
                wire:navigate
            >
                <span class="fa-solid fa-arrow-left"></span> {{ __('cms::labels.back') }}
            </a>
            @if ($form->type === 'banner')
                @canAccess('cms.gallery.banner.create|cms.gallery.banner.update')
                <button
                    class="btn primary"
                    type="submit"
                    wire:loading.attr="disabled"
                >
                    <span
                        class="fa-solid fa-floppy-disk"
                        wire:loading.remove
                    ></span>
                    <span
                        class="fa-solid fa-circle-notch fa-spin"
                        wire:loading
                    ></span>
                    {{ __('cms::labels.save') }}
                </button>
                @endcanAccess
            @else
                @canAccess('cms.gallery.album.create|cms.gallery.album.update')
                <button
                    class="btn primary"
                    type="submit"
                    wire:loading.attr="disabled"
                >
                    <span
                        class="fa-solid fa-floppy-disk"
                        wire:loading.remove
                    ></span>
                    <span
                        class="fa-solid fa-circle-notch fa-spin"
                        wire:loading
                    ></span>
                    {{ __('cms::labels.save') }}
                </button>
                @endcanAccess
            @endif
        </div>
        <div class="h-16"></div>
    </form>
</div>

<script nonce="{{ csp_nonce() }}">
    (() => {
        const register = () => {
            Alpine.data('cmsPageSlugForm', (pageName, autoSlug) => ({
                pageName,
                autoSlug,
                generateSlug(v) {
                    return v.toLowerCase().replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-')
                        .replace(
                            /-+/g, '-').trim();
                },
                handleTitleBlur(value) {
                    if (!this.$wire.form.slug) {
                        this.$wire.set('form.slug', this.generateSlug(value));
                    }
                    if (!this.$wire.form.meta_title) {
                        this.$wire.set('form.meta_title', value);
                    }
                },
                fillSlugFromTitle() {
                    this.$wire.set('form.slug', this.generateSlug(this.$wire.form.title));
                    this.autoSlug = false;
                },
            }));
        };
        document.addEventListener('alpine:init', register);
        if (window.Alpine) {
            register();
        }
    })();
</script>
