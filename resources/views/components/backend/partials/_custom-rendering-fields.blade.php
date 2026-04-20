{{-- Plain (no-card) 2-column grid of custom_view + custom_layout inputs. Expects: $viewModel, $layoutModel, optional $wrapperClass --}}
<div class="{{ $wrapperClass ?? '' }} grid gap-3 sm:grid-cols-2">
    <div>
        <label class="form-label">{{ __('cms::labels.custom_view') }}</label>
        <input
            class="form-input"
            type="text"
            wire:model="{{ $viewModel }}"
            placeholder="home::templates.my-view"
        />
        <p class="form-help">{{ __('cms::labels.custom_view_help') }}</p>
    </div>
    <div>
        <label class="form-label">{{ __('cms::labels.custom_layout') }}</label>
        <input
            class="form-input"
            type="text"
            wire:model="{{ $layoutModel }}"
            placeholder="home::layouts.my-layout"
        />
        <p class="form-help">{{ __('cms::labels.custom_layout_help') }}</p>
    </div>
</div>
