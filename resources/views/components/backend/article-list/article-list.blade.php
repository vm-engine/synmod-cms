<div x-data="{ pageName: '{{ $this->title() }}', drawerOpen: false }">
    @include('synapps::components.layouts.partials.breadcrumbs', ['breadcrumbs' => $this->breadcrumbs])

    <x-synapse-confirm-dialog />

    <div class="space-y-5 sm:space-y-6">
        <x-synapse-panel :title="__('cms::labels.article_list')">
            <x-slot:toolbar>
                @if ($sectionId)
                    @if ($this->section && $this->canManage($this->section, 'cms.article.create'))
                    <a
                        class="btn primary w-full justify-center sm:w-auto"
                        href="{{ backend_route('cms.section.article.form', ['sectionId' => $sectionId]) }}"
                        wire:navigate
                    >
                        <span class="fa-solid fa-plus"></span> {{ __('cms::labels.add') }}
                    </a>
                    @endif
                @endif
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
                                    <div class="font-medium">{{ $article->title }}<x-cms::locale-badges :model="$article" /></div>
                                    @if ($article->read_time > 0)
                                        <div class="text-xs text-gray-400">{{ $article->read_time }} min read</div>
                                    @endif
                                </td>
                                @if (!$sectionId)
                                    <td>{{ $article->section->title }}</td>
                                @endif
                                <td>{{ $article->category?->title ?? '-' }}</td>
                                <td>
                                    <x-synapse-badge
                                        :color="$article->status->value === 'published'
                                            ? 'success'
                                            : ($article->status->value === 'scheduled'
                                                ? 'warning'
                                                : 'gray')"
                                    >
                                        {{ $article->status->label() }}
                                    </x-synapse-badge>
                                </td>
                                <td>
                                    @if ($article->is_featured)
                                        <span class="fa-solid fa-star text-yellow-500"></span>
                                    @endif
                                </td>
                                <td>{{ $article->created_at->format('d M Y') }}</td>
                                <td>
                                    <div class="text-center">
                                        @if ($this->canManage($article, 'cms.article.update'))
                                        <a
                                            class="btn-icon warning has-tooltip group"
                                            href="{{ backend_route('cms.section.article.edit', ['sectionId' => $article->section_id, 'id' => $article->id]) }}"
                                            wire:navigate
                                        >
                                            <span class="fa-solid fa-edit"></span>
                                            <span class="tooltip">{{ __('cms::labels.edit') }}</span>
                                        </a>
                                        @endif
                                        @if ($this->canManage($article, 'cms.article.delete'))
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
                                        @endif
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
                            <option value="50">50</option>
                        </select>
                    </div>
                    <div class="pagination-container flex-1">{{ $this->articleList->links() }}</div>
                </div>
            </div>
        </x-synapse-panel>
    </div>

@php $drawerTitle = '<span class="fa-solid fa-filter mr-2"></span>' . __('cms::labels.advanced_filter'); @endphp
<x-synapse-drawer :title="$drawerTitle">
    @if (!$sectionId)
        <div class="mb-6">
            <label class="mb-2 block text-sm font-medium">{{ __('cms::labels.section') }}</label>
            <x-synapse-adv-select
                wire-model="filterSection"
                :live="true"
                :options="$this->sectionOptions"
            />
        </div>
    @endif
    <div class="mb-6">
        <label class="mb-2 block text-sm font-medium">{{ __('cms::labels.category') }}</label>
        <x-synapse-adv-select
            wire-model="filterCategory"
            :live="true"
            :options="$this->categoryOptions"
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
