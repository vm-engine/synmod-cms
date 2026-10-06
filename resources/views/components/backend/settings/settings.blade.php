<div x-data="{ pageName: '{{ $this->title() }}' }">
    @include('synapps::components.layouts.partials.breadcrumbs', ['breadcrumbs' => $this->breadcrumbs])

    <x-synapse-confirm-dialog />

    <form
        class="space-y-6"
        wire:submit.prevent="save"
    >
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
            </div>
        </x-synapse-panel>

        <x-synapse-panel :title="__('cms::labels.analytics_tracking_section')">
            <div class="syn-panel-body space-y-4 p-5 sm:p-6">
                <div>
                    <label
                        class="form-label"
                        for="measurementId"
                    >{{ __('cms::labels.analytics_measurement_id') }}</label>
                    <input
                        class="form-input @error('measurementId') has-error @enderror"
                        id="measurementId"
                        type="text"
                        placeholder="G-XXXXXXXXXX"
                        wire:model="measurementId"
                    >
                    <p class="form-help">{{ __('cms::labels.analytics_measurement_id_help') }}</p>
                    @error('measurementId')
                        <p class="form-error-message">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label
                        class="form-label"
                        for="gscVerification"
                    >{{ __('cms::labels.analytics_gsc_verification') }}</label>
                    <input
                        class="form-input @error('gscVerification') has-error @enderror"
                        id="gscVerification"
                        type="text"
                        wire:model="gscVerification"
                    >
                    <p class="form-help">{{ __('cms::labels.analytics_gsc_verification_help') }}</p>
                    @error('gscVerification')
                        <p class="form-error-message">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-start gap-3">
                    <x-synapse-toggler
                        id="requireConsent"
                        wire:model="requireConsent"
                        :inline="true"
                        :checked="$requireConsent"
                        activeColor="green"
                    />
                    <div>
                        <span class="text-sm">{{ __('cms::labels.analytics_require_consent') }}</span>
                        <p class="form-help">{{ __('cms::labels.analytics_require_consent_help') }}</p>
                    </div>
                </div>
            </div>
        </x-synapse-panel>

        <x-synapse-panel :title="__('cms::labels.analytics_api_section')">
            <div class="syn-panel-body space-y-4 p-5 sm:p-6">
                <div>
                    <label
                        class="form-label"
                        for="propertyId"
                    >{{ __('cms::labels.analytics_property_id') }}</label>
                    <input
                        class="form-input @error('propertyId') has-error @enderror"
                        id="propertyId"
                        type="text"
                        inputmode="numeric"
                        wire:model="propertyId"
                    >
                    <p class="form-help">{{ __('cms::labels.analytics_property_id_help') }}</p>
                    @error('propertyId')
                        <p class="form-error-message">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label
                        class="form-label"
                        for="gscSite"
                    >{{ __('cms::labels.analytics_gsc_site') }}</label>
                    <input
                        class="form-input @error('gscSite') has-error @enderror"
                        id="gscSite"
                        type="text"
                        placeholder="https://example.com/ | sc-domain:example.com"
                        wire:model="gscSite"
                    >
                    <p class="form-help">{{ __('cms::labels.analytics_gsc_site_help') }}</p>
                    @error('gscSite')
                        <p class="form-error-message">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label
                        class="form-label"
                        for="serviceAccountJson"
                    >{{ __('cms::labels.analytics_service_account') }}</label>
                    @if ($this->serviceAccountEmail !== null)
                        <div class="syn-status-row mb-2">
                            <span class="text-sm">
                                <span class="ph ph-check-circle text-green-600"></span>
                                {{ __('cms::labels.analytics_key_configured', ['email' => $this->serviceAccountEmail]) }}
                            </span>
                            @canAccess('cms.settings.update')
                            <button
                                class="btn xs danger"
                                type="button"
                                @click="$dispatch('confirm-dialog', {
                                    title: '{{ __('cms::labels.analytics_key_remove') }}',
                                    message: '{{ __('cms::labels.analytics_key_remove_confirm') }}',
                                    confirmText: '{{ __('cms::labels.analytics_key_remove') }}',
                                    confirmColor: 'danger',
                                    icon: 'ph ph-key',
                                    wireMethod: 'removeServiceAccount',
                                    wireComponent: '{{ $this->getId() }}'
                                })"
                            >{{ __('cms::labels.analytics_key_remove') }}</button>
                            @endcanAccess
                        </div>
                    @endif
                    <textarea
                        class="form-input font-mono text-xs @error('serviceAccountJson') has-error @enderror"
                        id="serviceAccountJson"
                        rows="4"
                        autocomplete="off"
                        spellcheck="false"
                        placeholder="{{ $this->serviceAccountEmail === null ? '{ "type": "service_account", … }' : __('cms::labels.analytics_key_replace_placeholder') }}"
                        wire:model="serviceAccountJson"
                    ></textarea>
                    <p class="form-help">{{ __('cms::labels.analytics_service_account_help') }}</p>
                    @error('serviceAccountJson')
                        <p class="form-error-message">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </x-synapse-panel>

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
    </form>
</div>
