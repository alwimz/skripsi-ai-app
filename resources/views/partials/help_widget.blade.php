<style>
    .sl-help-launcher {
        position: fixed;
        inset-block-end: 20px;
        inset-inline-end: 20px;
        z-index: 95;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }

    @keyframes slHelpPillCycle {
        0%, 8% {
            opacity: 0;
            transform: translateX(8px) scale(0.96);
            pointer-events: none;
        }
        14%, 48% {
            opacity: 1;
            transform: translateX(0) scale(1);
            pointer-events: auto;
        }
        55%, 100% {
            opacity: 0;
            transform: translateX(8px) scale(0.96);
            pointer-events: none;
        }
    }

    .sl-help-pill {
        background: #0f172a;
        color: #ffffff;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 13px;
        font-weight: 600;
        padding: 9px 14px;
        border-radius: 999px;
        box-shadow: 0 10px 25px -6px rgba(15, 23, 42, 0.35);
        cursor: pointer;
        border: 1px solid rgba(255, 255, 255, 0.12);
        line-height: 1.2;
        transition: background 0.15s ease;
        animation: slHelpPillCycle 8s ease-in-out infinite;
    }

    .sl-help-pill:hover {
        background: #1e293b;
    }

    .sl-help-launcher:has([aria-expanded="true"]) .sl-help-pill {
        animation: none;
        opacity: 0;
        pointer-events: none;
    }

    @media (prefers-reduced-motion: reduce) {
        .sl-help-pill {
            animation: none;
            display: none;
        }
    }

    .sl-help-fab {
        width: 48px;
        height: 48px;
        border-radius: 999px;
        border: none;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 12px 28px -6px rgba(37, 99, 235, 0.5);
        transition: transform 0.15s ease, filter 0.15s ease;
        flex-shrink: 0;
    }

    .sl-help-fab:hover {
        filter: brightness(1.06);
        transform: translateY(-1px);
    }

    .sl-help-fab .sl-help-icon-close {
        display: none;
    }

    .sl-help-fab[aria-expanded="true"] .sl-help-icon-chat {
        display: none;
    }

    .sl-help-fab[aria-expanded="true"] .sl-help-icon-close {
        display: block;
    }

    #sl-help-popover[popover] {
        margin: 0;
        inset: auto;
        inset-block-end: 80px;
        inset-inline-end: 20px;
        width: min(calc(100vw - 32px), 340px);
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 20px;
        box-shadow: 0 24px 50px -12px rgba(15, 23, 42, 0.22);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        color: #0f172a;
    }

    #sl-help-popover[popover]:is(:popover-open, .\:popover-open) {
        display: block;
    }

    @supports not selector(:popover-open) {
        #sl-help-popover[popover]:not(.\:popover-open) {
            display: none;
        }
    }

    .sl-help-pop-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 8px;
    }

    .sl-help-pop-title {
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }

    .sl-help-pop-close {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        border: 1px solid transparent;
        background: transparent;
        color: #64748b;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.15s ease, color 0.15s ease;
    }

    .sl-help-pop-close:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    .sl-help-pop-desc {
        font-size: 13px;
        color: #475569;
        line-height: 1.5;
        margin: 0 0 14px;
    }

    .sl-help-pop-list {
        display: flex;
        flex-direction: column;
        gap: 9px;
    }

    .sl-help-pop-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 11px 13px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #ffffff;
        text-decoration: none;
        color: inherit;
        transition: border-color 0.15s ease, background 0.15s ease, transform 0.15s ease;
    }

    .sl-help-pop-item:hover {
        border-color: #93c5fd;
        background: #f8fafc;
        transform: translateY(-1px);
    }

    .sl-help-pop-ico {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .sl-help-pop-ico.wa {
        background: #ecfdf5;
        color: #059669;
    }

    .sl-help-pop-ico.tel {
        background: #eff6ff;
        color: #2563eb;
    }

    .sl-help-pop-ico.email {
        background: #eff6ff;
        color: #2563eb;
    }

    .sl-help-pop-label {
        font-size: 13.5px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.25;
    }

    .sl-help-pop-sub {
        font-size: 12px;
        color: #64748b;
        margin-top: 2px;
        line-height: 1.3;
    }

    .sl-help-pop-foot {
        margin-top: 14px;
        padding-top: 12px;
        border-top: 1px solid #f1f5f9;
        font-size: 11.5px;
        color: #64748b;
        text-align: center;
    }

    @media (max-width: 520px) {
        .sl-help-launcher {
            inset-block-end: 14px;
            inset-inline-end: 14px;
        }

        #sl-help-popover[popover] {
            inset-block-end: 74px;
            inset-inline-end: 14px;
        }
    }
