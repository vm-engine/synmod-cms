<div x-data="cmsBuilderSlugForm('{{ $this->title() }}', {{ $builderPageId ? 'false' : 'true' }})">
    <x-synapse-breadcrumbs :breadcrumbs="$this->breadcrumbs" />

    <x-synapse-panel :title="__('cms::builder.settings_title')">
        <form
            class="syn-panel-body space-y-4 px-5 py-4 sm:px-6"
            x-data="cmsLocaleTabs('{{ $this->primaryLocale() }}')"
            wire:submit="save"
        >
            <x-cms::locale-tabs :values="$form->translations" />

            <div x-show="isPrimary()">
                <label
                    class="form-label required"
                    for="title"
                >{{ __('cms::labels.title') }}</label>
                <input
                    class="form-input @error('form.title') has-error @enderror"
                    id="title"
                    type="text"
                    wire:model.blur="form.title"
                    @blur="handleTitleBlur($event.target.value)"
                />
                @error('form.title')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>
            <x-cms::translation-input
                field="title"
                :label="__('cms::labels.title')"
                :placeholder="$form->title"
                :values="$form->translations"
            />

            <div>
                <label
                    class="form-label required"
                    for="slug"
                >{{ __('cms::labels.slug') }}</label>
                <input
                    class="form-input @error('form.slug') has-error @enderror"
                    id="slug"
                    type="text"
                    wire:model.live.blur="form.slug"
                    @input="stopAutoSlug"
                />
                @error('form.slug')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label
                    class="form-label required"
                    for="template"
                >{{ __('cms::builder.template') }}</label>
                <select
                    class="form-select @error('form.template') has-error @enderror"
                    id="template"
                    wire:model="form.template"
                >
                    @foreach ($this->templateOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('form.template')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div x-show="isPrimary()">
                <label
                    class="form-label"
                    for="meta_title"
                >{{ __('cms::labels.meta_title') }}</label>
                <input
                    class="form-input @error('form.meta_title') has-error @enderror"
                    id="meta_title"
                    type="text"
                    wire:model="form.meta_title"
                />
                @error('form.meta_title')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>
            <x-cms::translation-input
                field="meta_title"
                :label="__('cms::labels.meta_title')"
                :placeholder="$form->meta_title"
                :values="$form->translations"
            />

            <div x-show="isPrimary()">
                <label
                    class="form-label"
                    for="meta_description"
                >{{ __('cms::labels.meta_description') }}</label>
                <textarea
                    class="form-input @error('form.meta_description') has-error @enderror"
                    id="meta_description"
                    rows="3"
                    wire:model="form.meta_description"
                ></textarea>
                @error('form.meta_description')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>
            <x-cms::translation-input
                field="meta_description"
                type="textarea"
                :label="__('cms::labels.meta_description')"
                :placeholder="$form->meta_description"
                :values="$form->translations"
            />

            <div class="flex flex-wrap justify-end gap-3 pt-2">
                <a
                    class="btn secondary"
                    href="{{ backend_route('cms.builder.index') }}"
                    wire:navigate
                >{{ __('cms::labels.cancel') }}</a>
                @if ($builderPageId)
                    <a
                        class="btn secondary"
                        href="{{ backend_route('cms.builder.editor', ['id' => $builderPageId]) }}"
                    ><span class="ph ph-layout"></span> {{ __('cms::builder.open_builder') }}</a>
                @endif
                <button
                    class="btn primary"
                    type="submit"
                ><span class="ph ph-floppy-disk"></span> {{ __('cms::labels.save') }}</button>
            </div>
        </form>
    </x-synapse-panel>
</div>

<script nonce="{{ csp_nonce() }}">
    (() => {
        const register = () => {
            Alpine.data('cmsBuilderSlugForm', (pageName, autoSlug) => ({
                pageName,
                autoSlug,
                generateSlug(value) {
                    return value.toLowerCase().replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-')
                        .replace(/-+/g, '-')
                        .trim();
                },
                handleTitleBlur(value) {
                    if (this.autoSlug && !this.$wire.form.slug) {
                        this.$wire.set('form.slug', this.generateSlug(value));
                    }
                },
                stopAutoSlug() {
                    this.autoSlug = false;
                },
            }));
        };
        document.addEventListener('alpine:init', register);
        if (window.Alpine) {
            register();
        }
    })();
</script>
