<div x-data="{ pageName: '{{ $this->title() }}' }">
    <x-synapse-breadcrumbs :breadcrumbs="$this->breadcrumbs" />

    <x-synapse-confirm-dialog />

    <div class="space-y-5 sm:space-y-6">
        <div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
            <div class="flex justify-between">
                <div class="px-5 py-4 sm:px-6 sm:py-5">
                    <h3 class="text-base font-medium text-gray-800 dark:text-white/90">{{ __('cms::labels.menu_list') }}
                    </h3>
                </div>
                <div class="my-auto flex gap-3 px-5">
                    @canAccess('cms.menu.create')
                    <button
                        class="btn primary"
                        type="button"
                        @click="$dispatch('open-modal-menu-form')"
                        wire:click="$dispatch('reset-menu-form')"
                    >
                        <span class="ph ph-plus"></span> {{ __('cms::labels.add') }}
                    </button>
                    @endcanAccess
                    <input
                        class="form-input p-2"
                        type="text"
                        placeholder="{{ __('cms::labels.search') }}"
                        wire:model.live.debounce="q"
                    >
                </div>
            </div>

            <div class="border-t border-gray-100 dark:border-gray-800 max-w-full overflow-x-auto">
                <table class="datatable min-w-full">
                    <thead>
                        <tr>
                            <th>
                                <p>No.</p>
                            </th>
                            <th>
                                <p>{{ __('cms::labels.name') }}</p>
                            </th>
                            <th>
                                <p>{{ __('cms::labels.position') }}</p>
                            </th>
                            <th>
                                <p>{{ __('cms::labels.items') }}</p>
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
                        @forelse($this->menuList as $i => $menu)
                            <tr>
                                <td>{{ $this->menuList->firstItem() + $i }}</td>
                                <td>{{ $menu->name }}</td>
                                <td><code class="text-xs">{{ $menu->position }}</code></td>
                                <td>{{ $menu->items_count }}</td>
                                <td>
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $menu->is_active ? 'bg-success/10 text-success' : 'bg-gray-100 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400' }}"
                                    >
                                        {{ $menu->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="text-center">
                                        @canAccess('cms.menu.update')
                                        <button
                                            class="btn-icon warning has-tooltip group"
                                            type="button"
                                            @click="$dispatch('open-modal-menu-form'); $wire.dispatch('load-menu', { id: {{ $menu->id }} })"
                                        >
                                            <span class="ph ph-pencil-simple"></span>
                                            <span class="tooltip">{{ __('cms::labels.edit') }}</span>
                                        </button>
                                        <a
                                            class="btn-icon info has-tooltip group"
                                            href="{{ backend_route('cms.menu.items', ['menuId' => $menu->id]) }}"
                                            wire:navigate
                                        >
                                            <span class="ph ph-list-bullets"></span>
                                            <span class="tooltip">{{ __('cms::labels.manage_items') }}</span>
                                        </a>
                                        @endcanAccess
                                        @canAccess('cms.menu.delete')
                                        <button
                                            class="btn-icon danger has-tooltip group"
                                            data-token="{{ $menu->delete_token }}"
                                            type="button"
                                            @click="$dispatch('confirm-dialog', {
                                            title: '{{ __('cms::labels.delete_menu_title') }}',
                                            message: '{{ __('cms::labels.delete_menu_message', ['name' => addslashes($menu->name)]) }}',
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
                                <td
                                    colspan="6"
                                    class="text-center py-8 text-gray-400"
                                >{{ __('cms::labels.no_data') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex gap-2 p-3 border-t border-gray-100 dark:border-gray-800">
                <div class="pagination-container flex-1">{{ $this->menuList->links() }}</div>
            </div>
        </div>
    </div>

    {{-- Menu Form Modal --}}
    <x-synapse-modal
        name="menu-form"
        maxWidth="lg"
    >
        <div class="p-6 space-y-4">
            <h3 class="text-lg font-semibold">
                {{ $form->menu ? __('cms::labels.edit_menu') : __('cms::labels.add_menu') }}</h3>

            <div>
                <label
                    class="form-label required"
                    for="menu_name"
                >{{ __('cms::labels.name') }}</label>
                <input
                    class="form-input @error('form.name') has-error @enderror"
                    id="menu_name"
                    type="text"
                    wire:model="form.name"
                />
                @error('form.name')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label
                    class="form-label required"
                    for="menu_position"
                >{{ __('cms::labels.position') }}</label>
                <input
                    class="form-input @error('form.position') has-error @enderror"
                    id="menu_position"
                    type="text"
                    wire:model="form.position"
                    placeholder="header, footer, sidebar..."
                />
                @error('form.position')
                    <p class="form-error">{{ $message }}</p>
                @enderror
                <p class="text-xs text-gray-400 mt-1">{{ __('cms::labels.position_hint') }}</p>
            </div>
            <div>
                <label
                    class="form-label"
                    for="menu_description"
                >{{ __('cms::labels.description') }}</label>
                <textarea
                    class="form-input"
                    id="menu_description"
                    rows="2"
                    wire:model="form.description"
                ></textarea>
            </div>
            <div class="flex items-center gap-3">
                <x-synapse-toggler
                    id="menu_is_active"
                    wire:model="form.is_active"
                    :inline="true"
                    :checked="$form->is_active"
                    activeColor="green"
                />
                <span class="text-sm">{{ __('cms::labels.active') }}</span>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button
                    class="btn secondary"
                    type="button"
                    @click="$dispatch('close-modal-menu-form')"
                >{{ __('cms::labels.cancel') }}</button>
                @canAccess('cms.menu.create|cms.menu.update')
                <button
                    class="btn primary"
                    type="button"
                    wire:click="save"
                >
                    <span class="ph ph-floppy-disk"></span> {{ __('cms::labels.save') }}
                </button>
                @endcanAccess
            </div>
        </div>
    </x-synapse-modal>
</div>
