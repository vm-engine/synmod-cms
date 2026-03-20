<div x-data="{ pageName: '{{ $this->title() }}', generateSlug(v) { return v.toLowerCase().replace(/[^a-z0-9\s-]/g,'').replace(/\s+/g,'-').replace(/-+/g,'-').trim(); } }">
    @include('synapps::components.layouts.partials.breadcrumbs', ['breadcrumbs' => $this->breadcrumbs])

    <form wire:submit.prevent="save">
        <div class="space-y-5 sm:space-y-6">

            {{-- Article Content --}}
            <div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
                <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-base font-medium">{{ __('cms::labels.article_content') }}</h3>
                </div>
                <div class="p-5 sm:p-6 space-y-4">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label required">{{ __('cms::labels.section') }}</label>
                            <input type="hidden" wire:model="form.section_id" />
                            <p class="form-input bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 cursor-default">{{ $this->sectionName }}</p>
                        </div>
                        <div>
                            <label class="form-label" for="category_id">{{ __('cms::labels.category') }}</label>
                            <x-synapse-select id="category_id" wire:model.live="form.category_id" :options="$this->categoryOptions" />
                        </div>
                    </div>

                    <div>
                        <label class="form-label required" for="title">{{ __('cms::labels.title') }}</label>
                        <input class="form-input @error('form.title') !border-red-500 @enderror" id="title" type="text" wire:model.blur="form.title" @blur="if (!$wire.form.slug) $wire.set('form.slug', generateSlug($event.target.value)); if (!$wire.form.meta_title) $wire.set('form.meta_title', $event.target.value)" />
                        @error('form.title') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div x-data="{ autoSlug: {{ $articleId ? 'false' : 'true' }} }">
                        <label class="form-label required" for="slug">{{ __('cms::labels.slug') }}</label>
                        <div class="input-group">
                            <input
                                class="form-input grow @error('form.slug') !border-red-500 @enderror"
                                id="slug"
                                type="text"
                                @input="autoSlug = false"
                                wire:model.live="form.slug"
                            />
                            <button
                                class="input-group-item right btn"
                                type="button"
                                @click="$wire.set('form.slug', generateSlug($wire.form.title)); autoSlug = false"
                                title="{{ __('cms::labels.fill_from_title') }}"
                            >
                                <span class="fa-solid fa-wand-magic-sparkles"></span>
                            </button>
                        </div>
                        @error('form.slug') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="form-label" for="excerpt">{{ __('cms::labels.excerpt') }}</label>
                        <textarea class="form-input" id="excerpt" rows="3" wire:model="form.excerpt"></textarea>
                    </div>

                    <div>
                        <label class="form-label" for="body">{{ __('cms::labels.body') }}</label>
                        <x-cms::tinymce name="form.body" :value="$form->body" height="500" />
                    </div>

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
            </div>

            {{-- Publishing --}}
            <div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
                <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-base font-medium">{{ __('cms::labels.publishing') }}</h3>
                </div>
                <div class="p-5 sm:p-6 grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label required" for="status">{{ __('cms::labels.status') }}</label>
                        <x-synapse-select id="status" wire:model.live="form.status" :options="['draft' => 'Draft', 'published' => 'Published', 'scheduled' => 'Scheduled']" />
                    </div>
                    @php
                        $flatpickrDt = json_encode(['enableTime' => true, 'time_24hr' => true, 'dateFormat' => 'Y-m-d H:i']);
                    @endphp
                    <div>
                        <label class="form-label" for="published_at">{{ __('cms::labels.publish_date') }}</label>
                        <x-synapse-datepicker id="published_at" wire:model="form.published_at" :flatpickr="$flatpickrDt" />
                    </div>
                    <div>
                        <label class="form-label" for="expires_at">{{ __('cms::labels.expires_at') }}</label>
                        <x-synapse-datepicker id="expires_at" wire:model="form.expires_at" :flatpickr="$flatpickrDt" />
                    </div>
                    <div>
                        <label class="form-label" for="sort_order">{{ __('cms::labels.sort_order') }}</label>
                        <input class="form-input" id="sort_order" type="number" min="0" wire:model="form.sort_order" />
                    </div>
                    <div>
                        <label class="form-label">{{ __('cms::labels.cover_image') }}</label>
                        <livewire:fm::file-picker wire:model="form.cover_image" accept="image/*" pickerKey="article-cover" wire:key="article-fm-cover" />
                    </div>
                    <div class="flex items-center gap-3 pt-6">
                        <x-synapse-toggler id="is_featured" wire:model="form.is_featured" :inline="true" :checked="$form->is_featured" activeColor="yellow" />
                        <span class="text-sm font-medium">{{ __('cms::labels.is_featured') }}</span>
                    </div>
                </div>
            </div>

            {{-- SEO --}}
            <div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
                <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-base font-medium">{{ __('cms::labels.seo') }}</h3>
                </div>
                <div class="p-5 sm:p-6 space-y-4">
                    <div>
                        <label class="form-label" for="meta_title">{{ __('cms::labels.meta_title') }}</label>
                        <input class="form-input" id="meta_title" type="text" wire:model="form.meta_title" />
                    </div>
                    <div>
                        <label class="form-label" for="meta_description">{{ __('cms::labels.meta_description') }}</label>
                        <textarea class="form-input" id="meta_description" rows="3" wire:model="form.meta_description"></textarea>
                    </div>
                    <div>
                        <label class="form-label">{{ __('cms::labels.og_image') }}</label>
                        <livewire:fm::file-picker wire:model="form.og_image" accept="image/*" pickerKey="article-og" wire:key="article-fm-og" />
                    </div>
                </div>
            </div>
        </div>

        <div class="fixed bottom-0 left-0 right-0 z-9 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 px-5 py-3 flex justify-between items-center">
            <a class="btn secondary" href="{{ backend_route('cms.section.article.index', ['sectionId' => $sectionId]) }}" wire:navigate>
                <span class="fa-solid fa-arrow-left"></span> {{ __('cms::labels.back') }}
            </a>
            @canAccess('cms.article.create|cms.article.update')
            <button class="btn primary" type="submit" wire:loading.attr="disabled">
                <span class="fa-solid fa-floppy-disk" wire:loading.remove></span>
                <span class="fa-solid fa-circle-notch fa-spin" wire:loading></span>
                {{ __('cms::labels.save') }}
            </button>
            @endcanAccess
        </div>
        <div class="h-16"></div>
    </form>
</div>
