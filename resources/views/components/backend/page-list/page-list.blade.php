<div x-data="{ pageName: '{{ $this->title() }}', drawerOpen: false }">
    @include('synapps::components.layouts.partials.breadcrumbs', ['breadcrumbs' => $this->breadcrumbs])

    <x-synapse-confirm-dialog />

    <div class="space-y-5 sm:space-y-6">
        <div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="px-5 py-4 sm:px-6 sm:py-5">
                    <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                        {{ __('cms::labels.page_list') }}
                    </h3>
                </div>
                <div class="flex flex-col gap-3 px-5 pb-4 sm:my-auto sm:flex-row sm:items-center sm:pb-0">
                    @canAccess('cms.page.create')
                    <a
                        class="btn primary w-full justify-center sm:w-auto"
                        href="{{ backend_route('cms.page.form') }}"
                        wire:navigate
                    >
                        <span class="fa-solid fa-plus"></span> {{ __('cms::labels.add') }}
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
                            <span class="fa-solid fa-filter"></span>
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
                                    <p>{{ __('cms::labels.title') }}</p>
                                </th>
                                <th>
                                    <p>{{ __('cms::labels.slug') }}</p>
                                </th>
                                <th>
                                    <p>{{ __('cms::labels.status') }}</p>
                                </th>
                                <th>
                                    <p>{{ __('cms::labels.actions') }}</p>
                                </th>
                            </tr>
                        </thead>
                        @php
                            $statusClass = fn($s) => match ($s) {
                                'published' => 'bg-success/10 text-success',
                                'scheduled' => 'bg-warning/10 text-warning',
                                default => 'bg-gray-100 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400',
                            };
                        @endphp
                        <tbody>
                            @if ($this->isSearching)
                                {{-- Flat search results --}}
                                @forelse($this->pageList as $page)
                                    <tr>
                                        <td>{{ $page->title }}</td>
                                        <td><code class="text-xs">{{ $page->full_slug }}</code></td>
                                        <td>
                                            <span
                                                class="{{ $statusClass($page->status) }} inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            >
                                                {{ ucfirst($page->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-center">
                                                @canAccess('cms.page.create')
                                                @if ($page->getDepth() < 2)
                                                    <a
                                                        class="btn-icon success has-tooltip group"
                                                        href="{{ backend_route('cms.page.form', ['parentId' => $page->id]) }}"
                                                        wire:navigate
                                                    >
                                                        <span class="fa-solid fa-plus"></span>
                                                        <span
                                                            class="tooltip">{{ __('cms::labels.add_child_page') }}</span>
                                                    </a>
                                                @endif
                                                @endcanAccess
                                                @canAccess('cms.page.update')
                                                <a
                                                    class="btn-icon warning has-tooltip group"
                                                    href="{{ backend_route('cms.page.form', ['id' => $page->id]) }}"
                                                    wire:navigate
                                                >
                                                    <span class="fa-solid fa-edit"></span>
                                                    <span class="tooltip">{{ __('cms::labels.edit') }}</span>
                                                </a>
                                                @endcanAccess
                                                @canAccess('cms.page.delete')
                                                <button
                                                    class="btn-icon danger has-tooltip group"
                                                    data-token="{{ $page->delete_token }}"
                                                    type="button"
                                                    @click="$dispatch('confirm-dialog', {
                                                    title: '{{ __('cms::labels.delete_page_title') }}',
                                                    message: '{{ __('cms::labels.delete_page_message', ['title' => addslashes($page->title)]) }}',
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
                                            class="py-8 text-center text-gray-400"
                                            colspan="4"
                                        >
                                            {{ __('cms::labels.no_data') }}
                                        </td>
                                    </tr>
                                @endforelse
                            @else
                                {{-- Tree view --}}
                                @forelse($this->pageList as $root)
                                    {{-- Root (grandparent) row --}}
                                    <tr>
                                        <td class="font-medium">{{ $root->title }}</td>
                                        <td><code class="text-xs">{{ $root->full_slug }}</code></td>
                                        <td>
                                            <span
                                                class="{{ $statusClass($root->status) }} inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            >
                                                {{ ucfirst($root->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-center">
                                                @canAccess('cms.page.create')
                                                <a
                                                    class="btn-icon success has-tooltip group"
                                                    href="{{ backend_route('cms.page.form', ['parentId' => $root->id]) }}"
                                                    wire:navigate
                                                >
                                                    <span class="fa-solid fa-plus"></span>
                                                    <span class="tooltip">{{ __('cms::labels.add_child_page') }}</span>
                                                </a>
                                                @endcanAccess
                                                @canAccess('cms.page.update')
                                                <a
                                                    class="btn-icon warning has-tooltip group"
                                                    href="{{ backend_route('cms.page.form', ['id' => $root->id]) }}"
                                                    wire:navigate
                                                >
                                                    <span class="fa-solid fa-edit"></span>
                                                    <span class="tooltip">{{ __('cms::labels.edit') }}</span>
                                                </a>
                                                @endcanAccess
                                                @canAccess('cms.page.delete')
                                                <button
                                                    class="btn-icon danger has-tooltip group"
                                                    data-token="{{ $root->delete_token }}"
                                                    type="button"
                                                    @click="$dispatch('confirm-dialog', {
                                                    title: '{{ __('cms::labels.delete_page_title') }}',
                                                    message: '{{ __('cms::labels.delete_page_message', ['title' => addslashes($root->title)]) }}',
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
                                    @foreach ($root->children as $child)
                                        {{-- Child (parent) row — indented --}}
                                        <tr class="bg-gray-50/50 dark:bg-white/[0.02]">
                                            <td>
                                                <span
                                                    class="flex items-center gap-1.5 pl-5 text-gray-500 dark:text-gray-400"
                                                >
                                                    <span
                                                        class="fa-solid fa-turn-down fa-sm rotate-180 opacity-40"></span>
                                                    {{ $child->title }}
                                                </span>
                                            </td>
                                            <td><code class="text-xs">{{ $child->full_slug }}</code></td>
                                            <td>
                                                <span
                                                    class="{{ $statusClass($child->status) }} inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                                >
                                                    {{ ucfirst($child->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="text-center">
                                                    @canAccess('cms.page.create')
                                                    <a
                                                        class="btn-icon success has-tooltip group"
                                                        href="{{ backend_route('cms.page.form', ['parentId' => $child->id]) }}"
                                                        wire:navigate
                                                    >
                                                        <span class="fa-solid fa-plus"></span>
                                                        <span
                                                            class="tooltip">{{ __('cms::labels.add_child_page') }}</span>
                                                    </a>
                                                    @endcanAccess
                                                    @canAccess('cms.page.update')
                                                    <a
                                                        class="btn-icon warning has-tooltip group"
                                                        href="{{ backend_route('cms.page.form', ['id' => $child->id]) }}"
                                                        wire:navigate
                                                    >
                                                        <span class="fa-solid fa-edit"></span>
                                                        <span class="tooltip">{{ __('cms::labels.edit') }}</span>
                                                    </a>
                                                    @endcanAccess
                                                    @canAccess('cms.page.delete')
                                                    <button
                                                        class="btn-icon danger has-tooltip group"
                                                        data-token="{{ $child->delete_token }}"
                                                        type="button"
                                                        @click="$dispatch('confirm-dialog', {
                                                    title: '{{ __('cms::labels.delete_page_title') }}',
                                                    message: '{{ __('cms::labels.delete_page_message', ['title' => addslashes($child->title)]) }}',
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
                                        @foreach ($child->children as $grandchild)
                                            {{-- Grandchild row — further indented, no "+" button --}}
                                            <tr class="bg-gray-50/80 dark:bg-white/[0.04]">
                                                <td>
                                                    <span
                                                        class="flex items-center gap-1.5 pl-10 text-gray-400 dark:text-gray-500"
                                                    >
                                                        <span
                                                            class="fa-solid fa-turn-down fa-sm rotate-180 opacity-40"></span>
                                                        {{ $grandchild->title }}
                                                    </span>
                                                </td>
                                                <td><code class="text-xs">{{ $grandchild->full_slug }}</code></td>
                                                <td>
                                                    <span
                                                        class="{{ $statusClass($grandchild->status) }} inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                                    >
                                                        {{ ucfirst($grandchild->status) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="text-center">
                                                        @canAccess('cms.page.update')
                                                        <a
                                                            class="btn-icon warning has-tooltip group"
                                                            href="{{ backend_route('cms.page.form', ['id' => $grandchild->id]) }}"
                                                            wire:navigate
                                                        >
                                                            <span class="fa-solid fa-edit"></span>
                                                            <span class="tooltip">{{ __('cms::labels.edit') }}</span>
                                                        </a>
                                                        @endcanAccess
                                                        @canAccess('cms.page.delete')
                                                        <button
                                                            class="btn-icon danger has-tooltip group"
                                                            data-token="{{ $grandchild->delete_token }}"
                                                            type="button"
                                                            @click="$dispatch('confirm-dialog', {
                                                    title: '{{ __('cms::labels.delete_page_title') }}',
                                                    message: '{{ __('cms::labels.delete_page_message', ['title' => addslashes($grandchild->title)]) }}',
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
                                        @endforeach
                                    @endforeach
                                @empty
                                    <tr>
                                        <td
                                            class="py-8 text-center text-gray-400"
                                            colspan="4"
                                        >
                                            {{ __('cms::labels.no_data') }}
                                        </td>
                                    </tr>
                                @endforelse
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @php
        $drawerTitle = '<span class="fa-solid fa-filter mr-2"></span>' . __('cms::labels.advanced_filter');
    @endphp
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
