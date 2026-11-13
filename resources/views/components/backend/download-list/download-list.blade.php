<div x-data="{ pageName: '{{ $this->title() }}', drawerOpen: false }">
    <x-synapse-breadcrumbs :breadcrumbs="$this->breadcrumbs" />

    <x-synapse-confirm-dialog />

    <div class="space-y-5 sm:space-y-6">
        <x-synapse-panel :title="__('cms::labels.download_list')">
            <x-slot:toolbar>
                @canAccess('cms.download.category.create')
                <a
                    class="btn primary w-full justify-center sm:w-auto"
                    href="{{ backend_route('cms.download.form') }}"
                    wire:navigate
                >
                    <span class="ph ph-plus"></span> {{ __('cms::labels.add') }}
                </a>
                @endcanAccess
                <x-synapse-search-box
                    :placeholder="__('cms::labels.search')"
                    wire:model.live.debounce="q"
                >
                    <x-slot:trailing>
                        <button
                            type="button"
                            class="btn"
                            @click="drawerOpen = true"
                        >
                            <span class="ph ph-funnel"></span>
                        </button>
                    </x-slot:trailing>
                </x-synapse-search-box>
            </x-slot:toolbar>

            <div class="syn-panel-body max-w-full overflow-x-auto">
                <table class="datatable min-w-full">
                    <thead>
                        <tr>
                            <th>
                                <p>No.</p>
                            </th>
                            <th>
                                <p>{{ __('cms::labels.title') }}</p>
                            </th>
                            <th>
                                <p>{{ __('cms::access.visibility') }}</p>
                            </th>
                            <th>
                                <p>{{ __('cms::labels.files') }}</p>
                            </th>
                            <th>
                                <p>{{ __('cms::labels.status') }}</p>
                            </th>
                            <th>
                                <p>{{ __('cms::labels.actions') }}</p>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->downloadList as $i => $download)
                            <tr>
                                <td>{{ $this->downloadList->firstItem() + $i }}</td>
                                <td>{{ $download->title }}</td>
                                <td>
                                    <x-synapse-badge :color="$download->visibility->value !== 'public' ? 'warning' : 'gray'">
                                        <span
                                            class="ph {{ $download->visibility->value !== 'public' ? 'ph-lock' : 'ph-lock-open' }} mr-1 text-[10px]"
                                        ></span>
                                        {{ $download->visibility->label() }}
                                    </x-synapse-badge>
                                </td>
                                <td>{{ $download->files_count }}</td>
                                <td>
                                    <x-synapse-badge :color="$download->status === 'active' ? 'success' : 'gray'">
                                        {{ ucfirst($download->status) }}
                                    </x-synapse-badge>
                                </td>
                                <td>
                                    <div class="text-center">
                                        @if ($this->canManage($download, 'cms.download.category.update'))
                                        <a
                                            class="btn-icon warning has-tooltip group"
                                            href="{{ backend_route('cms.download.form', ['id' => $download->id]) }}"
                                            wire:navigate
                                        >
                                            <span class="ph ph-pencil-simple"></span>
                                            <span class="tooltip">{{ __('cms::labels.edit') }}</span>
                                        </a>
                                        @endif
                                        @canAccess('cms.download.file.read|cms.download.file.update')
                                        <a
                                            class="btn-icon info has-tooltip group"
                                            href="{{ backend_route('cms.download.files', ['downloadId' => $download->id]) }}"
                                            wire:navigate
                                        >
                                            <span class="ph ph-folder-open"></span>
                                            <span class="tooltip">{{ __('cms::labels.manage_files') }}</span>
                                        </a>
                                        @endcanAccess
                                        @if ($this->canManage($download, 'cms.download.category.delete'))
                                        <button
                                            class="btn-icon danger has-tooltip group"
                                            data-token="{{ $download->delete_token }}"
                                            type="button"
                                            @click="$dispatch('confirm-dialog', {
                                            title: '{{ __('cms::labels.delete_download_title') }}',
                                            message: '{{ __('cms::labels.delete_download_message', ['title' => addslashes($download->title)]) }}',
                                            confirmText: '{{ __('cms::labels.yes_delete') }}',
                                            confirmColor: 'danger',
                                            icon: 'ph ph-trash',
                                            wireMethod: 'delete',
                                            wireParams: [$el.dataset.token],
                                            wireComponent: '{{ $this->getId() }}'
                                        })"
                                        >
                                            <span class="ph ph-trash"></span>
                                            <span class="tooltip">{{ __('cms::labels.delete') }}</span>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="6"
                                    class="text-center py-8 text-gray-400"
                                >{{ __('cms::labels.no_downloads') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="syn-panel-body">
                <div class="syn-per-page-selector">
                    <div class="flex-none p-1">
                        <select
                            class="syn-per-page-select"
                            wire:model.live="limit"
                        >
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="25">25</option>
                        </select>
                    </div>
                    <div class="pagination-container flex-1">{{ $this->downloadList->links() }}</div>
                </div>
            </div>
        </x-synapse-panel>
    </div>

    @php $drawerTitle = '<span class="ph ph-funnel mr-2"></span>' . __('cms::labels.advanced_filter'); @endphp
    <x-synapse-drawer :title="$drawerTitle">
        <div class="mb-6">
            <label class="mb-2 block text-sm font-medium">{{ __('cms::access.visibility') }}</label>
            <x-synapse-adv-select
                wire-model="filterVisibility"
                :live="true"
                :options="$this->visibilityOptions"
            />
        </div>
        <div class="mb-6">
            <label class="mb-2 block text-sm font-medium">{{ __('cms::labels.status') }}</label>
            <x-synapse-adv-select
                wire-model="filterStatus"
                :live="true"
                :options="$this->statusOptions"
            />
        </div>
    </x-synapse-drawer>
</div>
