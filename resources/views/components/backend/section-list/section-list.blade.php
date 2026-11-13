<div x-data="{ pageName: '{{ $this->title() }}', drawerOpen: false }">
    <x-synapse-breadcrumbs :breadcrumbs="$this->breadcrumbs" />

    <x-synapse-confirm-dialog />

    <div class="space-y-5 sm:space-y-6">
        <div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="px-5 py-4 sm:px-6 sm:py-5">
                    <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                        {{ __('cms::labels.section_list') }}
                    </h3>
                </div>
                <div class="flex flex-col gap-3 px-5 pb-4 sm:my-auto sm:flex-row sm:items-center sm:pb-0">
                    @canAccess('cms.article.section.create')
                    <a
                        class="btn primary w-full justify-center sm:w-auto"
                        href="{{ backend_route('cms.section.form') }}"
                        wire:navigate
                    >
                        <span class="ph ph-plus"></span> {{ __('cms::labels.add') }}
                    </a>
                    @endcanAccess
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
                            <span class="ph ph-funnel"></span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100 dark:border-gray-800">
                <div class="max-w-full overflow-x-auto">
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
                                    <p>{{ __('cms::labels.slug') }}</p>
                                </th>
                                <th>
                                    <p>{{ __('cms::labels.categories') }}</p>
                                </th>
                                <th>
                                    <p>{{ __('cms::labels.articles') }}</p>
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
                            @forelse($this->sectionList as $i => $section)
                                <tr>
                                    <td>{{ $this->sectionList->firstItem() + $i }}</td>
                                    <td>{{ $section->title }}</td>
                                    <td><code class="text-xs">{{ $section->slug }}</code></td>
                                    <td>{{ $section->categories_count }}</td>
                                    <td>{{ $section->articles_count }}</td>
                                    <td>
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $section->status === 'published' ? 'bg-success/10 text-success' : 'bg-gray-100 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400' }}"
                                        >
                                            {{ ucfirst($section->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="text-center">
                                            @if ($this->canManage($section, 'cms.article.section.update'))
                                            <a
                                                class="btn-icon warning has-tooltip group"
                                                href="{{ backend_route('cms.section.form', ['id' => $section->id]) }}"
                                                wire:navigate
                                            >
                                                <span class="ph ph-pencil-simple"></span>
                                                <span class="tooltip">{{ __('cms::labels.edit') }}</span>
                                            </a>
                                            @endif
                                            @canAccess('cms.article.section.read')
                                            <a
                                                class="btn-icon secondary has-tooltip group"
                                                href="{{ backend_route('cms.category.index', ['sectionId' => $section->id]) }}"
                                                wire:navigate
                                            >
                                                <span class="ph ph-folder"></span>
                                                <span class="tooltip">{{ __('cms::labels.manage_categories') }}</span>
                                            </a>
                                            @endcanAccess
                                            @canAccess('cms.article.read')
                                            <a
                                                class="btn-icon info has-tooltip group"
                                                href="{{ backend_route('cms.section.article.index', ['sectionId' => $section->id]) }}"
                                                wire:navigate
                                            >
                                                <span class="ph ph-newspaper"></span>
                                                <span class="tooltip">{{ __('cms::labels.view_articles') }}</span>
                                            </a>
                                            @endcanAccess
                                            @if ($this->canManage($section, 'cms.article.section.delete'))
                                            <button
                                                class="btn-icon danger has-tooltip group"
                                                data-token="{{ $section->delete_token }}"
                                                type="button"
                                                @click="$dispatch('confirm-dialog', {
                                                title: '{{ __('cms::labels.delete_section_title') }}',
                                                message: '{{ __('cms::labels.delete_section_message', ['title' => addslashes($section->title)]) }}',
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
                                        colspan="7"
                                        class="text-center py-8 text-gray-400"
                                    >{{ __('cms::labels.no_data') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
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
                <div class="pagination-container flex-1">{{ $this->sectionList->links() }}</div>
            </div>
        </div>
    </div>

    @php $drawerTitle = '<span class="ph ph-funnel mr-2"></span>' . __('cms::labels.advanced_filter'); @endphp
    <x-synapse-drawer :title="$drawerTitle">
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
