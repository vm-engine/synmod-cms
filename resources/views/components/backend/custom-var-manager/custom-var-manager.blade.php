<div class="card mt-6">
    <div class="card-header flex items-center justify-between gap-4">
        <h3 class="card-title">{{ __('cms::labels.custom_vars') }}</h3>
        <div class="flex items-center gap-3">
            @if($canManageSchema)
            <label class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 cursor-pointer select-none">
                <input class="form-checkbox" type="checkbox" wire:model.live="allowAdd" />
                {{ __('cms::labels.custom_var_allow_add') }}
            </label>
            @endif
            @if($canManageSchema || $allowAdd)
            <button class="btn xs primary" type="button" wire:click="addVar">
                <span class="fa-solid fa-plus"></span> {{ __('cms::labels.add_var') }}
            </button>
            @endif
        </div>
    </div>

    <div class="card-body">
        @if(empty($value))
            <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('cms::labels.no_custom_vars') }}</p>
        @else
            {{-- Schema manager view: full editable table --}}
            @if($canManageSchema)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 text-left">
                            <th class="pb-2 pr-3 font-medium text-gray-500 dark:text-gray-400 w-36">{{ __('cms::labels.var_key') }}</th>
                            <th class="pb-2 pr-3 font-medium text-gray-500 dark:text-gray-400 w-40">{{ __('cms::labels.var_label') }}</th>
                            <th class="pb-2 pr-3 font-medium text-gray-500 dark:text-gray-400">{{ __('cms::labels.var_value') }}</th>
                            <th class="pb-2 pr-3 font-medium text-gray-500 dark:text-gray-400 w-24 text-center">{{ __('cms::labels.var_editable') }}</th>
                            <th class="pb-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($value as $index => $var)
                        <tr wire:key="var-{{ $index }}">
                            <td class="py-2 pr-3">
                                <input
                                    class="form-input text-sm font-mono"
                                    type="text"
                                    placeholder="key_name"
                                    wire:model.live="value.{{ $index }}.key"
                                />
                            </td>
                            <td class="py-2 pr-3">
                                <input
                                    class="form-input text-sm"
                                    type="text"
                                    placeholder="{{ __('cms::labels.var_label_placeholder') }}"
                                    wire:model.live="value.{{ $index }}.label"
                                />
                            </td>
                            <td class="py-2 pr-3">
                                <input
                                    class="form-input text-sm"
                                    type="text"
                                    wire:model.live="value.{{ $index }}.value"
                                />
                            </td>
                            <td class="py-2 pr-3 text-center">
                                <input
                                    class="form-checkbox"
                                    type="checkbox"
                                    wire:model.live="value.{{ $index }}.editable"
                                    title="{{ __('cms::labels.var_editable_hint') }}"
                                />
                            </td>
                            <td class="py-2 text-right">
                                <button
                                    class="btn xs danger"
                                    type="button"
                                    wire:click="removeVar({{ $index }})"
                                    title="{{ __('cms::labels.remove_var') }}"
                                >
                                    <span class="fa-solid fa-trash"></span>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            {{-- Non-schema user view: only editable values --}}
            <div class="space-y-4">
                @foreach($value as $index => $var)
                @php $isNew = empty($var['key']); $label = $var['label'] ?: $var['key']; @endphp
                <div wire:key="var-{{ $index }}">
                    @if($isNew)
                        <div class="flex gap-2 items-end">
                            <div class="flex-1">
                                <label class="form-label required">{{ __('cms::labels.var_key') }}</label>
                                <input class="form-input font-mono text-sm" type="text" placeholder="key_name" wire:model.live="value.{{ $index }}.key" />
                            </div>
                            <div class="flex-1">
                                <label class="form-label">{{ __('cms::labels.var_value') }}</label>
                                <input class="form-input text-sm" type="text" wire:model.live="value.{{ $index }}.value" />
                            </div>
                            <button class="btn xs danger mb-0.5" type="button" wire:click="removeVar({{ $index }})">
                                <span class="fa-solid fa-trash"></span>
                            </button>
                        </div>
                    @elseif($var['editable'] ?? true)
                        <label class="form-label">{{ $label }}</label>
                        <input class="form-input" type="text" wire:model.live="value.{{ $index }}.value" />
                    @else
                        <label class="form-label">{{ $label }}</label>
                        <div class="form-input bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 flex items-center gap-2 cursor-not-allowed">
                            <span class="fa-solid fa-lock text-xs opacity-50"></span>
                            <span>{{ $var['value'] }}</span>
                        </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        @endif
    </div>
</div>
