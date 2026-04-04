<div>
    <x-synapse::breadcrumb :breadcrumbs="$this->breadcrumbs" />

    <form wire:submit="save">
        <div class="content-area max-w-2xl">

            {{-- Image Preview --}}
            @php $image = $form->image; @endphp
            @if($image && $image->file)
            <div class="card mb-4">
                <div class="card-body flex gap-4 items-start">
                    <img
                        src="{{ $image->file->url ?? '' }}"
                        alt="{{ $image->title ?? '' }}"
                        class="w-32 h-24 object-cover rounded-lg border border-gray-200 dark:border-gray-700 flex-shrink-0"
                    />
                    <div class="text-sm text-gray-500 dark:text-gray-400 space-y-1">
                        <p><span class="font-medium text-gray-700 dark:text-gray-300">{{ __('cms::labels.file') }}:</span> {{ $image->file->original_name ?? $image->file->relative_path }}</p>
                        @if($image->file->size)
                        <p><span class="font-medium text-gray-700 dark:text-gray-300">{{ __('cms::labels.file_size') }}:</span> {{ number_format($image->file->size / 1024, 1) }} KB</p>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Title & Caption --}}
            <div class="card">
                <div class="card-body space-y-4">
                    <div>
                        <label class="form-label" for="img_title">{{ __('cms::labels.image_title') }}</label>
                        <input class="form-input" id="img_title" type="text" wire:model="form.title" />
                    </div>
                    <div>
                        <label class="form-label" for="img_caption">{{ __('cms::labels.image_caption') }}</label>
                        <textarea class="form-input" id="img_caption" rows="3" wire:model="form.caption"></textarea>
                    </div>
                </div>
            </div>

            {{-- Custom Variables --}}
            <livewire:cms::backend.custom-var-manager wire:model="form.customVars" :allow-add="$form->customVarAllowAdd" wire:key="cvm-image-{{ $imageId }}" />

        </div>

        {{-- Fixed Action Bar --}}
        <div class="z-9 fixed bottom-0 left-0 right-0 flex items-center justify-between border-t border-gray-200 bg-white px-5 py-3 dark:border-gray-700 dark:bg-gray-900">
            <a
                class="btn secondary"
                href="{{ backend_route('cms.album.images', ['albumId' => $albumId]) }}"
                wire:navigate
            >
                <span class="fa-solid fa-arrow-left"></span> {{ __('cms::labels.back') }}
            </a>
            @canAccess('cms.gallery.image.update')
            <button
                class="btn primary"
                type="submit"
                wire:loading.attr="disabled"
            >
                <span class="fa-solid fa-floppy-disk" wire:loading.remove></span>
                <span class="fa-solid fa-circle-notch fa-spin" wire:loading></span>
                {{ __('cms::labels.save') }}
            </button>
            @endcanAccess
        </div>
        <div class="h-16"></div>
    </form>
</div>
