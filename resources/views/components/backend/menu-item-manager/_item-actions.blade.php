<div class="flex gap-2">
    @canAccess('cms.menu.update')
    @if ($canAddSub)
        <button
            class="btn xs secondary"
            type="button"
            wire:click="startAddChild({{ $itemId }})"
            title="{{ __('cms::labels.add_sub_item') }}"
        >
            <span class="fa-solid fa-circle-plus"></span>
        </button>
    @endif
    <button
        class="btn xs secondary"
        type="button"
        wire:click="startEdit({{ $itemId }})"
    >
        <span class="fa-solid fa-pen-to-square"></span>
    </button>
    @endcanAccess
    @canAccess('cms.menu.delete')
    <button
        class="btn xs danger"
        type="button"
        data-title="{{ __('cms::labels.delete_item_title') }}"
        data-message="{{ __('cms::labels.delete_item_confirm') }}"
        data-confirm="{{ __('cms::labels.yes_delete') }}"
        data-id="{{ $itemId }}"
        data-component="{{ $this->getId() }}"
        @click="$dispatch('confirm-dialog', {
            title: $el.dataset.title,
            message: $el.dataset.message,
            confirmText: $el.dataset.confirm,
            confirmColor: 'danger',
            icon: 'fa-solid fa-trash',
            wireMethod: 'deleteItem',
            wireParams: [{{ (int) $itemId }}],
            wireComponent: $el.dataset.component
        })"
    >
        <span class="fa-solid fa-trash"></span>
    </button>
    @endcanAccess
</div>
