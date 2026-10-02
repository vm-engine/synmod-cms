<div x-data="{ pageName: '{{ $this->title() }}' }">
    @include('synapps::components.layouts.partials.breadcrumbs', ['breadcrumbs' => $this->breadcrumbs])

    <x-synapse-confirm-dialog />

    <div class="dark:bg-white/3 rounded-2xl border border-gray-200 bg-white dark:border-gray-800">
        <div class="flex flex-wrap justify-between gap-3 px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">{{ __('cms::labels.search_term_list') }}</h3>
            <div class="flex gap-3">
                @if (count($this->locales) > 1)
                    <select class="form-input" wire:model.live="locale" aria-label="{{ __('cms::labels.language') }}">
                        <option value="">{{ __('cms::labels.all_languages') }}</option>
                        @foreach ($this->locales as $code)
                            <option value="{{ $code }}">{{ strtoupper($code) }}</option>
                        @endforeach
                    </select>
                @endif
                <x-synapse-search-box :placeholder="__('cms::labels.search')" wire:model.live.debounce="q" />
            </div>
        </div>

        <div class="max-w-full overflow-x-auto border-t border-gray-100 dark:border-gray-800">
            <table class="datatable min-w-full">
                <thead>
                    <tr>
                        <th><p>{{ __('cms::labels.search_term') }}</p></th>
                        <th><p>{{ __('cms::labels.language') }}</p></th>
                        <th><p>{{ __('cms::labels.search_hits') }}</p></th>
                        <th><p>{{ __('cms::labels.search_last_searched') }}</p></th>
                        <th><p>{{ __('cms::labels.status') }}</p></th>
                        <th class="datatable-col-actions"><p>{{ __('cms::labels.actions') }}</p></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->terms as $term)
                        <tr wire:key="term-{{ $term->id }}">
                            <td>{{ $term->term }}</td>
                            <td>{{ strtoupper($term->locale) }}</td>
                            <td>{{ $term->hits }}</td>
                            <td>{{ $term->last_searched_at?->diffForHumans() ?? '-' }}</td>
                            <td>
                                <x-synapse-badge :color="$term->is_hidden ? 'gray' : 'success'" size="sm">
                                    {{ $term->is_hidden ? __('cms::labels.search_term_hidden') : __('cms::labels.search_term_visible') }}
                                </x-synapse-badge>
                            </td>
                            <td class="datatable-col-actions">
                                <div class="flex gap-2">
                                    @canAccess('cms.search_term.update')
                                    <button class="btn xs" type="button" wire:click="toggleHidden({{ (int) $term->id }})">
                                        {{ $term->is_hidden ? __('cms::labels.search_term_unhide') : __('cms::labels.search_term_hide') }}
                                    </button>
                                    @endcanAccess
                                    @canAccess('cms.search_term.delete')
                                    <button
                                        class="btn xs danger"
                                        type="button"
                                        data-title="{{ __('cms::labels.delete') }}"
                                        data-message="{{ __('cms::labels.search_term_delete_confirm') }}"
                                        data-confirm="{{ __('cms::labels.yes_delete') }}"
                                        data-component="{{ $this->getId() }}"
                                        @click="$dispatch('confirm-dialog', {
                                            title: $el.dataset.title,
                                            message: $el.dataset.message,
                                            confirmText: $el.dataset.confirm,
                                            confirmColor: 'danger',
                                            icon: 'fa-solid fa-trash',
                                            wireMethod: 'delete',
                                            wireParams: [{{ (int) $term->id }}],
                                            wireComponent: $el.dataset.component
                                        })"
                                    >{{ __('cms::labels.delete') }}</button>
                                    @endcanAccess
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="syn-empty-state-text">{{ __('cms::labels.search_terms_empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4 sm:px-6">
            <x-synapse-per-page-selector :paginator="$this->terms" />
        </div>
    </div>
</div>
