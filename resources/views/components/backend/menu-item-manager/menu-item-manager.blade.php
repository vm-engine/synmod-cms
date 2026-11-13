<div x-data="{ pageName: '{{ $this->title() }}' }">
    <x-synapse-confirm-dialog />
    <x-synapse-breadcrumbs :breadcrumbs="$this->breadcrumbs" />

    <div class="space-y-5 sm:space-y-6">

        {{-- Add root item form (hidden when adding a child/grandchild) --}}
        @canAccess('cms.menu.update')
        @if ($addingChildFor === null)
            <div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
                <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                    <h3 class="text-base font-medium">{{ __('cms::labels.add_menu_item') }}</h3>
                </div>
                <div class="p-5 sm:p-6">
                    @include('cms::components.backend.menu-item-manager._add-form', [
                        'targetType' => $newTargetType,
                        'wireModelLabel' => 'newLabel',
                        'wireModelType' => 'newTargetType',
                        'wireModelUrl' => 'newTargetUrl',
                        'wireModelTargetId' => 'newTargetId',
                        'wireModelNewTab' => 'newOpenNewTab',
                    ])
                </div>
            </div>
        @endif
        @endcanAccess

        {{-- Items list --}}
        <div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
            <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                <h3 class="text-base font-medium">{{ __('cms::labels.menu_items') }}</h3>
            </div>
            <div
                class="space-y-2 p-5 sm:p-6"
                x-data="cmsSortableMenuItems()"
            >
                @forelse($this->items as $item)
                    <div
                        class="rounded-lg border border-gray-200 dark:border-gray-700"
                        data-item-id="{{ $item->id }}"
                    >
                        @if ($editingId === $item->id)
                            {{-- Edit mode (level 0) --}}
                            @include('cms::components.backend.menu-item-manager._edit-form')
                        @else
                            {{-- View mode (level 0) --}}
                            <div class="flex items-center gap-3 px-4 py-3">
                                <span class="drag-handle cursor-grab text-gray-400">
                                    <span class="ph ph-dots-six-vertical"></span>
                                </span>
                                <div class="flex-1">
                                    <span class="text-sm font-medium">{{ $item->label }}</span>
                                    <span class="ml-2 text-xs text-gray-400">
                                        {{ __('cms::labels.type_' . $item->target_type) }}{{ $item->target_url ? ': ' . $item->target_url : '' }}
                                    </span>
                                </div>
                                @include('cms::components.backend.menu-item-manager._item-actions', [
                                    'itemId' => $item->id,
                                    'itemToken' => $item->delete_token,
                                    'canAddSub' => true,
                                ])
                            </div>

                            {{-- Level-1 children --}}
                            @if ($item->children->isNotEmpty() || $addingChildFor === $item->id)
                                <div class="border-t border-gray-100 pb-1 pl-6 pt-1 dark:border-gray-800">
                                    @foreach ($item->children as $child)
                                        <div class="mt-1 rounded border border-gray-100 dark:border-gray-700/50">
                                            @if ($editingId === $child->id)
                                                {{-- Edit mode (level 1) --}}
                                                @include('cms::components.backend.menu-item-manager._edit-form')
                                            @else
                                                {{-- View mode (level 1) --}}
                                                <div class="flex items-center gap-3 px-4 py-2">
                                                    <span class="text-gray-300 dark:text-gray-600">
                                                        <span class="ph ph-arrow-elbow-down-right text-xs"></span>
                                                    </span>
                                                    <div class="flex-1">
                                                        <span class="text-sm font-medium">{{ $child->label }}</span>
                                                        <span class="ml-2 text-xs text-gray-400">
                                                            {{ __('cms::labels.type_' . $child->target_type) }}{{ $child->target_url ? ': ' . $child->target_url : '' }}
                                                        </span>
                                                    </div>
                                                    @include(
                                                        'cms::components.backend.menu-item-manager._item-actions',
                                                        [
                                                            'itemId' => $child->id,
                                                            'itemToken' => $child->delete_token,
                                                            'canAddSub' => true,
                                                        ]
                                                    )
                                                </div>

                                                {{-- Level-2 grandchildren --}}
                                                @if ($child->children->isNotEmpty() || $addingChildFor === $child->id)
                                                    <div
                                                        class="border-t border-gray-100 pb-1 pl-6 pt-1 dark:border-gray-800">
                                                        @foreach ($child->children as $grandchild)
                                                            <div class="mt-1 rounded bg-gray-50 dark:bg-gray-800/30">
                                                                @if ($editingId === $grandchild->id)
                                                                    {{-- Edit mode (level 2) --}}
                                                                    @include('cms::components.backend.menu-item-manager._edit-form')
                                                                @else
                                                                    {{-- View mode (level 2 — no add sub) --}}
                                                                    <div class="flex items-center gap-3 px-4 py-2">
                                                                        <span class="text-gray-300 dark:text-gray-600">
                                                                            <span
                                                                                class="ph ph-arrow-elbow-down-right text-xs"
                                                                            ></span>
                                                                        </span>
                                                                        <div class="flex-1">
                                                                            <span
                                                                                class="text-sm">{{ $grandchild->label }}</span>
                                                                            <span class="ml-2 text-xs text-gray-400">
                                                                                {{ __('cms::labels.type_' . $grandchild->target_type) }}{{ $grandchild->target_url ? ': ' . $grandchild->target_url : '' }}
                                                                            </span>
                                                                        </div>
                                                                        @include(
                                                                            'cms::components.backend.menu-item-manager._item-actions',
                                                                            [
                                                                                'itemId' => $grandchild->id,
                                                                                'itemToken' => $grandchild->delete_token,
                                                                                'canAddSub' => false,
                                                                            ]
                                                                        )
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endforeach

                                                        {{-- Inline add grandchild form --}}
                                                        @canAccess('cms.menu.update')
                                                        @if ($addingChildFor === $child->id)
                                                            @include(
                                                                'cms::components.backend.menu-item-manager._inline-add-form',
                                                                [
                                                                    'parentLabel' => $child->label,
                                                                ]
                                                            )
                                                        @endif
                                                        @endcanAccess
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    @endforeach

                                    {{-- Inline add child form --}}
                                    @canAccess('cms.menu.update')
                                    @if ($addingChildFor === $item->id)
                                        @include(
                                            'cms::components.backend.menu-item-manager._inline-add-form',
                                            [
                                                'parentLabel' => $item->label,
                                            ]
                                        )
                                    @endif
                                    @endcanAccess
                                </div>
                            @endif
                        @endif
                    </div>
                @empty
                    <div class="py-8 text-center text-gray-400">{{ __('cms::labels.no_items') }}</div>
                @endforelse
            </div>
        </div>

        <div class="flex justify-start">
            <a
                class="btn secondary"
                href="{{ backend_route('cms.menu.index') }}"
                wire:navigate
            >
                <span class="ph ph-arrow-left"></span> {{ __('cms::labels.back') }}
            </a>
        </div>
    </div>
</div>

<script nonce="{{ csp_nonce() }}">
    (() => {
        const register = () => {
        Alpine.data('cmsSortableMenuItems', () => ({
            init() {
                new Sortable(this.$el, {
                    animation: 150,
                    handle: '.drag-handle',
                    onEnd: () => {
                        const ids = Array.from(this.$el.querySelectorAll(':scope > [data-item-id]'))
                            .map(el => parseInt(el.dataset.itemId));
                        this.$wire.updateOrder(ids);
                    }
                });
            }
        }));
        };
    document.addEventListener('alpine:init', register);
    if (window.Alpine) {
        register();
    }
    })();
</script>
