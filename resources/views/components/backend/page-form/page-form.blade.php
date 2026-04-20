<div x-data="{ pageName: '{{ $this->title() }}', generateSlug(v) { return v.toLowerCase().replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-').trim(); } }">
    @include('synapps::components.layouts.partials.breadcrumbs', ['breadcrumbs' => $this->breadcrumbs])

    <form wire:submit.prevent="save">
        <div class="space-y-5 sm:space-y-6">

            {{-- Main Content --}}
            <div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
                <div class="border-b border-gray-100 px-5 py-4 sm:px-6 sm:py-5 dark:border-gray-800">
                    <h3 class="text-base font-medium">{{ __('cms::labels.page_content') }}</h3>
                </div>
                <div class="space-y-4 p-5 sm:p-6">
                    <div>
                        <label
                            class="form-label required"
                            for="title"
                        >{{ __('cms::labels.title') }}</label>
                        <input
                            class="form-input @error('form.title') !border-red-500 @enderror"
                            id="title"
                            type="text"
                            wire:model.blur="form.title"
                            @blur="if (!$wire.form.slug) $wire.set('form.slug', generateSlug($event.target.value)); if (!$wire.form.meta_title) $wire.set('form.meta_title', $event.target.value)"
                        />
                        @error('form.title')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-data="{ autoSlug: {{ $pageId ? 'false' : 'true' }} }">
                        <label
                            class="form-label required"
                            for="slug"
                        >{{ __('cms::labels.slug') }}</label>
                        <div class="input-group">
                            <input
                                class="form-input @error('form.slug') !border-red-500 @enderror grow"
                                id="slug"
                                type="text"
                                @input="autoSlug = false"
                                wire:model.live="form.slug"
                            />
                            <button
                                class="input-group-item right btn"
                                type="button"
                                title="{{ __('cms::labels.fill_from_title') }}"
                                @click="$wire.set('form.slug', generateSlug($wire.form.title)); autoSlug = false"
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
                        <x-synapse-select
                            id="parent_id"
                            wire:model.live="form.parent_id"
                            :options="$this->parentOptions"
                            :disabled="$parentLocked"
                        />
                        @if($parentLocked)
                            <p class="mt-1 text-xs text-gray-400">{{ __('cms::labels.parent_locked') }}</p>
                        @endif
                    </div>

                    <div>
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

                    <div>
                        <label
                            class="form-label"
                            for="body"
                        >{{ __('cms::labels.body') }}</label>
                        <x-cms::tinymce
                            name="form.body"
                            :value="$form->body"
                            height="400"
                        />
                        @error('form.body')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Publishing --}}
            <div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
                <div class="border-b border-gray-100 px-5 py-4 sm:px-6 sm:py-5 dark:border-gray-800">
                    <h3 class="text-base font-medium">{{ __('cms::labels.publishing') }}</h3>
                </div>
                <div class="grid gap-4 p-5 sm:grid-cols-2 sm:p-6">
                    <div>
                        <label
                            class="form-label required"
                            for="status"
                        >{{ __('cms::labels.status') }}</label>
                        <x-synapse-select
                            id="status"
                            wire:model.live="form.status"
                            :options="['draft' => 'Draft', 'published' => 'Published', 'scheduled' => 'Scheduled']"
                        />
                    </div>
                    @if ($form->status === 'scheduled')
                        <div>
                            <label
                                class="form-label"
                                for="published_at"
                            >{{ __('cms::labels.publish_date') }}</label>
                            @php
                                $now = $form->published_at = now()->format('Y-m-d H:i');
                                $flatpickr = json_encode([
                                    'enableTime' => true,
                                    'time_24hr' => true,
                                    'dateFormat' => 'Y-m-d H:i',
                                    'defaultDate' => $now,
                                ]);
                            @endphp
                            <x-synapse-datepicker
                                id="published_at"
                                wire:model="form.published_at"
                                :flatpickr="$flatpickr"
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
            </div>

            {{-- Page Settings --}}
            <div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
                <div class="border-b border-gray-100 px-5 py-4 sm:px-6 sm:py-5 dark:border-gray-800">
                    <h3 class="text-base font-medium">{{ __('cms::labels.page_settings') }}</h3>
                </div>
                <div class="space-y-3 p-5 sm:p-6">
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
                            id="show_children"
                            wire:model="form.show_children"
                            :inline="true"
                            :checked="$form->show_children"
                            activeColor="green"
                        />
                        <span class="text-sm">{{ __('cms::labels.show_children') }}</span>
                    </div>
                </div>
            </div>

            {{-- SEO --}}
            <div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
                <div class="border-b border-gray-100 px-5 py-4 sm:px-6 sm:py-5 dark:border-gray-800">
                    <h3 class="text-base font-medium">{{ __('cms::labels.seo') }}</h3>
                </div>
                <div class="space-y-4 p-5 sm:p-6">
                    <div>
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
                    <div>
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
                    <div>
                        <label class="form-label">{{ __('cms::labels.og_image') }}</label>
                        <livewire:fm::file-picker
                            wire:model="form.og_image"
                            accept="image/*"
                            pickerKey="page-og"
                            wire:key="page-fm-og"
                        />
                    </div>
                </div>
            </div>

            @include('cms::components.backend.partials._custom-rendering-card', [
                'fields' => [
                    ['id' => 'custom_view', 'model' => 'form.custom_view', 'label' => 'custom_view', 'help' => 'custom_view_page_help', 'placeholder' => 'home::templates.inner-page'],
                    ['id' => 'custom_layout', 'model' => 'form.custom_layout', 'label' => 'custom_layout', 'help' => 'custom_layout_page_help', 'placeholder' => 'home::layouts.my-layout'],
                ],
            ])

        </div>

        {{-- Custom Variables --}}
        <livewire:cms::backend.custom-var-manager wire:model="form.customVars" :allow-add="$form->customVarAllowAdd" wire:key="cvm-page-{{ $pageId ?? 'new' }}" />

        {{-- Fixed Action Bar --}}
        <div
            class="z-9 fixed bottom-0 left-0 right-0 flex items-center justify-between border-t border-gray-200 bg-white px-5 py-3 dark:border-gray-700 dark:bg-gray-900">
            <a
                class="btn secondary"
                href="{{ backend_route('cms.page.index') }}"
                wire:navigate
            >
                <span class="fa-solid fa-arrow-left"></span> {{ __('cms::labels.back') }}
            </a>
            @canAccess('cms.page.create|cms.page.update')
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
