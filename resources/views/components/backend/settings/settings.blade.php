<div x-data="{ pageName: '{{ $this->title() }}' }">
    @include('synapps::components.layouts.partials.breadcrumbs', ['breadcrumbs' => $this->breadcrumbs])

    <form wire:submit.prevent="save">
        <x-synapse-panel :title="__('cms::labels.settings_editor_section')">
            <div class="syn-panel-body space-y-4 p-5 sm:p-6">
                <div>
                    <label
                        class="form-label"
                        for="editorDriver"
                    >{{ __('cms::labels.settings_editor') }}</label>
                    <select
                        class="form-input @error('editorDriver') has-error @enderror"
                        id="editorDriver"
                        wire:model="editorDriver"
                    >
                        @foreach ($this->editorOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    <p class="form-help">{{ __('cms::labels.settings_editor_help') }}</p>
                    @error('editorDriver')
                        <p class="form-error-message">{{ $message }}</p>
                    @enderror
                </div>

                @canAccess('cms.settings.update')
                <div class="flex justify-end">
                    <button
                        class="btn primary"
                        type="submit"
                        wire:loading.attr="disabled"
                    >
                        <span
                            class="fa-solid fa-floppy-disk"
                            wire:loading.remove
                        ></span>
                        <span
                            class="fa-solid fa-circle-notch fa-spin"
                            wire:loading
                        ></span>
                        {{ __('cms::labels.save') }}
                    </button>
                </div>
                @endcanAccess
            </div>
        </x-synapse-panel>
    </form>
</div>
