@if ($verification !== '')
<meta name="google-site-verification" content="{{ $verification }}" />
@endif
@if ($measurementId !== '')
<script nonce="{{ csp_nonce() }}">
    window.dataLayer = window.dataLayer || [];
    function gtag() { dataLayer.push(arguments); }
    var cmsConsentGranted = {{ $requireConsent ? 'false' : 'true' }} || /(?:^|; )cms_consent=granted/.test(document.cookie);
    gtag('consent', 'default', {
        ad_storage: 'denied',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
        analytics_storage: cmsConsentGranted ? 'granted' : 'denied'
    });
    gtag('js', new Date());
    gtag('config', '{{ $measurementId }}');
</script>
<script async nonce="{{ csp_nonce() }}" src="https://www.googletagmanager.com/gtag/js?id={{ $measurementId }}"></script>
@endif
