{{--
    Shell for the long CMS editors: main column + settings sidebar (side by side from xl,
    stacked below), a full-width `after` slot, and the sticky Back/Save bar. A failed save
    shows an error summary on top (it also covers fields without an inline @error). The
    cmsForm helper (resources/js/cms.js) submits via $wire.save(), reveals the first
    inline error (or the summary) and guards unsaved changes. Sidebar panels sit in an
    @container, so use `@md:grid-cols-2` (not `sm:`) inside them.

    Props: back (url of the Back button), can (canAccess permission string for Save).
    Slots: default (main column), sidebar, after (full width, below the grid).
--}}
@props(['back', 'can'])

<form
    x-data="cmsForm({{ json_encode(__('cms::labels.unsaved_changes_confirm')) }})"
    @submit.prevent="submit()"
>
    @if (isset($errors) && $errors->any())
        <div
            class="mb-5 sm:mb-6"
            role="alert"
            tabindex="-1"
            data-cms-error-summary
        >
            <x-synapse-alert
                type="danger"
                :dismissible="false"
                :title="__('cms::labels.fix_errors')"
            >
                <ul class="list-disc ps-5">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </x-synapse-alert>
        </div>
    @endif

    <div x-data="cmsLocaleTabs('{{ \VmEngine\Cms\Support\CmsLocale::primary() }}')">
        <div class="grid items-start gap-5 sm:gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="min-w-0 space-y-5 sm:space-y-6">{{ $slot }}</div>
            <aside
                class="@container space-y-5 sm:space-y-6"
                data-cms-form-sidebar
            >{{ $sidebar ?? '' }}</aside>
        </div>

        @isset($after)
            <div class="mt-5 space-y-5 sm:mt-6 sm:space-y-6">{{ $after }}</div>
        @endisset
    </div>

    <div class="syn-form-action-bar">
        <a
            class="btn secondary"
            href="{{ $back }}"
            wire:navigate
        >
            <span class="fa-solid fa-arrow-left"></span> {{ __('cms::labels.back') }}
        </a>
        <div class="flex items-center gap-3">
            <span
                class="flex items-center gap-2 text-sm text-amber-600 dark:text-amber-400"
                role="status"
                x-show="isDirty"
                x-cloak
            >
                <span class="size-2 rounded-full bg-current"></span>
                {{ __('cms::labels.unsaved_changes') }}
            </span>
            @canAccess($can)
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
            @endcanAccess
        </div>
    </div>
</form>
