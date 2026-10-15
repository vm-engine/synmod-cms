<div x-data="cmsPageSlugForm('{{ $this->title() }}', {{ $pageId ? 'false' : 'true' }})">
    @include('synapps::components.layouts.partials.breadcrumbs', ['breadcrumbs' => $this->breadcrumbs])

    <x-cms::form-layout
        :back="backend_route('cms.page.index')"
        can="cms.page.create|cms.page.update"
    >
        {{-- Main Content --}}
        <x-synapse-panel :title="__('cms::labels.page_content')">
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
                            class="form-input @error('form.slug') has-error @enderror grow"
                            id="slug"
                            type="text"
                            @input="autoSlug = false"
                            wire:model.live="form.slug"
                        />
                        <button
                            class="input-group-item right btn"
                            type="button"
                            title="{{ __('cms::labels.fill_from_title') }}"
                            @click="fillSlugFromTitle()"
                        >
                            <span class="fa-solid fa-wand-magic-sparkles"></span>
                        </button>
                    </div>
                    @error('form.slug')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-400">{{ __('cms::labels.slug_hint') }}</p>
                </div>

                <div>
                    <label
                        class="form-label"
                        for="parent_id"
                    >{{ __('cms::labels.parent_page') }}</label>
                    <x-synapse-adv-select
                        wire-model="form.parent_id"
                        :live="true"
                        :options="$this->parentOptions"
                        :disabled="$parentLocked"
                    />
                    @if ($parentLocked)
                        <p class="mt-1 text-xs text-gray-400">{{ __('cms::labels.parent_locked') }}</p>
                    @endif
                </div>

                <div x-show="isPrimary()">
                    <label
                        class="form-label"
                        for="excerpt"
                    >{{ __('cms::labels.excerpt') }}</label>
                    <textarea
                        class="form-input"
                        id="excerpt"
                        rows="3"
                        wire:model="form.excerpt"
                    ></textarea>
                </div>
                <x-cms::translation-input
                    field="excerpt"
                    type="textarea"
                    :label="__('cms::labels.excerpt')"
                    :placeholder="Str::limit($form->excerpt, 120)"
                />

                <div x-show="isPrimary()">
                    <label
                        class="form-label"
                        for="body"
                    >{{ __('cms::labels.body') }}</label>
                    <x-cms::rte
                        name="form.body"
                        :value="$form->body"
                        height="400"
                    />
                    @error('form.body')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
                <x-cms::translation-input
                    field="body"
                    type="rte"
                    :label="__('cms::labels.body')"
                    :height="400"
                    :values="$form->translations"
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
                <div>
                    <label class="form-label">{{ __('cms::labels.og_image') }}</label>
                    <livewire:fm::file-picker
                        wire:model="form.og_image"
                        accept="image/*"
                        pickerKey="page-og"
                        wire:key="page-fm-og"
                    />
                    @error('form.og_image')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </x-synapse-panel>

        <x-slot:sidebar>
            {{-- Publishing --}}
            <x-synapse-panel :title="__('cms::labels.publishing')">
                <div class="syn-panel-body grid gap-4 p-5 @md:grid-cols-2 sm:p-6">
                    <div>
                        <label
                            class="form-label required"
                            for="status"
                        >{{ __('cms::labels.status') }}</label>
                        <x-synapse-adv-select
                            wire-model="form.status"
                            :live="true"
                            :options="[
                                ['value' => 'draft', 'label' => 'Draft'],
                                ['value' => 'published', 'label' => 'Published'],
                                ['value' => 'scheduled', 'label' => 'Scheduled'],
                            ]"
                        />
                    </div>
                    @if ($form->status === 'scheduled')
                        <div>
                            <label
                                class="form-label"
                                for="published_at"
                            >{{ __('cms::labels.publish_date') }}</label>
                            <x-synapse-datepicker
                                id="published_at"
                                wire:model="form.published_at"
                                :flatpickr="json_encode(['enableTime' => true, 'time_24hr' => true, 'dateFormat' => 'Y-m-d H:i'])"
                            />
                        </div>
                    @endif
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
                    <div>
                        <label class="form-label">{{ __('cms::labels.cover_image') }}</label>
                        <livewire:fm::file-picker
                            wire:model="form.cover_image"
                            accept="image/*"
                            pickerKey="page-cover"
                            wire:key="page-fm-cover"
                        />
                    </div>
                </div>
            </x-synapse-panel>

            {{-- Access --}}
            @include('cms::components.backend.partials._access-panel', [
                'withInherit' => $this->form->parent_id !== null,
                'withManagers' => true,
                'inheritedValue' => $this->inheritedVisibility,
            ])

            {{-- Page Settings --}}
            <x-synapse-panel :title="__('cms::labels.page_settings')">
                <div class="syn-panel-body space-y-3 p-5 sm:p-6">
                    <div class="flex items-center gap-3">
                        <x-synapse-toggler
                            id="show_title"
                            wire:model="form.show_title"
                            :inline="true"
                            :checked="$form->show_title"
                            activeColor="green"
                        />
                        <span class="text-sm">{{ __('cms::labels.show_title') }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-synapse-toggler
                            id="show_breadcrumb"
                            wire:model="form.show_breadcrumb"
                            :inline="true"
                            :checked="$form->show_breadcrumb"
                            activeColor="green"
                        />
                        <span class="text-sm">{{ __('cms::labels.show_breadcrumb') }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-synapse-toggler
                            id="show_excerpt"
                            wire:model="form.show_excerpt"
                            :inline="true"
                            :checked="$form->show_excerpt"
                            activeColor="green"
                        />
                        <span class="text-sm">{{ __('cms::labels.show_excerpt') }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-synapse-toggler
                            id="show_in_sitemap"
                            wire:model="form.show_in_sitemap"
                            :inline="true"
                            :checked="$form->show_in_sitemap"
                            activeColor="green"
                        />
                        <span class="text-sm">{{ __('cms::labels.show_in_sitemap') }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-synapse-toggler
                            id="show_submenu"
                            wire:model="form.show_submenu"
                            :inline="true"
                            :checked="$form->show_submenu"
                            activeColor="green"
                        />
                        <span class="text-sm">{{ __('cms::labels.show_submenu') }}</span>
                    </div>
                    <div>
                        <label
                            class="form-label"
                            for="children_display"
                        >{{ __('cms::labels.children_display') }}</label>
                        <x-synapse-adv-select
                            wire-model="form.children_display"
                            :options="$this->childrenDisplayOptions"
                        />
                        @error('form.children_display')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </x-synapse-panel>
        </x-slot:sidebar>

        <x-slot:after>
            @include('cms::components.backend.partials._custom-rendering-card', [
                'fields' => [
                    [
                        'id' => 'custom_view',
                        'model' => 'form.custom_view',
                        'label' => 'custom_view',
                        'help' => 'custom_view_page_help',
                        'placeholder' => 'home::templates.inner-page',
                    ],
                    [
                        'id' => 'custom_layout',
                        'model' => 'form.custom_layout',
                        'label' => 'custom_layout',
                        'help' => 'custom_layout_page_help',
                        'placeholder' => 'home::layouts.my-layout',
                    ],
                ],
            ])

            {{-- Custom field definitions (dev only) --}}
            @isDev
                <div class="card">
                    <div class="card-body flex items-start gap-3">
                        <x-synapse-toggler
                            id="allow_child_create"
                            wire:model="form.allow_child_create"
                            :inline="true"
                            :checked="$form->allow_child_create"
                            activeColor="green"
                        />
                        <div>
                            <span class="text-sm">{{ __('cms::labels.allow_child_create') }}</span>
                            <p class="form-help">{{ __('cms::labels.allow_child_create_help') }}</p>
                        </div>
                    </div>
                </div>
                @if ($form->parent_id === null)
                    @include('cms::components.backend.partials._field-schema-manager')
                    @include('cms::components.backend.partials._media-gallery-toggle', ['help' => __('cms::labels.media_gallery_enable_page_help')])
                @endif
            @endisDev

            {{-- Custom field values --}}
            <x-cms::custom-field-inputs :schema="$form->inputFieldSchema()" />

            {{-- Custom Variables --}}
            <livewire:cms::backend.custom-var-manager
                wire:model="form.customVars"
                :allow-add="$form->customVarAllowAdd"
                wire:key="cvm-page-{{ $pageId ?? 'new' }}"
            />
        </x-slot:after>
    </x-cms::form-layout>

    @if ($this->mediaGalleriesAvailable)
        <div class="mb-20">
            @if ($pageId)
                <livewire:cms::backend.media-gallery-manager
                    :owner="$form->page"
                    wire:key="mgm-page-{{ $pageId }}"
                />
            @else
                <p class="form-help mt-6">{{ __('cms::labels.media_galleries_save_first') }}</p>
            @endif
        </div>
    @endif
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
