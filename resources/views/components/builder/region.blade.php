{{--
    Page builder region. Edit mode is rendered inside the builder canvas Livewire component:
    wire:sort / wire:click target its methods, @click targets its cmsBuilderCanvas Alpine data.
    Widget HTML comes from the CMS widget components (never raw user input).
--}}
@if ($editing)
    <section
        class="cms-builder-region relative rounded-xl border-2 border-dashed border-sky-300 p-3 {{ $regionClass }}"
        data-region="{{ $name }}"
    >
        <div class="mb-2 flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-sky-600">
            <span>{{ $label }}</span>
            <button
                class="rounded px-2 py-1 hover:bg-sky-50"
                type="button"
                data-region="{{ $name }}"
                @click="editRegion"
            ><span class="ph ph-paint-brush"></span> {{ __('cms::builder.style') }}</button>
        </div>
        <div
            class="min-h-16 space-y-3"
            wire:sort="moveWidget"
            wire:sort:group="cms-builder"
            wire:sort:group-id="{{ $name }}"
        >
            @foreach ($items as $item)
                <div
                    class="cms-builder-widget group relative rounded-lg outline-2 outline-offset-2 outline-transparent hover:outline-sky-400 {{ $item['class'] }}"
                    wire:key="builder-widget-{{ $item['id'] }}"
                    wire:sort:item="{{ $item['id'] }}"
                >
                    <div
                        class="absolute end-2 top-2 z-10 hidden items-center gap-1 rounded-md bg-sky-600 px-1 py-0.5 text-white shadow group-hover:flex">
                        <span
                            class="ph ph-dots-six-vertical cursor-grab px-1"
                            wire:sort:handle
                            aria-hidden="true"
                        ></span>
                        <span class="px-1 text-xs">{{ $item['label'] }}</span>
                        <span
                            class="flex items-center gap-1"
                            wire:sort:ignore
                        >
                            <button
                                class="px-1"
                                type="button"
                                data-widget="{{ $item['id'] }}"
                                aria-label="{{ __('cms::builder.settings') }}"
                                @click="select"
                            ><span class="ph ph-gear"></span></button>
                            <button
                                class="px-1"
                                type="button"
                                aria-label="{{ __('cms::builder.duplicate') }}"
                                wire:click="duplicateWidget('{{ $item['id'] }}')"
                            ><span class="ph ph-copy"></span></button>
                            <button
                                class="px-1"
                                type="button"
                                data-widget="{{ $item['id'] }}"
                                data-label="{{ $item['label'] }}"
                                aria-label="{{ __('cms::builder.remove') }}"
                                @click="remove"
                            ><span class="ph ph-trash"></span></button>
                        </span>
                    </div>
                    @if ($item['heading'] !== '')
                        <h2 class="cms-widget__heading cms-builder-widget__heading">{{ $item['heading'] }}</h2>
                    @endif
                    @if ($item['empty'])
                        <div class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-500">
                            <span class="{{ $item['icon'] }}"></span> {{ $item['label'] }} —
                            {{ __('cms::builder.widget_empty') }}
                        </div>
                    @elseif ($item['html'] !== null)
                        {!! $item['html'] !!}
                    @else
                        <div class="rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-700">
                            <span class="ph ph-warning"></span> {{ __('cms::builder.widget_failed') }}
                            ({{ $item['label'] }})
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        <button
            class="mt-3 w-full rounded-lg border border-dashed border-sky-300 py-2 text-sm text-sky-600 hover:bg-sky-50"
            type="button"
            data-region="{{ $name }}"
            @click="add"
        ><span class="ph ph-plus"></span> {{ __('cms::builder.add_widget') }}</button>
    </section>
@else
    <section class="cms-builder-region cms-builder-region--{{ $name }} {{ $regionClass }}">
        @foreach ($visibleItems() as $item)
            <div class="cms-builder-widget {{ $item['class'] }}">
                @if ($item['heading'] !== '')
                    <h2 class="cms-widget__heading cms-builder-widget__heading">{{ $item['heading'] }}</h2>
                @endif
                {!! $item['html'] !!}
            </div>
        @endforeach
    </section>
@endif
