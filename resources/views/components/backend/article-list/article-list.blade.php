<div x-data="{ pageName: '{{ $this->title() }}', drawerOpen: false }">
    @include('synapps::components.layouts.partials.breadcrumbs', ['breadcrumbs' => $this->breadcrumbs])

    <x-synapse-confirm-dialog />

    <div class="space-y-5 sm:space-y-6">
        <div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="px-5 py-4 sm:px-6 sm:py-5">
                    <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                        {{ __('cms::labels.article_list') }}</h3>
                </div>
                <div class="flex flex-col gap-3 px-5 pb-4 sm:my-auto sm:flex-row sm:items-center sm:pb-0">
                    @if ($sectionId)
                        @canAccess('cms.article.create')
                        <a
                            class="btn primary w-full justify-center sm:w-auto"
                            href="{{ backend_route('cms.section.article.form', ['sectionId' => $sectionId]) }}"
                            wire:navigate
                        >
                            <span class="fa-solid fa-plus"></span> {{ __('cms::labels.add') }}
                        </a>
                        @endcanAccess
                    @endif
                    <div class="input-group w-full sm:w-auto">
                        <input
                            class="form-input w-full grow p-2"
                            type="text"
                            placeholder="{{ __('cms::labels.search') }}"
                            wire:model.live.debounce="q"
                        >
                        <button
                            class="input-group-item right btn"
                            type="button"
                            @click="drawerOpen = true"
                        >
                            <span class="fa-solid fa-filter"></span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100 dark:border-gray-800 max-w-full overflow-x-auto">
                <table class="datatable min-w-full">
                    <thead>
                        <tr>
                            <th>
                                <p>No.</p>
                            </th>
                            <th class="sortable">
                                <div>
                                    <p>{{ __('cms::labels.title') }}</p>
                                    <a
                                        href="#"
                                        wire:click.prevent="sortData('title')"
                                    >
                                        <span
                                            class="fa fa-solid {{ $sort !== 'title' ? 'fa-sort' : ($sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down') }}"
                                        ></span>
                                    </a>
                                </div>
                            </th>
                            @if (!$sectionId)
                                <th>
                                    <p>{{ __('cms::labels.section') }}</p>
                                </th>
                            @endif
                            <th>
                                <p>{{ __('cms::labels.category') }}</p>
                            </th>
                            <th>
                                <p>{{ __('cms::labels.status') }}</p>
                            </th>
                            <th>
                                <p>{{ __('cms::labels.featured') }}</p>
                            </th>
                            <th class="sortable">
                                <div>
                                    <p>{{ __('cms::labels.created_at') }}</p>
                                    <a
                                        href="#"
                                        wire:click.prevent="sortData('created_at')"
                                    >
                                        <span
                                            class="fa fa-solid {{ $sort !== 'created_at' ? 'fa-sort' : ($sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down') }}"
                                        ></span>
                                    </a>
                                </div>
                            </th>
                            <th>
                                <p>{{ __('cms::labels.actions') }}</p>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->articleList as $i => $article)
                            <tr>
                                <td>{{ $this->articleList->firstItem() + $i }}</td>
                                <td>
                                    <div class="font-medium">{{ $article->title }}</div>
                                    @if ($article->read_time > 0)
                                        <div class="text-xs text-gray-400">{{ $article->read_time }} min read</div>
                                    @endif
                                </td>
                                @if (!$sectionId)
                                    <td>{{ $article->section->title }}</td>
                                @endif
                                <td>{{ $article->category?->title ?? '-' }}</td>
                                <td>
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $article->status->value === 'published' ? 'bg-success/10 text-success' : ($article->status->value === 'scheduled' ? 'bg-warning/10 text-warning' : 'bg-gray-100 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400') }}"
                                    >
                                        {{ $article->status->label() }}
                                    </span>
                                </td>
                                <td>
                                    @if ($article->is_featured)
                                        <span class="fa-solid fa-star text-yellow-500"></span>
                                    @endif
                                </td>
                                <td>{{ $article->created_at->format('d M Y') }}</td>
                                <td>
                                    <div class="text-center">
                                        @canAccess('cms.article.update')
                                        <a
                                            class="btn-icon warning has-tooltip group"
                                            href="{{ backend_route('cms.section.article.edit', ['sectionId' => $article->section_id, 'id' => $article->id]) }}"
                                            wire:navigate
                                        >
                                            <span class="fa-solid fa-edit"></span>
                                            <span class="tooltip">{{ __('cms::labels.edit') }}</span>
                                        </a>
                                        @endcanAccess
                                        @canAccess('cms.article.delete')
                                        <button
                                            class="btn-icon danger has-tooltip group"
                                            data-token="{{ $article->delete_token }}"
                                            type="button"
                                            @click="$dispatch('confirm-dialog', {
                                            title: '{{ __('cms::labels.delete_article_title') }}',
                                            message: '{{ __('cms::labels.delete_article_message', ['title' => addslashes($article->title)]) }}',
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
                                <td
                                    colspan="{{ $sectionId ? 7 : 8 }}"
                                    class="text-center py-8 text-gray-400"
                                >{{ __('cms::labels.no_data') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex gap-2 p-3 border-t border-gray-100 dark:border-gray-800">
                <div class="flex-none p-1">
                    <select
                        class="form-input py-1 text-sm"
                        wire:model.live="limit"
                    >
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div class="pagination-container flex-1">{{ $this->articleList->links() }}</div>
            </div>
        </div>
    </div>

    @php $drawerTitle = '<span class="fa-solid fa-filter mr-2"></span>' . __('cms::labels.advanced_filter'); @endphp
    <x-synapse-drawer :title="$drawerTitle">
        @if (!$sectionId)
            <div class="mb-6">
                <label class="mb-2 block text-sm font-medium">{{ __('cms::labels.section') }}</label>
                <x-synapse-select
                    id="filterSection"
                    name="filterSection"
                    wire:model.live="filterSection"
                    :options="$this->sectionOptions"
                />
            </div>
        @endif
        <div class="mb-6">
            <label class="mb-2 block text-sm font-medium">{{ __('cms::labels.category') }}</label>
            <x-synapse-select
                id="filterCategory"
                name="filterCategory"
                wire:model.live="filterCategory"
                :options="$this->categoryOptions"
            />
        </div>
        <div class="mb-6">
            <label class="mb-2 block text-sm font-medium">{{ __('cms::labels.status') }}</label>
            <x-synapse-select
                id="filterStatus"
                name="filterStatus"
                wire:model.live="filterStatus"
                :options="$this->statusOptions"
            />
        </div>
    </x-synapse-drawer>
</div>