</style>

<div class="sl-help-launcher">
    <button type="button" class="sl-help-pill" commandfor="sl-help-popover" command="toggle-popover">
        Butuh Bantuan?
    </button>
    <button type="button" id="sl-help-trigger" class="sl-help-fab" commandfor="sl-help-popover" command="toggle-popover"
        aria-label="Buka menu bantuan SkripsiLab AI" aria-expanded="false">
        <svg class="sl-help-icon-chat" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        <svg class="sl-help-icon-close" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
    </button>
</div>

<div id="sl-help-popover" popover="auto" role="dialog" aria-labelledby="sl-help-pop-title">
    <div class="sl-help-pop-head">
        <h3 id="sl-help-pop-title" class="sl-help-pop-title">Butuh Bantuan?</h3>
        <button type="button" class="sl-help-pop-close" commandfor="sl-help-popover" command="hide-popover"
            aria-label="Tutup panel bantuan">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>
    <p class="sl-help-pop-desc">Tim SkripsiLab AI siap membantu kalau kamu punya pertanyaan seputar penggunaan atau paket.</p>

    <div class="sl-help-pop-list">
        <a href="tel:+6282195036971" class="sl-help-pop-item">
            <span class="sl-help-pop-ico tel" aria-hidden="true">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                </svg>
            </span>
            <div>
                <div class="sl-help-pop-label">Telepon</div>
                <div class="sl-help-pop-sub">+62 821-9503-6971</div>
            </div>
        </a>

        <a href="https://wa.me/6282195036971?text=Halo%20Admin%20SkripsiLab%20AI%2C%20saya%20ingin%20bertanya%20tentang%20SkripsiLab%20AI."
            target="_blank" rel="noopener noreferrer" class="sl-help-pop-item">
            <span class="sl-help-pop-ico wa" aria-hidden="true">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                </svg>
            </span>
            <div>
                <div class="sl-help-pop-label">WhatsApp</div>
                <div class="sl-help-pop-sub">Chat langsung dengan tim kami</div>
            </div>
        </a>

        <a href="mailto:muhalwisyahrir@gmail.com" class="sl-help-pop-item">
            <span class="sl-help-pop-ico email" aria-hidden="true">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                </svg>
            </span>
            <div>
                <div class="sl-help-pop-label">Email</div>
                <div class="sl-help-pop-sub">muhalwisyahrir@gmail.com</div>
            </div>
        </a>
    </div>

    <div class="sl-help-pop-foot">Respon cepat setiap hari &bull; 09.00–21.00 WITA</div>
</div>

<script type="module">
    if (!Object.hasOwn(HTMLElement.prototype, 'popover')) {
        await import('https://unpkg.com/@oddbird/popover-polyfill@latest/dist/popover.min.js');
    }
    if (!('commandForElement' in HTMLButtonElement.prototype)) {
        await import('https://esm.run/invokers-polyfill');
    }

    const helpPopover = document.getElementById('sl-help-popover');
    const helpTrigger = document.getElementById('sl-help-trigger');
    const closeBtn = helpPopover ? helpPopover.querySelector('.sl-help-pop-close') : null;

    if (helpPopover && helpTrigger) {
        helpPopover.addEventListener('toggle', (event) => {
            const isOpen = event.newState === 'open' || helpPopover.matches(':popover-open, .\\:popover-open');
            helpTrigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    if (closeBtn && helpTrigger) {
        closeBtn.addEventListener('click', () => {
            requestAnimationFrame(() => helpTrigger.focus());
        });
    }
</script>
