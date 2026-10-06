<div
    class="cms-consent"
    role="dialog"
    aria-live="polite"
    aria-label="{{ __('cms::labels.consent_settings') }}"
    x-data="cmsConsent"
    x-show="open"
    x-cloak
    x-on:cms-consent-open.window="show"
>
    <p class="cms-consent__message">{{ __('cms::labels.consent_message') }}</p>
    <div class="cms-consent__actions">
        <button
            class="cms-consent__button cms-consent__button--reject"
            type="button"
            x-on:click="reject"
        >{{ __('cms::labels.consent_reject') }}</button>
        <button
            class="cms-consent__button cms-consent__button--accept"
            type="button"
            x-on:click="accept"
        >{{ __('cms::labels.consent_accept') }}</button>
    </div>
</div>

<script nonce="{{ csp_nonce() }}">
    (() => {
        const register = () => {
            Alpine.data('cmsConsent', () => ({
                open: !/(?:^|; )cms_consent=/.test(document.cookie),
                show() {
                    this.open = true;
                },
                accept() {
                    this.choose('granted');
                },
                reject() {
                    this.choose('denied');
                },
                choose(value) {
                    document.cookie = 'cms_consent=' + value + '; max-age=' + (180 * 24 * 60 * 60) +
                        '; path=/; SameSite=Lax';
                    if (typeof window.gtag === 'function') {
                        window.gtag('consent', 'update', {
                            analytics_storage: value
                        });
                    }
                    this.open = false;
                },
            }));
        };
        document.addEventListener('alpine:init', register);
        if (window.Alpine) {
            register();
        }
    })();
</script>
