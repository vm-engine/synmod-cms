// SortableJS — drag-and-drop reordering
import Sortable from 'sortablejs';
window.Sortable = Sortable;

// <x-cms::jodit> — Jodit WYSIWYG bound to a Livewire property.
// Jodit owns its DOM (the Blade wrapper is wire:ignore'd) and is imported
// lazily so pages without an editor never download it. Changes are pushed
// with a deferred $wire.set (sent with the next request, like wire:model).
// The image/file buttons open the synmod-fm picker instead of Jodit's own
// uploader, so uploads stay under FM's permissions and storage.
document.addEventListener('alpine:init', () => {
    Alpine.data('cmsJodit', (config) => ({
        editor: null,
        pendingInsert: null,
        onFileSelected: null,

        async init() {
            let Jodit;
            try {
                // 'jodit' is the ESM core (24 plugins); all.js adds the rest the
                // toolbar and paste cleanup rely on (justify, source, video,
                // file, paste, clipboard, clean-html, ...).
                [{ Jodit }] = await Promise.all([
                    import('jodit'),
                    import('jodit/esm/plugins/all.js'),
                    import('jodit/es2021/jodit.min.css'),
                ]);
            } catch (error) {
                // Assets not built: keep the plain textarea editable and bound.
                console.error('[cms] Jodit failed to load, using plain textarea.', error);
                this.$refs.textarea.addEventListener('input', (event) => {
                    this.$wire.set(config.field, event.target.value, false);
                });
                return;
            }

            this.editor = Jodit.make(this.$refs.textarea, {
                height: config.height,
                toolbarAdaptive: false,
                askBeforePasteHTML: false,
                askBeforePasteFromWord: false,
                defaultActionOnPaste: 'insert_clear_html',
                // Same functions as the TinyMCE toolbar; everything here survives
                // the cms_rte purifier (text-align, local media only).
                buttons: [
                    'undo', 'redo', '|',
                    'paragraph', '|',
                    'bold', 'italic', '|',
                    'left', 'center', 'right', '|',
                    'ul', 'ol', '|',
                    'link',
                    { name: 'fmFile', icon: 'file', tooltip: config.labels.file, exec: () => this.openPicker('file') },
                    { name: 'fmImage', icon: 'image', tooltip: config.labels.image, exec: () => this.openPicker('image') },
                    { name: 'fmVideo', icon: 'video', tooltip: config.labels.video, exec: () => this.openPicker('video') },
                    '|', 'source',
                ],
            });

            this.editor.events.on('change', (value) => this.$wire.set(config.field, value, false));

            // One persistent listener filtered by pickerKey: a cancelled picker
            // leaves nothing behind, and other editors on the page are ignored.
            this.onFileSelected = (event) => {
                if (event.detail.key !== config.pickerKey || this.pendingInsert === null) return;
                this.insertFile(this.pendingInsert, event.detail);
                this.pendingInsert = null;
            };
            window.addEventListener('fm:file-selected', this.onFileSelected);
        },

        openPicker(mode) {
            this.pendingInsert = mode;
            const accept = { image: 'image/*', video: 'video/*' }[mode] || '*';
            Livewire.dispatch('fm:open-picker', { accept, key: config.pickerKey });
        },

        insertFile(mode, file) {
            if (mode === 'image') {
                this.editor.s.insertImage(file.url);
                return;
            }
            if (mode === 'video') {
                const video = this.editor.createInside.element('video');
                video.setAttribute('src', file.url);
                video.setAttribute('controls', 'controls');
                this.editor.s.insertNode(video);
                return;
            }
            const link = this.editor.createInside.element('a');
            link.setAttribute('href', file.url);
            link.textContent = this.editor.s.sel?.toString() || file.path.split('/').pop();
            this.editor.s.insertNode(link);
        },

        destroy() {
            if (this.onFileSelected) {
                window.removeEventListener('fm:file-selected', this.onFileSelected);
            }
            this.editor?.destruct();
            this.editor = null;
        },
    }));
});
