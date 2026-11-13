<div x-data="{ pageName: '{{ $this->title() }}' }">
    <x-synapse-breadcrumbs :breadcrumbs="$this->breadcrumbs" />

    <form x-data="cmsLocaleTabs('{{ \VmEngine\Cms\Support\CmsLocale::primary() }}')" wire:submit="save">
        <div class="space-y-5 sm:space-y-6">

            {{-- File Preview --}}
            @php $image = $form->image; @endphp
            @if($image && $image->file)
            <div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
                <div class="p-5 flex gap-4 items-center">
                    <div class="flex-none w-12 h-12 rounded border border-gray-200 dark:border-gray-700 flex items-center justify-center bg-gray-50 dark:bg-gray-800 overflow-hidden">
                        @if($image->file->isImage())
                            <img class="w-full h-full object-cover" src="{{ $image->file->getThumbnailUrl() }}" alt="{{ $image->title ?? '' }}" />
                        @else
                            <span class="ph ph-file text-gray-400 text-2xl"></span>
                        @endif
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 space-y-0.5">
                        <p class="font-medium text-gray-700 dark:text-gray-300">{{ $image->file->original_name ?? $image->file->filename }}</p>
                        <p>{{ strtoupper($image->file->extension) }} &bull; {{ $image->file->getHumanSize() }}</p>
                    </div>
                </div>
            </div>
            @endif

            {{-- Title & Caption --}}
            <div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
                <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-base font-medium">{{ __('cms::labels.edit_image') }}</h3>
                </div>
                <div class="p-5 sm:p-6 space-y-4">
                    <x-cms::locale-tabs :values="$form->translations" />
                    <div x-show="isPrimary()">
                        <label class="form-label" for="img_title">{{ __('cms::labels.image_title') }}</label>
                        <input class="form-input" id="img_title" type="text" wire:model="form.title" />
                        @error('form.title') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <x-cms::translation-input
                        field="title"
                        :label="__('cms::labels.image_title')"
                        :placeholder="$form->title"
                    />
                    <div x-show="isPrimary()">
                        <label class="form-label" for="img_caption">{{ __('cms::labels.caption') }}</label>
                        <textarea class="form-input" id="img_caption" rows="3" wire:model="form.caption"></textarea>
                        @error('form.caption') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <x-cms::translation-input
                        field="caption"
                        type="textarea"
                        :label="__('cms::labels.caption')"
                        :placeholder="Str::limit($form->caption, 120)"
                    />
                </div>
            </div>

            <x-cms::custom-field-inputs :schema="$form->inputFieldSchema()" />

            {{-- Custom Variables --}}
            <livewire:cms::backend.custom-var-manager wire:model="form.customVars" :allow-add="$form->customVarAllowAdd" wire:key="cvm-image-{{ $imageId }}" />

        </div>

        <div class="fixed bottom-0 left-0 right-0 z-9 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 px-5 py-3 flex justify-between items-center">
            <a class="btn secondary" href="{{ backend_route('cms.album.images', ['albumId' => $albumId]) }}" wire:navigate>
                <span class="ph ph-arrow-left"></span> {{ __('cms::labels.back') }}
            </a>
            @canAccess('cms.gallery.image.update')
            <button class="btn primary" type="submit" wire:loading.attr="disabled">
                <span class="ph ph-floppy-disk" wire:loading.remove></span>
                <span class="ph ph-spinner syn-spin" wire:loading></span>
                {{ __('cms::labels.save') }}
            </button>
            @endcanAccess
        </div>
        <div class="h-16"></div>
    </form>
</div>
