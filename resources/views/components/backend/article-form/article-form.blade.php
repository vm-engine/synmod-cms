<div x-data="cmsPageSlugForm('{{ $this->title() }}', {{ $articleId ? 'false' : 'true' }})">
    @include('synapps::components.layouts.partials.breadcrumbs', ['breadcrumbs' => $this->breadcrumbs])

    <form x-data="cmsLocaleTabs('{{ \VmEngine\Cms\Support\CmsLocale::primary() }}')" wire:submit.prevent="save">
        <div class="space-y-5 sm:space-y-6">

            {{-- Article Content --}}
            <x-synapse-panel :title="__('cms::labels.article_content')">
                <div class="syn-panel-body p-5 sm:p-6 space-y-4">
                    <x-cms::locale-tabs :values="$form->translations" />
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label required">{{ __('cms::labels.section') }}</label>
                            <input
                                type="hidden"
                                wire:model="form.section_id"
                            />
                            <p
                                class="form-input bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 cursor-default">
                                {{ $this->sectionName }}</p>
                        </div>
                        <div>
                            <label
                                class="form-label"
                                for="category_id"
                            >{{ __('cms::labels.category') }}</label>
                            <x-synapse-adv-select
                                wire-model="form.category_id"
                                :live="true"
                                :options="$this->categoryOptions"
                            />
                        </div>
                    </div>

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
                            height="500"
                        />
                    </div>
                    <x-cms::translation-input
                        field="body"
                        type="rte"
                        :label="__('cms::labels.body')"
                        :height="500"
                        :values="$form->translations"
                    />

                    <div>
                        <label class="form-label">{{ __('cms::labels.tags') }}</label>
                        <x-synapse-adv-select
                            wire-model="form.tag_ids"
                            :multiple="true"
                            :allow-create="true"
                            search-method="searchTags"
                            load-method="loadTags"
                            placeholder="{{ __('cms::labels.tags_placeholder') }}"
                        />
                    </div>
                </div>
            </x-synapse-panel>

            {{-- Publishing --}}
            <x-synapse-panel :title="__('cms::labels.publishing')">
                <div class="syn-panel-body p-5 sm:p-6 grid sm:grid-cols-2 gap-4">
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
                    @php
                        $flatpickrDt = json_encode([
                            'enableTime' => true,
                            'time_24hr' => true,
                            'dateFormat' => 'Y-m-d H:i',
                        ]);
                    @endphp
                    <div>
                        <label
                            class="form-label"
                            for="published_at"
                        >{{ __('cms::labels.publish_date') }}</label>
                        <x-synapse-datepicker
                            id="published_at"
                            wire:model="form.published_at"
                            :flatpickr="$flatpickrDt"
                        />
                    </div>
                    <div>
                        <label
                            class="form-label"
                            for="expires_at"
                        >{{ __('cms::labels.expires_at') }}</label>
                        <x-synapse-datepicker
                            id="expires_at"
                            wire:model="form.expires_at"
                            :flatpickr="$flatpickrDt"
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
                    <div>
                        <label class="form-label">{{ __('cms::labels.cover_image') }}</label>
                        <livewire:fm::file-picker
                            wire:model="form.cover_image"
                            accept="image/*"
                            pickerKey="article-cover"
                            wire:key="article-fm-cover"
                        />
                    </div>
                    <div class="flex items-center gap-3 pt-6">
                        <x-synapse-toggler
                            id="is_featured"
                            wire:model="form.is_featured"
                            :inline="true"
                            :checked="$form->is_featured"
                            activeColor="yellow"
                        />
                        <span class="text-sm font-medium">{{ __('cms::labels.is_featured') }}</span>
                    </div>
                </div>
            </x-synapse-panel>

            {{-- Access --}}
            @include('cms::components.backend.partials._access-panel', [
                'withInherit' => true,
                'withManagers' => false,
                'inheritedValue' => $this->inheritedVisibility,
            ])

            {{-- SEO --}}
            <x-synapse-panel :title="__('cms::labels.seo')">
                <div class="syn-panel-body p-5 sm:p-6 space-y-4">
                    <x-cms::locale-tabs :values="$form->translations" />
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
                    <div>
                        <label class="form-label">{{ __('cms::labels.og_image') }}</label>
                        <livewire:fm::file-picker
                            wire:model="form.og_image"
                            accept="image/*"
                            pickerKey="article-og"
                            wire:key="article-fm-og"
                        />
                    </div>
                </div>
            </x-synapse-panel>

            @include('cms::components.backend.partials._custom-rendering-card', [
                'fields' => [
                    [
                        'id' => 'custom_view',
                        'model' => 'form.custom_view',
                        'label' => 'custom_view',
                        'help' => 'custom_view_article_help',
                        'placeholder' => 'home::templates.my-article',
                    ],
                    [
                        'id' => 'custom_layout',
                        'model' => 'form.custom_layout',
                        'label' => 'custom_layout',
                        'help' => 'custom_layout_article_help',
                        'placeholder' => 'home::layouts.my-layout',
                    ],
                ],
            ])
        </div>

        {{-- Custom Variables --}}
        <livewire:cms::backend.custom-var-manager
            wire:model="form.customVars"
            :allow-add="$form->customVarAllowAdd"
            wire:key="cvm-article-{{ $articleId ?? 'new' }}"
        />

        <div class="syn-form-action-bar">
            <a
                class="btn secondary"
                href="{{ backend_route('cms.section.article.index', ['sectionId' => $sectionId]) }}"
                wire:navigate
            >
                <span class="fa-solid fa-arrow-left"></span> {{ __('cms::labels.back') }}
            </a>
            @canAccess('cms.article.create|cms.article.update')
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
