<div x-data="{ pageName: '{{ $this->title() }}', drawerOpen: false }">
    @include('synapps::components.layouts.partials.breadcrumbs', ['breadcrumbs' => $this->breadcrumbs])


    <x-synapse-confirm-dialog />

    <div class="space-y-5 sm:space-y-6">
        <x-synapse-panel :title="__('cms::labels.page_list')">
            <x-slot:toolbar>
                @canAccess('cms.page.create')
                <a
                    class="btn primary w-full justify-center sm:w-auto"
                    href="{{ backend_route('cms.page.form') }}"
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

            <div class="syn-panel-body">
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
                            $statusColor = fn($s) => match ($s) {
                                'published' => 'success',
                                'scheduled' => 'warning',
                                default => 'gray',
                            };
                        @endphp
                        <tbody>
                            @if ($this->isSearching)
                                {{-- Flat search results --}}
                                @forelse($this->pageList as $page)
                                    <tr>
                                        <td>{{ $page->title }}<x-cms::locale-badges :model="$page" /></td>
                                        <td><code class="text-xs">{{ $page->full_slug }}</code></td>
                                        <td>
                                            <x-synapse-badge :color="$statusColor($page->status)">
                                                {{ ucfirst($page->status) }}
                                            </x-synapse-badge>
                                        </td>
                                        <td>
                                            <div class="text-center">
                                                @if ($this->canManage($page, 'cms.page.create') && $page->acceptsChildrenFromCurrentUser())
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
                                                @endif
                                                @if ($this->canManage($page, 'cms.page.update'))
                                                <a
                                                    class="btn-icon warning has-tooltip group"
                                                    href="{{ backend_route('cms.page.form', ['id' => $page->id]) }}"
                                                    wire:navigate
                                                >
                                                    <span class="fa-solid fa-edit"></span>
                                                    <span class="tooltip">{{ __('cms::labels.edit') }}</span>
                                                </a>
                                                @endif
                                                @if ($this->canManage($page, 'cms.page.delete'))
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
                                                @endif
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
                                        <td class="font-medium">{{ $root->title }}<x-cms::locale-badges :model="$root" /></td>
                                        <td><code class="text-xs">{{ $root->full_slug }}</code></td>
                                        <td>
                                            <x-synapse-badge :color="$statusColor($root->status)">
                                                {{ ucfirst($root->status) }}
                                            </x-synapse-badge>
                                        </td>
                                        <td>
                                            <div class="text-center">
                                                @if ($this->canManage($root, 'cms.page.create') && $root->acceptsChildrenFromCurrentUser())
                                                <a
                                                    class="btn-icon success has-tooltip group"
                                                    href="{{ backend_route('cms.page.form', ['parentId' => $root->id]) }}"
                                                    wire:navigate
                                                >
                                                    <span class="fa-solid fa-plus"></span>
                                                    <span class="tooltip">{{ __('cms::labels.add_child_page') }}</span>
                                                </a>
                                                @endif
                                                @if ($this->canManage($root, 'cms.page.update'))
                                                <a
                                                    class="btn-icon warning has-tooltip group"
                                                    href="{{ backend_route('cms.page.form', ['id' => $root->id]) }}"
                                                    wire:navigate
                                                >
                                                    <span class="fa-solid fa-edit"></span>
                                                    <span class="tooltip">{{ __('cms::labels.edit') }}</span>
                                                </a>
                                                @endif
                                                @if ($this->canManage($root, 'cms.page.delete'))
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
                                                @endif
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
                                                    {{ $child->title }}<x-cms::locale-badges :model="$child" />
                                                </span>
                                            </td>
                                            <td><code class="text-xs">{{ $child->full_slug }}</code></td>
                                            <td>
                                                <x-synapse-badge :color="$statusColor($child->status)">
                                                    {{ ucfirst($child->status) }}
                                                </x-synapse-badge>
                                            </td>
                                            <td>
                                                <div class="text-center">
                                                    @if ($this->canManage($child, 'cms.page.create') && $child->acceptsChildrenFromCurrentUser())
                                                    <a
                                                        class="btn-icon success has-tooltip group"
                                                        href="{{ backend_route('cms.page.form', ['parentId' => $child->id]) }}"
                                                        wire:navigate
                                                    >
                                                        <span class="fa-solid fa-plus"></span>
                                                        <span
                                                            class="tooltip">{{ __('cms::labels.add_child_page') }}</span>
                                                    </a>
                                                    @endif
                                                    @if ($this->canManage($child, 'cms.page.update'))
                                                    <a
                                                        class="btn-icon warning has-tooltip group"
                                                        href="{{ backend_route('cms.page.form', ['id' => $child->id]) }}"
                                                        wire:navigate
                                                    >
                                                        <span class="fa-solid fa-edit"></span>
                                                        <span class="tooltip">{{ __('cms::labels.edit') }}</span>
                                                    </a>
                                                    @endif
                                                    @if ($this->canManage($child, 'cms.page.delete'))
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
                                                    @endif
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
                                                        {{ $grandchild->title }}<x-cms::locale-badges :model="$grandchild" />
                                                    </span>
                                                </td>
                                                <td><code class="text-xs">{{ $grandchild->full_slug }}</code></td>
                                                <td>
                                                    <x-synapse-badge :color="$statusColor($grandchild->status)">
                                                        {{ ucfirst($grandchild->status) }}
                                                    </x-synapse-badge>
                                                </td>
                                                <td>
                                                    <div class="text-center">
                                                        @if ($this->canManage($grandchild, 'cms.page.update'))
                                                        <a
                                                            class="btn-icon warning has-tooltip group"
                                                            href="{{ backend_route('cms.page.form', ['id' => $grandchild->id]) }}"
                                                            wire:navigate
                                                        >
                                                            <span class="fa-solid fa-edit"></span>
                                                            <span class="tooltip">{{ __('cms::labels.edit') }}</span>
                                                        </a>
                                                        @endif
                                                        @if ($this->canManage($grandchild, 'cms.page.delete'))
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
                                                        @endif
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
        </x-synapse-panel>
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
