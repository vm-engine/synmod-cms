<div class="flex gap-2">
    @canAccess('cms.menu.update')
    @if ($canAddSub)
        <button
            class="btn xs secondary"
            type="button"
            wire:click="startAddChild({{ $itemId }})"
            title="{{ __('cms::labels.add_sub_item') }}"
        >
            <span class="ph ph-plus-circle"></span>
        </button>
    @endif
    <button
        class="btn xs secondary"
        type="button"
        wire:click="startEdit({{ $itemId }})"
    >
        <span class="ph ph-pencil-simple"></span>
    </button>
    @endcanAccess
    @canAccess('cms.menu.delete')
    <button
        class="btn xs danger"
        type="button"
        data-title="{{ __('cms::labels.delete_item_title') }}"
        data-message="{{ __('cms::labels.delete_item_confirm') }}"
        data-confirm="{{ __('cms::labels.yes_delete') }}"
        data-component="{{ $this->getId() }}"
        @click="$dispatch('confirm-dialog', {
            title: $el.dataset.title,
            message: $el.dataset.message,
            confirmText: $el.dataset.confirm,
            confirmColor: 'danger',
            icon: 'ph ph-trash',
            wireMethod: 'deleteItem',
            wireParams: ['{{ $itemToken }}'],
            wireComponent: $el.dataset.component
        })"
    >
        <span class="ph ph-trash"></span>
    </button>
    @endcanAccess
</div>
