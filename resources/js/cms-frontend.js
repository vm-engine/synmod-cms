// synmod-cms — public/frontend Alpine components.
// Imported by resources/js/frontend.js (wired by `php artisan cms:setup`).

// alpine:init fires once per full page load. After a wire:navigate from another layout this entry
// loads with Alpine already running, so register right away when it is.
const onAlpine = (register) => (window.Alpine ? register() : document.addEventListener('alpine:init', register));

// <livewire:cms::frontend.search-box> — open/close and keyboard navigation
// over the server-rendered [role=option] items (CSP build: views call methods).
onAlpine(() => {
    Alpine.data('cmsSearchBox', (listId) => ({
        open: false,
        active: -1,
        options() {
            return this.$refs.list ? Array.from(this.$refs.list.querySelectorAll('[role=option]')) : [];
        },
        show() {
            this.open = true;
        },
        close() {
            this.open = false;
            this.active = -1;
        },
        move(step) {
            const options = this.options();
            if (options.length === 0) {
                return;
            }
            this.open = true;
            this.active = (this.active + step + options.length) % options.length;
            options[this.active].scrollIntoView({ block: 'nearest' });
        },
        activeId() {
            const option = this.options()[this.active];
            return option ? option.id : '';
        },
        choose(event) {
            const option = this.options()[this.active];
            const link = option ? option.querySelector('a') : null;
            if (link) {
                event.preventDefault();
                window.location.href = link.href;
            }
        },
    }));
});
