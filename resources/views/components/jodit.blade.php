<div
    wire:ignore
    x-data="cmsJodit({{ json_encode($editorConfig()) }})"
>
    <textarea
        class="form-input"
        id="jodit-{{ str_replace(['.', '[', ']'], '-', $name) }}"
        x-ref="textarea"
        name="{{ $name }}"
        rows="12"
    >{{ $value }}</textarea>
</div>

{{-- Dedicated FM picker for the Jodit image/file buttons (modal only, input row hidden) --}}
@php $joditFmClass = 'cms-jodit-fm-' . str_replace(['.', '[', ']'], '-', $name); @endphp
<div class="{{ $joditFmClass }}">
    <livewire:fm::file-picker
        pickerKey="jodit-{{ $name }}"
        wire:key="jodit-fm-picker-{{ $name }}"
    />
</div>
<style>
    .{{ $joditFmClass }}>div>label,
    .{{ $joditFmClass }}>div>div.input-group,
    .{{ $joditFmClass }}>div>div.mt-2 {
        display: none !important;
    }
</style>
