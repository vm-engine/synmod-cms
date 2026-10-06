<div class="card mt-6">
    <div class="card-header flex items-center justify-between gap-4">
        <div>
            <h3 class="card-title">{{ __('cms::labels.custom_fields_schema') }}</h3>
            <p class="text-xs text-gray-400">{{ __('cms::labels.custom_fields_schema_hint') }}</p>
        </div>
        <button class="btn xs primary" type="button" wire:click="addField">
            <span class="fa-solid fa-plus"></span> {{ __('cms::labels.add_field') }}
        </button>
    </div>

    <div class="card-body space-y-4">
        @forelse ($form->fieldSchema as $index => $field)
            <div class="rounded border border-gray-200 p-4 dark:border-gray-700" wire:key="field-def-{{ $field['id'] ?? 'new-'.$index }}">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <label class="form-label required">{{ __('cms::labels.field_name') }}</label>
                        <input class="form-input font-mono text-sm @error('form.fieldSchema.'.$index.'.name') has-error @enderror" type="text" placeholder="field_name" wire:model.blur="form.fieldSchema.{{ $index }}.name" />
                        @error('form.fieldSchema.'.$index.'.name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label required">{{ __('cms::labels.field_label') }}</label>
                        <input class="form-input text-sm @error('form.fieldSchema.'.$index.'.label') has-error @enderror" type="text" wire:model.blur="form.fieldSchema.{{ $index }}.label" />
                        @error('form.fieldSchema.'.$index.'.label') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label required">{{ __('cms::labels.field_type') }}</label>
                        <select class="form-select text-sm" wire:model.live="form.fieldSchema.{{ $index }}.type" @disabled($this->isFieldLocked($field))>
                            @foreach ($this->fieldTypeOptions as $type)
                                <option value="{{ $type['value'] }}">{{ $type['label'] }}</option>
                            @endforeach
                        </select>
                        @if ($this->isFieldLocked($field))
                            <p class="mt-1 text-xs text-gray-400"><span class="fa-solid fa-lock"></span> {{ __('cms::labels.field_type_locked') }}</p>
                        @endif
                        @error('form.fieldSchema.'.$index.'.type') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">{{ __('cms::labels.field_default') }}</label>
                        <input class="form-input text-sm" type="text" wire:model.blur="form.fieldSchema.{{ $index }}.default" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="form-label">{{ __('cms::labels.field_help') }}</label>
                        <input class="form-input text-sm" type="text" wire:model.blur="form.fieldSchema.{{ $index }}.help" />
                    </div>
                </div>

                @if ($this->fieldHasOptions($field))
                    <div class="mt-3">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="form-label mb-0">{{ __('cms::labels.field_options') }}</span>
                            <button class="btn xs secondary" type="button" wire:click="addOption({{ $index }})">
                                <span class="fa-solid fa-plus"></span> {{ __('cms::labels.add_option') }}
                            </button>
                        </div>
                        @error('form.fieldSchema.'.$index.'.options') <p class="form-error">{{ $message }}</p> @enderror
                        @foreach ($field['options'] ?? [] as $optionIndex => $option)
                            <div class="mb-2 flex gap-2" wire:key="field-{{ $index }}-opt-{{ $optionIndex }}">
                                <input class="form-input font-mono text-sm" type="text" placeholder="{{ __('cms::labels.field_option_value') }}" wire:model.blur="form.fieldSchema.{{ $index }}.options.{{ $optionIndex }}.value" />
                                <input class="form-input text-sm" type="text" placeholder="{{ __('cms::labels.field_option_label') }}" wire:model.blur="form.fieldSchema.{{ $index }}.options.{{ $optionIndex }}.label" />
                                <button class="btn xs danger" type="button" title="{{ __('cms::labels.remove_option') }}" wire:click="removeOption({{ $index }}, {{ $optionIndex }})">
                                    <span class="fa-solid fa-trash"></span>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="mt-3 flex flex-wrap items-center gap-4 text-sm">
                    <label class="flex items-center gap-2"><input class="form-checkbox" type="checkbox" wire:model="form.fieldSchema.{{ $index }}.required" /> {{ __('cms::labels.field_required') }}</label>
                    <label class="flex items-center gap-2"><input class="form-checkbox" type="checkbox" wire:model="form.fieldSchema.{{ $index }}.searchable" /> {{ __('cms::labels.field_searchable') }}</label>
                    <label class="flex items-center gap-2"><input class="form-checkbox" type="checkbox" wire:model="form.fieldSchema.{{ $index }}.filterable" /> {{ __('cms::labels.field_filterable') }}</label>
                    @if ($this->fieldIsSortable($field))
                        <label class="flex items-center gap-2"><input class="form-checkbox" type="checkbox" wire:model="form.fieldSchema.{{ $index }}.sortable" /> {{ __('cms::labels.field_sortable') }}</label>
                    @endif
                    <div class="ml-auto flex gap-1">
                        <button class="btn xs secondary" type="button" wire:click="moveField({{ $index }}, -1)"><span class="fa-solid fa-arrow-up"></span></button>
                        <button class="btn xs secondary" type="button" wire:click="moveField({{ $index }}, 1)"><span class="fa-solid fa-arrow-down"></span></button>
                        <button class="btn xs danger" type="button" title="{{ __('cms::labels.remove_field') }}" wire:click="removeField({{ $index }})"><span class="fa-solid fa-trash"></span></button>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('cms::labels.no_custom_fields') }}</p>
        @endforelse
    </div>
</div>
