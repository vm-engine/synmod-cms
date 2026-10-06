<div x-data="{ pageName: '{{ $this->title() }}', drawerOpen: false }">
    @include('synapps::components.layouts.partials.breadcrumbs', ['breadcrumbs' => $this->breadcrumbs])

    <x-synapse-confirm-dialog />

    <div class="space-y-5 sm:space-y-6">
        <x-synapse-panel :title="__('cms::labels.album_list')">
            <x-slot:toolbar>
                @canAccess('cms.gallery.album.create|cms.gallery.banner.create')
                <a
                    class="btn primary w-full justify-center sm:w-auto"
                    href="{{ backend_route('cms.album.form') }}"
                    wire:navigate
                >
                    <span class="fa-solid fa-plus"></span> {{ __('cms::labels.add') }}
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
                            <span class="fa-solid fa-filter"></span>
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
                                <p>{{ __('cms::labels.type') }}</p>
                            </th>
                            <th>
                                <p>{{ __('cms::labels.images') }}</p>
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
                        @forelse($this->albumList as $i => $album)
                            <tr>
                                <td>{{ $this->albumList->firstItem() + $i }}</td>
                                <td>{{ $album->title }}</td>
                                <td>
                                    <x-synapse-badge :color="$album->type->value === 'banner' ? 'info' : 'gray'">
                                        {{ $album->type->label() }}
                                    </x-synapse-badge>
                                </td>
                                <td>{{ $album->images_count }}</td>
                                <td>
                                    <x-synapse-badge :color="$album->status === 'active' ? 'success' : 'gray'">
                                        {{ ucfirst($album->status) }}
                                    </x-synapse-badge>
                                </td>
                                <td>
                                    <div class="text-center">
                                        @if ($this->canManageAction($album, 'update'))
                                            <a
                                                class="btn-icon warning has-tooltip group"
                                                href="{{ backend_route('cms.album.form', ['id' => $album->id]) }}"
                                                wire:navigate
                                            >
                                                <span class="fa-solid fa-edit"></span>
                                                <span class="tooltip">{{ __('cms::labels.edit') }}</span>
                                            </a>
                                        @endif
                                        @canAccess('cms.gallery.image.read|cms.gallery.image.update')
                                        <a
                                            class="btn-icon info has-tooltip group"
                                            href="{{ backend_route('cms.album.images', ['albumId' => $album->id]) }}"
                                            wire:navigate
                                        >
                                            <span class="fa-solid fa-images"></span>
                                            <span class="tooltip">{{ __('cms::labels.manage_images') }}</span>
                                        </a>
                                        @endcanAccess
                                        @if ($this->canManageAction($album, 'delete'))
                                            <button
                                                class="btn-icon danger has-tooltip group"
                                                data-token="{{ $album->delete_token }}"
                                                type="button"
                                                @click="$dispatch('confirm-dialog', {
                                            title: '{{ __('cms::labels.delete_album_title') }}',
                                            message: '{{ __('cms::labels.delete_album_message', ['title' => addslashes($album->title)]) }}',
                                            confirmText: '{{ __('cms::labels.yes_delete') }}',
                                            confirmColor: 'danger',
                                            icon: 'fa-solid fa-trash',
                                            wireMethod: 'delete',
                                            wireParams: [$el.dataset.token],
                                            wireComponent: '{{ $this->getId() }}'
                                        })"
                                            >
                                                <span class="fa-solid fa-trash"></span>
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
                                >{{ __('cms::labels.no_data') }}</td>
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
                    <div class="pagination-container flex-1">{{ $this->albumList->links() }}</div>
                </div>
            </div>
        </x-synapse-panel>
    </div>

    @php $drawerTitle = '<span class="fa-solid fa-filter mr-2"></span>' . __('cms::labels.advanced_filter'); @endphp
    <x-synapse-drawer :title="$drawerTitle">
        <div class="mb-6">
            <label class="mb-2 block text-sm font-medium">{{ __('cms::labels.type') }}</label>
            <x-synapse-adv-select
                wire-model="filterType"
                :live="true"
                :options="$this->typeOptions"
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
