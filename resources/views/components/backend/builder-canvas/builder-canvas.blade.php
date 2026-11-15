<div
    class="cms-builder-canvas [html:has(&)]:overflow-x-clip [body:has(&)]:overflow-x-clip"
    x-data="cmsBuilderCanvas"
    @cms-builder-changed.window="changed"
>
    @include($this->template['view'], [
        'layout' => $this->page->layout_draft ?? [],
        'regions' => $this->template['regions'],
        'editing' => !$preview,
    ])
</div>

<script nonce="{{ csp_nonce() }}">
    (() => {
        const register = () => {
            Alpine.data('cmsBuilderCanvas', () => ({
                init() {
                    this.onMessage = (event) => {
                        if (event.origin === window.location.origin && event.data && event.data
                            .cmsBuilder === 'refresh') {
                            this.$wire.$refresh();
                        }
                    };
                    window.addEventListener('message', this.onMessage);
                    // Keep the editor in place: widget links must not navigate the iframe.
                    this.$el.addEventListener('click', (event) => {
                        if (event.target.closest('a')) {
                            event.preventDefault();
                        }
                    }, true);
                },
                destroy() {
                    window.removeEventListener('message', this.onMessage);
                },
                post(action, detail) {
                    if (window.parent === window) {
                        return;
                    }
                    window.parent.postMessage(Object.assign({
                        cmsBuilder: action
                    }, detail), window.location.origin);
                },
                select(event) {
                    this.post('select', {
                        widget: event.currentTarget.dataset.widget
                    });
                },
                add(event) {
                    this.post('add', {
                        region: event.currentTarget.dataset.region
                    });
                },
                // The shell confirms, then removes (the dialog covers the whole editor, not just the frame).
                remove(event) {
                    this.post('remove', {
                        widget: event.currentTarget.dataset.widget,
                        label: event.currentTarget.dataset.label
                    });
                },
                editRegion(event) {
                    this.post('region', {
                        region: event.currentTarget.dataset.region
                    });
                },
                changed() {
                    this.post('changed', {});
                },
            }));
        };
        document.addEventListener('alpine:init', register);
        if (window.Alpine) {
            register();
        }
    })();
</script>
