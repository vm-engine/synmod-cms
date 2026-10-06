<form class="cms-field-filters" wire:submit="applyFieldFilters">
    @foreach ($filter->filterableFields() as $field)
        <div class="cms-field-filters__field" wire:key="field-filter-{{ $field->name }}">
            <label class="cms-field-filters__label">{{ $field->displayLabel() }}</label>
            @if ($isDate($field))
                <div class="cms-field-filters__range">
                    <x-synapse-datepicker class="cms-field-filters__input" wire:model="{{ $model($field, 'min') }}" :flatpickr="$datepickerConfig($field)" placeholder="{{ __('cms::labels.filter_min') }}" />
                    <x-synapse-datepicker class="cms-field-filters__input" wire:model="{{ $model($field, 'max') }}" :flatpickr="$datepickerConfig($field)" placeholder="{{ __('cms::labels.filter_max') }}" />
                </div>
            @elseif ($isRange($field))
                <div class="cms-field-filters__range">
                    <input class="cms-field-filters__input" type="number" step="any" wire:model="{{ $model($field, 'min') }}" placeholder="{{ __('cms::labels.filter_min') }}" />
                    <input class="cms-field-filters__input" type="number" step="any" wire:model="{{ $model($field, 'max') }}" placeholder="{{ __('cms::labels.filter_max') }}" />
                </div>
            @elseif ($isChoice($field))
                <select class="cms-field-filters__select" wire:model="{{ $model($field) }}">
                    <option value="">{{ __('cms::labels.filter_any') }}</option>
                    @foreach ($choices($field) as $choice)
                        <option value="{{ $choice['value'] }}">{{ $choice['label'] }}</option>
                    @endforeach
                </select>
            @elseif ($isMulti($field))
                <div class="cms-field-filters__choices">
                    @foreach ($choices($field) as $choice)
                        <label class="cms-field-filters__choice">
                            <input type="checkbox" value="{{ $choice['value'] }}" wire:model="{{ $model($field) }}" />
                            {{ $choice['label'] }}
                        </label>
                    @endforeach
                </div>
            @else
                <input class="cms-field-filters__input" type="text" wire:model="{{ $model($field) }}" />
            @endif
        </div>
    @endforeach

    @if ($filter->sortableFields()->isNotEmpty())
        <div class="cms-field-filters__field">
            <label class="cms-field-filters__label">{{ __('cms::labels.filter_sort_by') }}</label>
            <div class="cms-field-filters__range">
                <select class="cms-field-filters__select" wire:model="sort">
                    <option value="">{{ __('cms::labels.filter_sort_default') }}</option>
                    @foreach ($filter->sortableFields() as $field)
                        <option value="{{ $field->name }}">{{ $field->displayLabel() }}</option>
                    @endforeach
                </select>
                <select class="cms-field-filters__select" wire:model="dir">
                    <option value="asc">{{ __('cms::labels.filter_ascending') }}</option>
                    <option value="desc">{{ __('cms::labels.filter_descending') }}</option>
                </select>
            </div>
        </div>
    @endif

    <div class="cms-field-filters__actions">
        <button class="cms-field-filters__button" type="submit">{{ __('cms::labels.apply_filters') }}</button>
        <button class="cms-field-filters__reset" type="button" wire:click="resetFieldFilters">{{ __('cms::labels.reset_filters') }}</button>
    </div>
</form>
