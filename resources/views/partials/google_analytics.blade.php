@php
    $gaMeasurementId = preg_replace('/[^A-Za-z0-9\-]/', '', trim((string) config('services.google_analytics.measurement_id', '')));
@endphp
@if($gaMeasurementId !== '')
    <!-- Google tag (gtag.js) - Google Analytics 4 -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaMeasurementId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json($gaMeasurementId));

        window.__slFiredGaIds = window.__slFiredGaIds || new Set();
        window.slTrackGaFromMeta = function (metaEventName, params, customEventId) {
            if (typeof window.gtag !== 'function' || !metaEventName) return;
            var map = {
                'CompleteRegistration': 'sign_up',
                'InitiateCheckout': 'begin_checkout',
                'Purchase': 'purchase',
                'Lead': 'generate_lead'
            };
            var gaEvent = map[metaEventName];
            if (!gaEvent) return;

            if (customEventId) {
                var dedupKey = gaEvent + ':' + String(customEventId);
                if (window.__slFiredGaIds.has(dedupKey)) return;
                window.__slFiredGaIds.add(dedupKey);
            }

            var p = params || {};
            var payload = {};
            if (p.value !== undefined && p.value !== null && p.value !== '') {
                payload.value = Number(p.value) || 0;
            }
            if (p.currency) {
                payload.currency = String(p.currency);
            } else if (gaEvent === 'begin_checkout' || gaEvent === 'purchase') {
                payload.currency = 'IDR';
            }
            if (p.content_name) {
                payload.item_name = String(p.content_name);
            }
            if (gaEvent === 'purchase' && customEventId) {
                payload.transaction_id = String(customEventId);
            }
            if (gaEvent === 'sign_up') {
                payload.method = p.content_name ? String(p.content_name) : 'website';
            }
            window.gtag('event', gaEvent, payload);
        };
    </script>
    <!-- End Google Analytics 4 -->
@endif
