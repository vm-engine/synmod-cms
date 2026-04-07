<div x-data="{ pageName: '{{ $this->title() }}' }">
    <x-synapse-confirm-dialog />
    @include('synapps::components.layouts.partials.breadcrumbs', ['breadcrumbs' => $this->breadcrumbs])

    <div class="space-y-5 sm:space-y-6">

        {{-- Add file --}}
        @canAccess('cms.download.file.create')
        <div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
            <div class="border-b border-gray-100 px-5 py-4 sm:px-6 sm:py-5 dark:border-gray-800">
                <h3 class="text-base font-medium">{{ __('cms::labels.add_file') }}</h3>
            </div>
            <div class="p-5 sm:p-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="form-label required">{{ __('cms::labels.file') }}</label>
                        <livewire:fm::file-picker
                            wire:model="newFileUrl"
                            pickerKey="download-add"
                            wire:key="download-fm-add"
                        />
                        @error('newFileUrl')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="form-label" for="newFileTitle">{{ __('cms::labels.title') }}</label>
                        <input class="form-input" id="newFileTitle" type="text" wire:model="newFileTitle" />
                    </div>
                    <div>
                        <label class="form-label" for="newFileDescription">{{ __('cms::labels.description') }}</label>
                        <input class="form-input" id="newFileDescription" type="text" wire:model="newFileDescription" />
                    </div>
                    <div class="sm:col-span-2 flex justify-end">
                        <button class="btn primary" type="button" wire:click="addFile">
                            <span class="fa-solid fa-plus"></span> {{ __('cms::labels.add_file') }}
                        </button>
                    </div>
                </div>

                <livewire:cms::backend.custom-var-manager
                    wire:model="newFileCustomVars"
                    :allow-add="$newFileCustomVarAllowAdd"
                    wire:key="cvm-new-file-{{ $downloadId }}"
                />
            </div>
        </div>
        @endcanAccess

        {{-- File list --}}
        <div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
            <div class="border-b border-gray-100 px-5 py-4 sm:px-6 sm:py-5 dark:border-gray-800">
                <h3 class="text-base font-medium">{{ __('cms::labels.files') }} ({{ $this->files->count() }})</h3>
            </div>
            <div
                class="divide-y divide-gray-100 dark:divide-gray-800"
                x-data="{
                    init() {
                        new Sortable(this.$el, {
                            animation: 150,
                            handle: '.drag-handle',
                            onEnd: (evt) => {
                                const ids = Array.from(this.$el.querySelectorAll('[data-id]')).map(el => parseInt(el.dataset.id));
                                $wire.updateOrder(ids);
                            }
                        });
                    }
                }"
            >
                @forelse($this->files as $file)
                    <div class="flex items-center gap-4 px-5 py-3" data-id="{{ $file->id }}">
                        <span class="drag-handle fa-solid fa-grip-vertical cursor-grab text-gray-300 hover:text-gray-500"></span>
                        <div class="flex-none w-10 h-10 rounded border border-gray-200 dark:border-gray-700 flex items-center justify-center bg-gray-50 dark:bg-gray-800">
                            @if($file->file?->isImage())
                                <img class="w-full h-full object-cover rounded" src="{{ $file->file->getThumbnailUrl() }}" alt="">
                            @else
                                <span class="fa-solid fa-file text-gray-400 text-lg"></span>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-sm truncate">{{ $file->title ?: ($file->file?->original_name ?? $file->file?->filename ?? 'Untitled') }}</p>
                            @if($file->description)
                                <p class="text-xs text-gray-400 truncate">{{ $file->description }}</p>
                            @endif
                            @if($file->file)
                                <p class="text-xs text-gray-400">{{ $file->file->getHumanSize() }} &bull; {{ strtoupper($file->file->extension) }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-1 flex-none">
                            @canAccess('cms.download.file.update')
                            <a
                                class="btn-icon warning has-tooltip group"
                                href="{{ backend_route('cms.download.file.form', ['downloadId' => $downloadId, 'id' => $file->id]) }}"
                                wire:navigate
                            >
                                <span class="fa-solid fa-edit"></span>
                                <span class="tooltip">{{ __('cms::labels.edit') }}</span>
                            </a>
                            @endcanAccess
                            @canAccess('cms.download.file.delete')
                            <button
                                class="btn-icon danger has-tooltip group"
                                data-id="{{ $file->id }}"
                                data-component="{{ $this->getId() }}"
                                type="button"
                                @click="$dispatch('confirm-dialog', {
                                    title: '{{ __('cms::labels.delete') }}',
                                    message: '{{ __('cms::labels.delete_file_confirm') }}',
                                    confirmText: '{{ __('cms::labels.yes_delete') }}',
                                    confirmColor: 'danger',
                                    icon: 'fa-solid fa-trash',
                                    wireMethod: 'deleteFile',
                                    wireParams: [parseInt($el.dataset.id)],
                                    wireComponent: $el.dataset.component
                                })"
                            >
                                <span class="fa-solid fa-trash"></span>
                                <span class="tooltip">{{ __('cms::labels.delete') }}</span>
                            </button>
                            @endcanAccess
                        </div>
                    </div>
                @empty
                    <div class="py-10 text-center text-gray-400">
                        <span class="fa-solid fa-folder-open text-3xl mb-2 block"></span>
                        {{ __('cms::labels.no_files') }}
                    </div>
                @endforelse
            </div>
        </div>

        <div class="flex justify-start">
            <a class="btn secondary" href="{{ backend_route('cms.download.index') }}" wire:navigate>
                <span class="fa-solid fa-arrow-left"></span> {{ __('cms::labels.back') }}
            </a>
        </div>
    </div>
</div>
