@pushOnce('styles')
    <script
        src="/build/tinymce/tinymce/tinymce.min.js"
        referrerpolicy="origin"
        data-navigate-track
    ></script>
@endPushOnce

<div
    wire:ignore
    x-data="cmsTinymce('{{ $name }}', {{ $height }}, '{{ $plugins }}', '{{ $toolbar }}')"
>
    <textarea
        id="tinymce-{{ str_replace(['.', '[', ']'], '-', $name) }}"
        x-ref="textarea"
        name="{{ $name }}"
    >{{ $value }}</textarea>
</div>

{{-- Dedicated FM picker for TinyMCE insert-image/insert-link dialogs (modal only, input row hidden) --}}
@php $tinymceFmClass = 'cms-tinymce-fm-' . str_replace(['.', '[', ']'], '-', $name); @endphp
<div class="{{ $tinymceFmClass }}">
    <livewire:fm::file-picker
        pickerKey="tinymce-{{ $name }}"
        wire:key="tinymce-fm-picker-{{ $name }}"
    />
</div>
<style>
    .{{ $tinymceFmClass }}>div>label,
    .{{ $tinymceFmClass }}>div>div.input-group,
    .{{ $tinymceFmClass }}>div>div.mt-2 {
        display: none !important;
    }
</style>

<script nonce="{{ csp_nonce() }}">
    (() => {
        const register = () => {
        Alpine.data('cmsTinymce', (fieldName, height, plugins, toolbar) => ({
            editor: null,
            doInit() {
                const self = this;
                if (typeof tinymce === 'undefined') return;
                if (this.$refs.textarea && tinymce.get(this.$refs.textarea.id)) {
                    tinymce.remove('#' + this.$refs.textarea.id);
                }
                tinymce.init({
                    target: this.$refs.textarea,
                    height,
                    menubar: false,
                    plugins,
                    toolbar,
                    license_key: 'gpl',
                    promotion: false,
                    relative_urls: false,
                    remove_script_host: false,
                    content_style: 'body { font-family: system-ui, sans-serif; font-size: 14px; }',
                    file_picker_callback: function(callback, value, meta) {
                        const key = 'tinymce-' + fieldName;
                        const accept = meta.filetype === 'image' ? 'image/*' : (meta.filetype === 'media' ? 'video/*' : '*');
                        window.dispatchEvent(new CustomEvent('fm:open-picker', {
                            detail: {
                                accept,
                                key
                            }
                        }));
                        window.addEventListener('fm:file-selected', function handler(e) {
                            callback(e.detail.url, {
                                title: e.detail.filename
                            });
                            window.removeEventListener('fm:file-selected', handler);
                        });
                    },
                    setup: function(editor) {
                        self.editor = editor;
                        editor.on('change keyup', function() {
                            self.$wire.set(fieldName, editor.getContent());
                        });
                    }
                });
            },
            destroy() {
                if (this.editor) {
                    this.editor.destroy();
                    this.editor = null;
                }
            },
            init() {
                this.$nextTick(() => this.doInit());
                const onNavigate = () => {
                    this.destroy();
                    document.removeEventListener('livewire:navigate', onNavigate);
                };
                document.addEventListener('livewire:navigate', onNavigate);
            }
        }));
        };
    document.addEventListener('alpine:init', register);
    if (window.Alpine) {
        register();
    }
    })();
</script>
