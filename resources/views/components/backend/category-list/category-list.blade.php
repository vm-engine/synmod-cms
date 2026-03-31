<div x-data="{ pageName: '{{ $this->title() }}', drawerOpen: false }">
    @include('synapps::components.layouts.partials.breadcrumbs', ['breadcrumbs' => $this->breadcrumbs])

    <x-synapse-confirm-dialog />

    <div class="space-y-5 sm:space-y-6">
        <div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
            <div class="flex justify-between">
                <div class="px-5 py-4 sm:px-6 sm:py-5">
                    <h3 class="text-base font-medium text-gray-800 dark:text-white/90">{{ __('cms::labels.category_list') }}</h3>
                </div>
                <div class="my-auto flex gap-3 px-5">
                    @canAccess('cms.section.create')
                    <a class="btn primary"
                       href="{{ backend_route('cms.category.form', ['sectionId' => $sectionId]) }}"
                       wire:navigate>
                        <span class="fa-solid fa-plus"></span> {{ __('cms::labels.add') }}
                    </a>
                    @endcanAccess
                    <div class="input-group">
                        <input class="form-input grow p-2" type="text" placeholder="{{ __('cms::labels.search') }}" wire:model.live.debounce="q">
                        <button class="input-group-item right btn" type="button" @click="drawerOpen = true">
                            <span class="fa-solid fa-filter"></span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100 dark:border-gray-800 max-w-full overflow-x-auto">
                <table class="datatable min-w-full">
                    <thead>
                        <tr>
                            <th><p>No.</p></th>
                            <th><p>{{ __('cms::labels.title') }}</p></th>
                            <th><p>{{ __('cms::labels.slug') }}</p></th>
                            <th><p>{{ __('cms::labels.articles') }}</p></th>
                            <th><p>{{ __('cms::labels.status') }}</p></th>
                            <th><p>{{ __('cms::labels.actions') }}</p></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->categoryList as $i => $category)
                        <tr>
                            <td>{{ $this->categoryList->firstItem() + $i }}</td>
                            <td>{{ $category->title }}</td>
                            <td><code class="text-xs">{{ $category->slug }}</code></td>
                            <td>{{ $category->articles_count }}</td>
                            <td>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $category->status === 'published' ? 'bg-success/10 text-success' : 'bg-gray-100 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400' }}">
                                    {{ ucfirst($category->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="text-center">
                                    @canAccess('cms.section.update')
                                    <a class="btn-icon warning has-tooltip group" href="{{ backend_route('cms.category.edit', ['sectionId' => $sectionId, 'id' => $category->id]) }}" wire:navigate>
                                        <span class="fa-solid fa-edit"></span>
                                        <span class="tooltip">{{ __('cms::labels.edit') }}</span>
                                    </a>
                                    @endcanAccess
                                    @canAccess('cms.section.delete')
                                    <button
                                        class="btn-icon danger has-tooltip group"
                                        data-token="{{ $category->delete_token }}"
                                        type="button"
                                        @click="$dispatch('confirm-dialog', {
                                            title: '{{ __('cms::labels.delete_category_title') }}',
                                            message: '{{ __('cms::labels.delete_category_message', ['title' => addslashes($category->title)]) }}',
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
                                    @endcanAccess
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-gray-400">{{ __('cms::labels.no_data') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex gap-2 p-3 border-t border-gray-100 dark:border-gray-800">
                <div class="flex-none p-1">
                    <select class="form-input py-1 text-sm" wire:model.live="limit">
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="25">25</option>
                    </select>
                </div>
                <div class="pagination-container flex-1">{{ $this->categoryList->links() }}</div>
            </div>
        </div>
    </div>

    @php $drawerTitle = '<span class="fa-solid fa-filter mr-2"></span>' . __('cms::labels.advanced_filter'); @endphp
    <x-synapse-drawer :title="$drawerTitle">
        <div class="mb-6">
            <label class="mb-2 block text-sm font-medium">{{ __('cms::labels.status') }}</label>
            <x-synapse-select id="filterStatus" name="filterStatus" wire:model.live="filterStatus" :options="$this->statusOptions" />
        </div>
    </x-synapse-drawer>
</div>
