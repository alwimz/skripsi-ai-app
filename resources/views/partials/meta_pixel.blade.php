@php
    $metaPixelId = preg_replace('/\D+/', '', (string) config('services.meta.pixel_id', ''));
    $metaFlashedEvent = session('meta_pixel_event');
    $metaInitialEvent = (isset($metaViewEvent) && is_array($metaViewEvent) && !empty($metaViewEvent['name'])) ? $metaViewEvent : null;
@endphp
@if($metaPixelId !== '')
    <!-- Meta Pixel Code -->
    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        window.__slFiredMetaIds = window.__slFiredMetaIds || new Set();
        window.slTrackMetaEvent = function (eventName, params, customEventId) {
            if (typeof window.fbq !== 'function' || !eventName) return;
            if (customEventId) {
                var key = eventName + ':' + String(customEventId);
                if (window.__slFiredMetaIds.has(key)) return;
                window.__slFiredMetaIds.add(key);
                window.fbq('track', eventName, params || {}, { eventID: String(customEventId) });
                return;
            }
            window.fbq('track', eventName, params || {});
        };
        fbq('init', @json($metaPixelId));
        fbq('track', 'PageView');
        @if($metaInitialEvent)
            window.slTrackMetaEvent(@json($metaInitialEvent['name']), @json($metaInitialEvent['params'] ?? []), @json($metaInitialEvent['event_id'] ?? null));
        @endif
        @if(is_array($metaFlashedEvent) && !empty($metaFlashedEvent['name']))
            window.slTrackMetaEvent(@json($metaFlashedEvent['name']), @json($metaFlashedEvent['params'] ?? []), @json($metaFlashedEvent['event_id'] ?? null));
        @endif
    </script>
    <noscript>
        <img height="1" width="1" style="display:none"
            src="https://www.facebook.com/tr?id={{ $metaPixelId }}&ev=PageView&noscript=1" alt="">
    </noscript>
    <!-- End Meta Pixel Code -->
@endif
