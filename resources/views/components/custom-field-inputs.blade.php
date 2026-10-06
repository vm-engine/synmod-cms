<x-synapse-panel :title="__('cms::labels.custom_fields')">
    <div class="syn-panel-body space-y-4 p-5 sm:p-6" x-data="cmsLocaleTabs('{{ $primary }}')">
        @if (count($locales) > 1)
            <div class="syn-tabs mb-4" role="tablist">
                @foreach ($locales as $locale)
                    <button class="syn-tab" type="button" role="tab" :class="is('{{ $locale }}') ? 'syn-tab-active' : ''" @click="select('{{ $locale }}')">
                        {{ strtoupper($locale) }}
                        @if ($locale === $primary)
                            <span class="text-xs text-gray-400">({{ __('cms::labels.default_language') }})</span>
                        @endif
                    </button>
                @endforeach
            </div>
        @endif

        @foreach ($locales as $locale)
            <div class="space-y-4" x-show="is('{{ $locale }}')" @if ($locale !== $primary) style="display: none" @endif>
                @foreach ($schema as $definition)
                    <div wire:key="cf-{{ $locale }}-{{ $definition['name'] }}">
                        @if ($type($definition) === 'checkbox')
                            <label class="flex items-center gap-2">
                                <input class="form-checkbox" id="{{ $inputId($locale, $definition) }}" type="checkbox" wire:model="{{ $path($locale, $definition) }}" />
                                {{ $label($definition) }}
                            </label>
                        @else
                            <label class="form-label {{ $isRequired($definition, $locale) ? 'required' : '' }}" for="{{ $inputId($locale, $definition) }}">{{ $label($definition) }}</label>

                            @switch ($type($definition))
                                @case('textarea')
                                    <textarea class="form-input @error($path($locale, $definition)) has-error @enderror" id="{{ $inputId($locale, $definition) }}" rows="3" wire:model="{{ $path($locale, $definition) }}"></textarea>
                                    @break
                                @case('select')
                                    <select class="form-select @error($path($locale, $definition)) has-error @enderror" id="{{ $inputId($locale, $definition) }}" wire:model="{{ $path($locale, $definition) }}">
                                        <option value="">—</option>
                                        @foreach ($options($definition) as $option)
                                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                    @break
                                @case('radio')
                                    <div class="flex flex-wrap gap-4">
                                        @foreach ($options($definition) as $option)
                                            <label class="flex items-center gap-2"><input class="form-radio" type="radio" value="{{ $option['value'] }}" wire:model="{{ $path($locale, $definition) }}" /> {{ $option['label'] }}</label>
                                        @endforeach
                                    </div>
                                    @break
                                @case('date')
                                @case('datetime')
                                    <x-synapse-datepicker
                                        id="{{ $inputId($locale, $definition) }}"
                                        wire:model="{{ $path($locale, $definition) }}"
                                        :flatpickr="$datepickerConfig($definition)"
                                    />
                                    @break
                                @case('checkboxes')
                                    <div class="flex flex-wrap gap-4">
                                        @foreach ($options($definition) as $option)
                                            <label class="flex items-center gap-2"><input class="form-checkbox" type="checkbox" value="{{ $option['value'] }}" wire:model="{{ $path($locale, $definition) }}" /> {{ $option['label'] }}</label>
                                        @endforeach
                                    </div>
                                    @break
                                @default
                                    <input
                                        class="form-input @error($path($locale, $definition)) has-error @enderror"
                                        id="{{ $inputId($locale, $definition) }}"
                                        type="{{ $htmlInputType($definition) }}"
                                        @if ($type($definition) === 'number') step="any" @endif
                                        wire:model="{{ $path($locale, $definition) }}"
                                    />
                            @endswitch
                        @endif

                        @error($path($locale, $definition))
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                        @if ($help($definition) !== '')
                            <p class="form-help">{{ $help($definition) }}</p>
                        @endif
                        @if ($locale !== $primary)
                            <p class="form-help">{{ __('cms::labels.translation_fallback_hint') }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</x-synapse-panel>
