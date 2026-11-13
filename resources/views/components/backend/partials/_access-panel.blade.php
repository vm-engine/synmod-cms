{{--
    Access panel: visibility + view/manage lists for a form object using HasAccessFields.
    Params: $withInherit (bool), $withManagers (bool), $inheritedValue (?string label of the inherited visibility).
    The host component must `use WithAccessPicker` (searchPrincipals / loadPrincipals).
--}}
<x-synapse-panel :title="__('cms::access.panel')">
    <div class="syn-panel-body p-5 sm:p-6 grid gap-4">
        @unless ($this->form->canEditAccess)
            <x-synapse-alert
                type="info"
                :dismissible="false"
                :message="__('cms::access.read_only')"
            />
        @endunless

        <div>
            <label
                class="form-label required"
                for="visibility"
            >{{ __('cms::access.visibility') }}</label>
            <x-synapse-adv-select
                wire-model="form.visibility"
                :live="true"
                :disabled="!$this->form->canEditAccess"
                :options="$this->form->visibilityOptions($withInherit)"
            />
            @if ($withInherit && $this->form->visibility === 'inherit' && $inheritedValue)
                <p class="form-help">{{ __('cms::access.inherited_value', ['value' => $inheritedValue]) }}</p>
            @endif
            @error('form.visibility')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        @foreach ($withManagers ? ['view', 'manage'] : ['view'] as $list)
            <div wire:key="access-{{ $list }}">
                <label
                    class="form-label">{{ __($list === 'view' ? 'cms::access.viewers' : 'cms::access.managers') }}</label>
                @if (count($this->form->lockedPrincipals[$list]))
                    <div class="flex flex-wrap gap-2 mb-2">
                        @foreach ($this->form->lockedPrincipals[$list] as $locked)
                            <x-synapse-badge
                                :color="$locked['reason'] === 'locked' ? 'warning' : 'gray'"
                                icon="ph ph-lock"
                                title="{{ __('cms::access.' . $locked['reason']) }}"
                            >
                                {{ $locked['label'] }}
                                <span class="sr-only">({{ __('cms::access.' . $locked['reason']) }})</span>
                            </x-synapse-badge>
                        @endforeach
                    </div>
                @endif
                <x-synapse-adv-select
                    wire-model="form.{{ $list === 'view' ? 'viewPrincipals' : 'managePrincipals' }}"
                    :multiple="true"
                    :disabled="!$this->form->canEditAccess"
                    search-method="searchPrincipals"
                    load-method="loadPrincipals"
                    placeholder="{{ __('cms::access.principals_placeholder') }}"
                />
                <p class="form-help">
                    {{ __($list === 'view' ? 'cms::access.viewers_help' : 'cms::access.managers_help') }}</p>
                @error('form.' . ($list === 'view' ? 'viewPrincipals' : 'managePrincipals') . '.*')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>
        @endforeach
    </div>
</x-synapse-panel>
