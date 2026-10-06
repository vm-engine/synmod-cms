<div x-data="cmsPageSlugForm('{{ $this->title() }}', {{ $sectionId ? 'false' : 'true' }})">
    @include('synapps::components.layouts.partials.breadcrumbs', ['breadcrumbs' => $this->breadcrumbs])

    <x-cms::form-layout
        :back="backend_route('cms.section.index')"
        can="cms.article.section.create|cms.article.section.update"
    >
        <x-synapse-panel :title="__('cms::labels.section_info')">
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
                    <x-cms::rte
                        name="form.description"
                        :value="$form->description"
                        height="250"
                    />
                </div>
                <x-cms::translation-input
                    field="description"
                    type="rte"
                    :label="__('cms::labels.description')"
                    :height="250"
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
                        pickerKey="section-og"
                        wire:key="section-fm-og"
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
                            class="form-label"
                            for="status"
                        >{{ __('cms::labels.status') }}</label>
                        <x-synapse-adv-select
                            wire-model="form.status"
                            :live="true"
                            :options="[
                                ['value' => 'draft', 'label' => 'Draft'],
                                ['value' => 'published', 'label' => 'Published'],
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
                            pickerKey="section-cover"
                            wire:key="section-fm-cover"
                        />
                    </div>
                </div>
            </x-synapse-panel>

            {{-- Display Settings --}}
            <x-synapse-panel :title="__('cms::labels.display_settings')">
                <div class="syn-panel-body space-y-4 p-5 sm:p-6">
                    <div>
                        <label
                            class="form-label"
                            for="items_per_page"
                        >{{ __('cms::labels.items_per_page') }}</label>
                        <input
                            class="form-input"
                            id="items_per_page"
                            type="number"
                            min="1"
                            max="100"
                            wire:model="form.items_per_page"
                        />
                    </div>

                    <div class="grid gap-3 @md:grid-cols-2">
                        <div class="flex items-center gap-3">
                            <x-synapse-toggler
                                id="show_date"
                                wire:model="form.show_date"
                                :inline="true"
                                :checked="$form->show_date"
                                activeColor="green"
                            />
                            <span class="text-sm">{{ __('cms::labels.show_date') }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-synapse-toggler
                                id="show_author"
                                wire:model="form.show_author"
                                :inline="true"
                                :checked="$form->show_author"
                                activeColor="green"
                            />
                            <span class="text-sm">{{ __('cms::labels.show_author') }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-synapse-toggler
                                id="show_thumbnail"
                                wire:model="form.show_thumbnail"
                                :inline="true"
                                :checked="$form->show_thumbnail"
                                activeColor="green"
                            />
                            <span class="text-sm">{{ __('cms::labels.show_thumbnail') }}</span>
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
                        'help' => 'custom_view_section_help',
                        'placeholder' => 'home::templates.faq-accordion',
                    ],
                    [
                        'id' => 'custom_layout',
                        'model' => 'form.custom_layout',
                        'label' => 'custom_layout',
                        'help' => 'custom_layout_section_help',
                        'placeholder' => 'home::layouts.my-layout',
                    ],
                    [
                        'id' => 'custom_category_view',
                        'model' => 'form.custom_category_view',
                        'label' => 'custom_category_view',
                        'help' => 'custom_category_view_help',
                        'placeholder' => 'home::templates.my-category',
                    ],
                    [
                        'id' => 'custom_article_view',
                        'model' => 'form.custom_article_view',
                        'label' => 'custom_article_view',
                        'help' => 'custom_article_view_help',
                        'placeholder' => 'home::templates.my-article',
                    ],
                ],
            ])

            {{-- Custom field definitions (dev only) --}}
            @isDev
                @include('cms::components.backend.partials._field-schema-manager')
                @include('cms::components.backend.partials._media-gallery-toggle', ['help' => __('cms::labels.media_gallery_enable_section_help')])
            @endisDev

            {{-- Custom field values --}}
            <x-cms::custom-field-inputs :schema="$form->inputFieldSchema()" />

            {{-- Custom Variables --}}
            <livewire:cms::backend.custom-var-manager
                wire:model="form.customVars"
                :allow-add="$form->customVarAllowAdd"
                wire:key="cvm-section-{{ $sectionId ?? 'new' }}"
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
