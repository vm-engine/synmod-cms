{{-- Placeholder renders before the component mounts: no $this here. --}}
@placeholder
    <div>
        <div class="h-64 animate-pulse rounded-xl bg-gray-100 dark:bg-gray-800"></div>
    </div>
@endplaceholder

<div>
    <x-synapse-panel :title="$this->title()">
        <div class="syn-panel-body space-y-6 p-5 sm:p-6">
            @if ($this->report['state'] === 'not_configured')
                <div class="syn-empty-state">
                    <span class="ph ph-plugs syn-empty-state-icon"></span>
                    <p class="syn-empty-state-text">{{ $this->report['message'] }}</p>
                    @canAccess('cms.settings.read')
                    <a class="btn secondary" href="{{ backend_route('cms.settings') }}" wire:navigate>{{ __('cms::labels.analytics_configure_link') }}</a>
                    @endcanAccess
                </div>
            @elseif ($this->report['state'] === 'error')
                <x-synapse-alert
                    type="danger"
                    :message="$this->report['message']"
                    :dismissible="false"
                />
            @else
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    @foreach ($this->report['tiles'] as $tile)
                        <x-synapse-stat-tile :icon="$tile['icon']" color="blue" :label="$tile['label']" :value="$tile['value']" />
                    @endforeach
                </div>

                @if ($this->report['trend'] !== '')
                    <figure>
                        <figcaption class="syn-section-label mb-2">{{ $this->report['trend_label'] }}</figcaption>
                        <svg class="h-32 w-full text-brand-500" viewBox="0 0 600 120" preserveAspectRatio="none" role="img" aria-label="{{ $this->report['trend_label'] }}">
                            <polyline points="{{ $this->report['trend'] }}" fill="none" stroke="currentColor" stroke-width="2" vector-effect="non-scaling-stroke" />
                        </svg>
                    </figure>
                @endif

                <div class="grid gap-6 lg:grid-cols-2">
                    @foreach ($this->report['tables'] as $table)
                        <div>
                            <h3 class="syn-section-label mb-2">{{ $table['title'] }}</h3>
                            @if ($table['rows'] === [])
                                <p class="syn-empty-state-text">{{ __('cms::labels.analytics_no_data') }}</p>
                            @else
                                <table class="datatable min-w-full">
                                    <thead>
                                        <tr>
                                            @foreach ($table['columns'] as $column)
                                                <th @class(['text-left' => $loop->first, 'text-right' => ! $loop->first])>{{ $column }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($table['rows'] as $row)
                                            <tr>
                                                @foreach ($row as $cell)
                                                    <td @class(['wrap-anywhere' => $loop->first, 'text-right tabular-nums' => ! $loop->first]) data-label="{{ $table['columns'][$loop->index] }}">{{ $cell }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if ($this->isSearchConsole())
                    <p class="form-help">{{ __('cms::labels.analytics_gsc_lag') }}</p>
                @endif
            @endif
        </div>
    </x-synapse-panel>
</div>
