<div
    x-data="cmsBuilderEditor"
    @cms-builder-refresh-canvas.window="refreshCanvas"
    data-remove-title="{{ __('cms::builder.remove_confirm_title') }}"
    data-remove-message="{{ __('cms::builder.remove_confirm_message') }}"
    data-remove-confirm="{{ __('cms::builder.remove') }}"
    data-remove-cancel="{{ __('cms::labels.cancel') }}"
>
    <x-synapse-confirm-dialog />
    <x-synapse-breadcrumbs :breadcrumbs="$this->breadcrumbs" />

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="w-full sm:w-64">
            <select
                class="form-select"
                aria-label="{{ __('cms::builder.template') }}"
                wire:model.live="template"
            >
                @foreach ($this->templateOptions as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div
            class="flex overflow-hidden rounded-lg border border-gray-200 dark:border-gray-800"
            role="group"
        >
            <button
                class="px-3 py-2"
                type="button"
                data-device="desktop"
                aria-label="{{ __('cms::builder.device_desktop') }}"
                :class="isDevice('desktop') ? 'bg-gray-100 dark:bg-gray-800' : ''"
                @click="setDevice"
            ><span class="ph ph-desktop"></span></button>
            <button
                class="px-3 py-2"
                type="button"
                data-device="tablet"
                aria-label="{{ __('cms::builder.device_tablet') }}"
                :class="isDevice('tablet') ? 'bg-gray-100 dark:bg-gray-800' : ''"
                @click="setDevice"
            ><span class="ph ph-device-tablet"></span></button>
            <button
                class="px-3 py-2"
                type="button"
                data-device="mobile"
                aria-label="{{ __('cms::builder.device_mobile') }}"
                :class="isDevice('mobile') ? 'bg-gray-100 dark:bg-gray-800' : ''"
                @click="setDevice"
            ><span class="ph ph-device-mobile"></span></button>
        </div>
        <button
            class="rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-800"
            type="button"
            @click="reloadCanvas"
            aria-label="{{ __('cms::builder.reload_canvas') }}"
            title="{{ __('cms::builder.reload_canvas') }}"
        ><span class="ph ph-arrow-clockwise"></span></button>

        <x-synapse-badge :color="$this->page->status === 'published' ? 'success' : 'gray'">
            {{ __('cms::builder.status_' . $this->page->status) }}
        </x-synapse-badge>
        @if ($this->page->status === 'published' && $this->page->hasUnpublishedChanges())
            <x-synapse-badge color="warning">{{ __('cms::builder.unpublished_changes') }}</x-synapse-badge>
        @endif

        <div class="ms-auto flex flex-wrap gap-2">
            <a
                class="btn secondary"
                href="{{ backend_route('cms.builder.form', ['id' => $builderPageId]) }}"
            ><span class="ph ph-gear"></span> {{ __('cms::builder.settings_title') }}</a>
            <a
                class="btn secondary"
                href="{{ backend_route('cms.builder.canvas', ['id' => $builderPageId, 'preview' => 1]) }}"
                target="_blank"
                rel="noopener"
            ><span class="ph ph-eye"></span> {{ __('cms::builder.preview') }}</a>
            @canAccess('cms.builder.publish.update')
            @if ($this->page->status === 'published')
                <button
                    class="btn secondary"
                    type="button"
                    wire:click="unpublish"
                >{{ __('cms::builder.unpublish') }}</button>
            @endif
            <button
                class="btn primary"
                type="button"
                wire:click="publish"
            ><span class="ph ph-rocket-launch"></span> {{ __('cms::builder.publish') }}</button>
            @endcanAccess
        </div>
    </div>

    <div
        class="flex justify-center rounded-2xl bg-gray-100 p-3 dark:bg-gray-900"
        wire:ignore
    >
        {{-- The device width binds on this wrapper: Alpine's CSP build refuses to evaluate any expression on an iframe. --}}
        <div
            class="transition-all"
            :class="frameClass"
        >
            <iframe
                class="h-[calc(100vh-14rem)] w-full rounded-xl border border-gray-200 bg-white dark:border-gray-800"
                src="{{ backend_route('cms.builder.canvas', ['id' => $builderPageId]) }}"
                title="{{ __('cms::builder.canvas_title') }}"
                x-ref="canvas"
            ></iframe>
        </div>
    </div>

    {{-- Widget picker --}}
    <x-synapse-modal
        name="builder-widget-picker"
        maxWidth="2xl"
        labelledby="modal-builder-widget-picker-title"
    >
        <x-synapse-modal-header
            modal="builder-widget-picker"
            :title="__('cms::builder.pick_widget')"
        />
        <div class="syn-modal-body grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ($this->widgets as $type => $widget)
                <button
                    class="hover:border-brand-500 flex flex-col items-center gap-2 rounded-xl border border-gray-200 p-4 text-sm dark:border-gray-800"
                    type="button"
                    wire:click="addWidget('{{ $type }}')"
                >
                    <span class="{{ $widget['icon'] }} text-2xl"></span>
                    {{ __($widget['label']) }}
                </button>
            @endforeach
        </div>
    </x-synapse-modal>

    {{-- Settings drawer: widget props + classes, or region classes. Opened by the
         open-drawer-builder-settings event; Synapse pins the footer actions. --}}
    <x-synapse-drawer
        name="builder-settings"
        :title="e($selectedRegion ? __('cms::builder.region_style') : __('cms::builder.widget_settings'))"
    >
        <div
            class="flex flex-col gap-4"
            wire:key="builder-drawer-{{ $selectedWidgetId ?? ($selectedRegion ?? 'none') }}"
        >
            @if ($this->selectedWidgetDefinition)
                <div>
                    <label
                        class="form-label"
                        for="builder-widget-heading"
                    >{{ __('cms::builder.widget_heading') }}</label>
                    <input
                        class="form-input"
                        id="builder-widget-heading"
                        type="text"
                        maxlength="120"
                        wire:model="widgetHeading"
                    />
                    <label class="mt-2 flex items-center gap-2">
                        <input
                            class="form-checkbox"
                            type="checkbox"
                            wire:model="widgetShowHeading"
                        />
                        {{ __('cms::builder.widget_show_heading') }}
                    </label>
                </div>
                @foreach ($this->selectedWidgetDefinition['props'] as $key => $schema)
                    <div>
                        @if ($schema['type'] === 'bool')
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    class="form-checkbox"
                                    type="checkbox"
                                    wire:model="widgetProps.{{ $key }}"
                                />
                                {{ __($schema['label']) }}
                            </label>
                        @else
                            <label
                                class="form-label"
                                for="prop-{{ $key }}"
                            >{{ __($schema['label']) }}</label>
                            @if ($schema['type'] === 'int')
                                <input
                                    class="form-input"
                                    id="prop-{{ $key }}"
                                    type="number"
                                    min="0"
                                    wire:model="widgetProps.{{ $key }}"
                                />
                            @elseif ($schema['type'] === 'select')
                                <select
                                    class="form-select"
                                    id="prop-{{ $key }}"
                                    wire:model="widgetProps.{{ $key }}"
                                >
                                    @foreach ($schema['options'] as $value => $label)
                                        <option value="{{ $value }}">{{ __($label) }}</option>
                                    @endforeach
                                </select>
                            @elseif ($schema['type'] === 'source')
                                <x-synapse-adv-select
                                    wire-model="widgetProps.{{ $key }}"
                                    :options="$this->pickerOptions($schema['source'])"
                                    :placeholder="__('cms::builder.none')"
                                />
                            @else
                                <input
                                    class="form-input"
                                    id="prop-{{ $key }}"
                                    type="text"
                                    wire:model="widgetProps.{{ $key }}"
                                />
                            @endif
                        @endif
                    </div>
                @endforeach
            @endif

            @if ($selectedWidgetId || $selectedRegion)
                <fieldset>
                    <legend class="form-label">{{ __('cms::builder.classes') }}</legend>
                    @foreach ($this->paletteGroupsForSelection as $group)
                        <details
                            class="group/palette border-b border-gray-100 py-2 dark:border-gray-800"
                            @if ($group['selected'] > 0) open @endif
                        >
                            <summary class="syn-section-label flex cursor-pointer list-none items-center justify-between">
                                <span>{{ __($group['label']) }}@if ($group['selected'] > 0)
                                        · {{ $group['selected'] }}
                                    @endif
                                </span>
                                <span class="ph ph-caret-down transition-transform group-open/palette:rotate-180"></span>
                            </summary>
                            <div class="mt-2 flex flex-wrap gap-1">
                                @foreach ($group['classes'] as $class)
                                    <label
                                        class="has-checked:border-brand-500 has-checked:bg-brand-50 dark:has-checked:bg-brand-900 cursor-pointer rounded-md border border-gray-200 px-2 py-1 text-xs dark:border-gray-800"
                                    >
                                        <input
                                            class="sr-only"
                                            type="checkbox"
                                            value="{{ $class }}"
                                            wire:model="{{ $selectedRegion ? 'regionClasses' : 'widgetClasses' }}"
                                        />
                                        {{ $class }}
                                    </label>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                </fieldset>

                <div>
                    <label
                        class="form-label"
                        for="builder-custom-class"
                    >{{ __('cms::builder.custom_class') }}</label>
                    <input
                        class="form-input"
                        id="builder-custom-class"
                        type="text"
                        placeholder="hero-banner"
                        wire:model="{{ $selectedRegion ? 'regionCustomClass' : 'widgetCustomClass' }}"
                    />
                    <p class="form-help">{{ __('cms::builder.custom_class_help') }}</p>
                </div>
            @endif
        </div>

        @if ($selectedWidgetId || $selectedRegion)
            <x-slot:footer>
                <div class="flex justify-end gap-2">
                    <button
                        class="btn secondary"
                        type="button"
                        @click="drawerOpen = false"
                    >{{ __('cms::labels.cancel') }}</button>
                    <button
                        class="btn primary"
                        type="button"
                        wire:click="{{ $selectedRegion ? 'saveRegion' : 'saveWidget' }}"
                        @click="drawerOpen = false"
                    >{{ __('cms::builder.apply') }}</button>
                </div>
            </x-slot:footer>
        @endif
    </x-synapse-drawer>
</div>

<script nonce="{{ csp_nonce() }}">
    (() => {
        const register = () => {
            Alpine.data('cmsBuilderEditor', () => ({
                device: 'desktop',
                get frameClass() {
                    return {
                        desktop: 'w-full',
                        tablet: 'w-[768px] max-w-full',
                        mobile: 'w-[390px] max-w-full',
                    } [this.device];
                },
                init() {
                    this.onMessage = (event) => {
                        if (event.origin !== window.location.origin || !event.data || !event
                            .data.cmsBuilder) {
                            return;
                        }
                        const data = event.data;
                        if (data.cmsBuilder === 'select') {
                            this.$wire.selectWidget(data.widget);
                        } else if (data.cmsBuilder === 'add') {
                            this.$wire.openPicker(data.region);
                        } else if (data.cmsBuilder === 'region') {
                            this.$wire.selectRegion(data.region);
                        } else if (data.cmsBuilder === 'remove') {
                            this.confirmRemove(data.widget, data.label);
                        } else if (data.cmsBuilder === 'changed') {
                            this.$wire.$refresh();
                        }
                    };
                    window.addEventListener('message', this.onMessage);
                },
                destroy() {
                    window.removeEventListener('message', this.onMessage);
                },
                confirmRemove(widget, label) {
                    const texts = this.$root.dataset;
                    this.$dispatch('confirm-dialog', {
                        title: texts.removeTitle,
                        message: texts.removeMessage.replace(':widget', label || ''),
                        confirmText: texts.removeConfirm,
                        cancelText: texts.removeCancel,
                        confirmColor: 'danger',
                        icon: 'ph ph-trash',
                        wireMethod: 'removeWidget',
                        wireParams: [String(widget)],
                        wireComponent: this.$wire.$id
                    });
                },
                isDevice(device) {
                    return this.device === device;
                },
                setDevice(event) {
                    this.device = event.currentTarget.dataset.device;
                },
                reloadCanvas() {
                    this.$refs.canvas.contentWindow.location.reload();
                },
                refreshCanvas() {
                    this.$refs.canvas.contentWindow.postMessage({
                        cmsBuilder: 'refresh'
                    }, window.location.origin);
                },
            }));
        };
        document.addEventListener('alpine:init', register);
        if (window.Alpine) {
            register();
        }
    })();
</script>
