<div x-data="cmsPageSlugForm('{{ $this->title() }}', {{ $albumId ? 'false' : 'true' }})">
    <x-synapse-breadcrumbs :breadcrumbs="$this->breadcrumbs" />

    <x-cms::form-layout
        :back="backend_route('cms.album.index')"
        :can="$form->type === 'banner' ? 'cms.gallery.banner.create|cms.gallery.banner.update' : 'cms.gallery.album.create|cms.gallery.album.update'"
    >
        <x-synapse-panel :title="__('cms::labels.album_info')">
            <div class="syn-panel-body space-y-4 p-5 sm:p-6">
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
                            <span class="ph ph-magic-wand"></span>
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
            </div>
        </x-synapse-panel>

        {{-- SEO --}}
        <x-synapse-panel
            :title="__('cms::labels.seo')"
            collapsible
            collapsed
        >
            <div class="syn-panel-body space-y-4 p-5 sm:p-6">
                <x-cms::locale-tabs :values="$form->translations" />
                <div x-show="isPrimary()">
                    <label
                        class="form-label"
                        for="meta_title"
                    >{{ __('cms::labels.meta_title') }}</label>
                    <input
                        class="form-input @error('form.meta_title') has-error @enderror"
                        id="meta_title"
                        type="text"
                        wire:model="form.meta_title"
                    />
                    @error('form.meta_title')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
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
                        class="form-input @error('form.meta_description') has-error @enderror"
                        id="meta_description"
                        rows="3"
                        wire:model="form.meta_description"
                    ></textarea>
                    @error('form.meta_description')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
                <x-cms::translation-input
                    field="meta_description"
                    type="textarea"
                    :label="__('cms::labels.meta_description')"
                    :placeholder="Str::limit($form->meta_description, 120)"
                />
            </div>
        </x-synapse-panel>

        <x-slot:sidebar>
            {{-- Publishing --}}
            <x-synapse-panel :title="__('cms::labels.publishing')">
                <div class="syn-panel-body grid gap-4 p-5 @md:grid-cols-2 sm:p-6">
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
                    <div class="@md:col-span-2">
                        <label class="form-label">{{ __('cms::labels.cover_image') }}</label>
                        <livewire:fm::file-picker
                            wire:model="form.cover_image"
                            accept="image/*"
                            pickerKey="album-cover"
                            wire:key="album-fm-cover"
                        />
                    </div>
                </div>
            </x-synapse-panel>

            {{-- Access --}}
            @include('cms::components.backend.partials._access-panel', [
                'withInherit' => false,
                'withManagers' => true,
                'inheritedValue' => null,
            ])
        </x-slot:sidebar>

        <x-slot:after>
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

            {{-- Custom field definitions (dev only) --}}
            @isDev
                @include('cms::components.backend.partials._field-schema-manager')
            @endisDev

            {{-- Custom field values --}}
            <x-cms::custom-field-inputs :schema="$form->inputFieldSchema()" />

            {{-- Custom Variables --}}
            <livewire:cms::backend.custom-var-manager
                wire:model="form.customVars"
                :allow-add="$form->customVarAllowAdd"
                wire:key="cvm-album-{{ $albumId ?? 'new' }}"
            />
        </x-slot:after>
    </x-cms::form-layout>
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
