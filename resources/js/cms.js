// SortableJS — drag-and-drop reordering
import Sortable from 'sortablejs';
window.Sortable = Sortable;

// File-manager buttons for <x-synapse-rte extension="cms-fm"> (see <x-cms::jodit>).
// The image/file/video buttons open the synmod-fm picker instead of Jodit's own
// uploader, so uploads stay under FM's permissions and storage.
document.addEventListener('alpine:init', () => {
    if (!window.synapseRte) return;

    window.synapseRte.extend('cms-fm', (rte, config) => {
        let pending = null;

        const insertFile = (mode, file) => {
            const editor = rte.editor;
            if (mode === 'image') {
                editor.s.insertImage(file.url);
                return;
            }
            if (mode === 'video') {
                const video = editor.createInside.element('video');
                video.setAttribute('src', file.url);
                video.setAttribute('controls', 'controls');
                editor.s.insertNode(video);
                return;
            }
            const link = editor.createInside.element('a');
            link.setAttribute('href', file.url);
            link.textContent = (editor.s.sel && editor.s.sel.toString()) || file.path.split('/').pop();
            editor.s.insertNode(link);
        };

        // One persistent listener filtered by pickerKey: a cancelled picker
        // leaves nothing behind, and other editors on the page are ignored.
        const onFileSelected = (event) => {
            if (event.detail.key !== config.pickerKey || pending === null || !rte.editor) return;
            insertFile(pending, event.detail);
            pending = null;
        };
        window.addEventListener('fm:file-selected', onFileSelected);

        const openPicker = (mode) => {
            pending = mode;
            const accept = { image: 'image/*', video: 'video/*' }[mode] || '*';
            Livewire.dispatch('fm:open-picker', { accept, key: config.pickerKey });
        };

        return {
            buttons: [
                { name: 'fmFile', icon: 'file', tooltip: config.labels.file, exec: () => openPicker('file') },
                { name: 'fmImage', icon: 'image', tooltip: config.labels.image, exec: () => openPicker('image') },
                { name: 'fmVideo', icon: 'video', tooltip: config.labels.video, exec: () => openPicker('video') },
            ],
            destroy() {
                window.removeEventListener('fm:file-selected', onFileSelected);
            },
        };
    });
});

// <x-cms::locale-tabs> / <x-cms::translation-input> — which language a
// translatable form is showing. Set on the <form> as
// x-data="cmsLocaleTabs('<primary>')"; views only call these methods (CSP build).
document.addEventListener('alpine:init', () => {
    Alpine.data('cmsLocaleTabs', (primary) => ({
        locale: primary,
        primary,
        select(locale) {
            this.locale = locale;
        },
        is(locale) {
            return this.locale === locale;
        },
        isPrimary() {
            return this.locale === this.primary;
        },
    }));
});

// <x-cms::form-layout> — long CMS editors. Submits via $wire.save(); on a
// validation failure, opens the collapsed panel holding the first error and
// focuses it. While $wire.form differs from its load-time snapshot, warns
// before a reload/close or a wire:navigate away. Comparing the whole form
// state (not DOM input events) also catches Jodit, file-picker, adv-select
// and custom-var changes, which never fire `input` on the form.
document.addEventListener('alpine:init', () => {
    Alpine.data('cmsForm', (confirmMessage) => ({
        snapshot: null,
        saving: false,

        init() {
            this.$nextTick(() => {
                this.snapshot = this.serialize();
            });
            this.onBeforeUnload = (event) => {
                if (this.isDirty) {
                    event.preventDefault();
                    event.returnValue = '';
                }
            };
            this.onNavigate = (event) => {
                if (this.isDirty && !window.confirm(confirmMessage)) {
                    event.preventDefault();
                }
            };
            window.addEventListener('beforeunload', this.onBeforeUnload);
            document.addEventListener('livewire:navigate', this.onNavigate);
        },

        destroy() {
            window.removeEventListener('beforeunload', this.onBeforeUnload);
            document.removeEventListener('livewire:navigate', this.onNavigate);
        },

        serialize() {
            return JSON.stringify(this.$wire.form);
        },

        get isDirty() {
            return !this.saving && this.snapshot !== null && this.serialize() !== this.snapshot;
        },

        async submit() {
            this.saving = true;
            try {
                await this.$wire.save();
            } catch (error) {
                // Request failed (network/500): re-arm the guard, edits are still unsaved.
                this.saving = false;
                throw error;
            }
            await this.$nextTick();
            // No error rendered → the save succeeded and a redirect is under way;
            // keep `saving` so the guard doesn't block it.
            if (this.revealFirstError()) {
                this.saving = false;
            }
        },

        // Focus the first inline error (opening its collapsed panel), or the
        // summary when the failing field has no inline @error. False = no errors.
        revealFirstError() {
            const error = this.$root.querySelector('.form-error');
            const summary = this.$root.querySelector('[data-cms-error-summary]');
            if (!error) {
                if (!summary) {
                    return false;
                }
                summary.scrollIntoView({ block: 'start', behavior: 'smooth' });
                summary.focus({ preventScroll: true });
                return true;
            }
            const collapsible = error.closest('.syn-panel[x-data]');
            if (collapsible) {
                Alpine.$data(collapsible).open = true;
            }
            this.$nextTick(() => {
                const field = error.parentElement.querySelector('input:not([type=hidden]), textarea, select, [tabindex]');
                error.scrollIntoView({ block: 'center', behavior: 'smooth' });
                if (field) {
                    field.focus({ preventScroll: true });
                }
            });
            return true;
        },
    }));
});
