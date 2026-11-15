<div x-data="{ pageName: '{{ $this->title() }}' }">
    <x-synapse-breadcrumbs :breadcrumbs="$this->breadcrumbs" />

    <x-synapse-confirm-dialog />

    <div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
        <div class="flex flex-wrap justify-between gap-3 px-5 py-4 sm:px-6">
            <h3 class="my-auto text-base font-medium text-gray-800 dark:text-white/90">{{ __('cms::builder.list') }}</h3>
            <div class="flex gap-3">
                @canAccess('cms.builder.create')
                <a
                    class="btn primary"
                    href="{{ backend_route('cms.builder.form') }}"
                    wire:navigate
                >
                    <span class="ph ph-plus"></span> {{ __('cms::builder.add') }}
                </a>
                @endcanAccess
                <x-synapse-search-box
                    placeholder="{{ __('cms::labels.search') }}"
                    wire:model.live.debounce="q"
                />
            </div>
        </div>

        <div class="max-w-full overflow-x-auto border-t border-gray-100 dark:border-gray-800">
            <table class="datatable min-w-full">
                <thead>
                    <tr>
                        <th>
                            <p>{{ __('cms::labels.title') }}</p>
                        </th>
                        <th>
                            <p>{{ __('cms::labels.slug') }}</p>
                        </th>
                        <th>
                            <p>{{ __('cms::builder.template') }}</p>
                        </th>
                        <th>
                            <p>{{ __('cms::builder.status') }}</p>
                        </th>
                        <th class="datatable-col-actions">
                            <p>{{ __('cms::labels.actions') }}</p>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->pages as $page)
                        <tr wire:key="builder-page-{{ $page->id }}">
                            <td>{{ $page->title }}</td>
                            <td><code class="text-xs">/{{ $page->slug }}</code></td>
                            <td>{{ $page->template }}</td>
                            <td>
                                <x-synapse-badge :color="$page->status === 'published' ? 'success' : 'gray'">
                                    {{ __('cms::builder.status_' . $page->status) }}
                                </x-synapse-badge>
                                @if ($page->status === 'published' && $page->hasUnpublishedChanges())
                                    <x-synapse-badge
                                        color="warning">{{ __('cms::builder.unpublished_changes') }}</x-synapse-badge>
                                @endif
                            </td>
                            <td>
                                <div class="text-center">
                                    @canAccess('cms.builder.update')
                                    <a
                                        class="btn-icon primary has-tooltip group"
                                        href="{{ backend_route('cms.builder.editor', ['id' => $page->id]) }}"
                                    >
                                        <span class="ph ph-layout"></span>
                                        <span class="tooltip">{{ __('cms::builder.open_builder') }}</span>
                                    </a>
                                    <a
                                        class="btn-icon warning has-tooltip group"
                                        href="{{ backend_route('cms.builder.form', ['id' => $page->id]) }}"
                                        wire:navigate
                                    >
                                        <span class="ph ph-pencil-simple"></span>
                                        <span class="tooltip">{{ __('cms::labels.edit') }}</span>
                                    </a>
                                    @endcanAccess
                                    @canAccess('cms.builder.delete')
                                    <button
                                        class="btn-icon danger has-tooltip group"
                                        type="button"
                                        data-token="{{ $page->delete_token }}"
                                        @click="$dispatch('confirm-dialog', {
                                            title: '{{ __('cms::builder.delete_title') }}',
                                            message: '{{ __('cms::builder.delete_message', ['name' => addslashes($page->title)]) }}',
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
                                    @endcanAccess
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-synapse-empty-state
                                    icon="ph ph-layout"
                                    :title="__('cms::labels.no_data')"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex gap-2 border-t border-gray-100 p-3 dark:border-gray-800">
            <div class="pagination-container flex-1">{{ $this->pages->links() }}</div>
        </div>
    </div>
</div>
