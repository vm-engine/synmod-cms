<div>
    @include('synapps::components.layouts.partials.breadcrumbs', ['breadcrumbs' => $this->breadcrumbs])

    <form wire:submit="save">
        <div class="content-area max-w-2xl">

            {{-- File Preview --}}
            @php $dlFile = $form->file; @endphp
            @if($dlFile && $dlFile->file)
            <div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 mb-5">
                <div class="p-5 flex gap-4 items-center">
                    <div class="flex-none w-12 h-12 rounded border border-gray-200 dark:border-gray-700 flex items-center justify-center bg-gray-50 dark:bg-gray-800">
                        @if($dlFile->file->isImage())
                            <img class="w-full h-full object-cover rounded" src="{{ $dlFile->file->getThumbnailUrl() }}" alt="">
                        @else
                            <span class="fa-solid fa-file text-gray-400 text-2xl"></span>
                        @endif
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 space-y-0.5">
                        <p class="font-medium text-gray-700 dark:text-gray-300">{{ $dlFile->file->original_name ?? $dlFile->file->filename }}</p>
                        <p>{{ strtoupper($dlFile->file->extension) }} &bull; {{ $dlFile->file->getHumanSize() }}</p>
                    </div>
                </div>
            </div>
            @endif

            {{-- Title & Description --}}
            <div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
                <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-base font-medium">{{ __('cms::labels.edit_file') }}</h3>
                </div>
                <div class="p-5 sm:p-6 space-y-4">
                    <div>
                        <label class="form-label" for="file_title">{{ __('cms::labels.title') }}</label>
                        <input class="form-input" id="file_title" type="text" wire:model="form.title" />
                        @error('form.title') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label" for="file_description">{{ __('cms::labels.description') }}</label>
                        <textarea class="form-input" id="file_description" rows="4" wire:model="form.description"></textarea>
                        @error('form.description') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Custom Variables --}}
            <livewire:cms::backend.custom-var-manager wire:model="form.customVars" :allow-add="$form->customVarAllowAdd" wire:key="cvm-dlfile-{{ $fileId }}" />

        </div>

        <div class="fixed bottom-0 left-0 right-0 z-9 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 px-5 py-3 flex justify-between items-center">
            <a class="btn secondary" href="{{ backend_route('cms.download.files', ['downloadId' => $downloadId]) }}" wire:navigate>
                <span class="fa-solid fa-arrow-left"></span> {{ __('cms::labels.back') }}
            </a>
            @canAccess('cms.download.file.update')
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
