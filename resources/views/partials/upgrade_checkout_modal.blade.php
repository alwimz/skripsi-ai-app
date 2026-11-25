@php
    $modalPlans = $plans ?? app(\App\Services\BillingService::class)->getPlans(false);
    unset($modalPlans['free']);
@endphp

<style>
    .sl-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.62);
        backdrop-filter: blur(4px);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 16px;
        overflow-y: auto;
    }
    .sl-modal-backdrop.open {
        display: flex;
    }
    .sl-upgrade-card {
        width: 100%;
        max-width: 440px;
        background: #ffffff;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.35);
        animation: slPopIn 0.18s ease-out;
    }
    .sl-checkout-card {
        width: 100%;
        max-width: 440px;
        background: #ffffff;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.35);
        animation: slPopIn 0.18s ease-out;
        max-height: 90vh;
        max-height: calc(100dvh - 24px);
        display: flex;
        flex-direction: column;
    }
    @keyframes slPopIn {
        from { opacity: 0; transform: scale(0.95) translateY(8px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    .sl-upgrade-top {
        background: #0f172a;
        color: #ffffff;
        padding: 28px 24px 24px;
        text-align: center;
        position: relative;
    }
    .sl-upgrade-close {
        position: absolute;
        top: 14px;
        right: 16px;
        background: transparent;
        border: none;
        color: #94a3b8;
        font-size: 20px;
        cursor: pointer;
        line-height: 1;
        padding: 4px;
    }
    .sl-upgrade-close:hover { color: #ffffff; }
    .sl-upgrade-icon-circle {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.16);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 14px;
        color: #f8fafc;
    }
    .sl-upgrade-title {
        font-size: 16px;
        font-weight: 700;
        color: #f8fafc;
        line-height: 1.5;
        margin: 0;
    }
    .sl-upgrade-body {
        padding: 24px;
        background: #ffffff;
    }
    .sl-upgrade-perks {
        list-style: none;
        padding: 0;
        margin: 0 0 22px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .sl-upgrade-perks li {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 13.5px;
        color: #334155;
        line-height: 1.45;
    }
    .sl-sparkle {
        color: #059669;
        font-weight: 800;
        font-size: 15px;
        line-height: 1.2;
        flex-shrink: 0;
    }
    .sl-upgrade-cta {
        display: flex;
        width: 100%;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 13px 20px;
        border-radius: 999px;
        border: none;
        background: #2563eb;
        color: #ffffff;
        font-size: 14.5px;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
        transition: background 0.15s;
        font-family: inherit;
    }
    .sl-upgrade-cta:hover { background: #1d4ed8; }
    .sl-upgrade-later {
        display: block;
        width: 100%;
        background: none;
        border: none;
        margin-top: 12px;
        color: #94a3b8;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        text-align: center;
        font-family: inherit;
    }
    .sl-upgrade-later:hover { color: #64748b; }
    .sl-checkout-body {
        padding: 18px 20px 20px;
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
        text-align: center;
    }
    .sl-invoice-tabs {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 6px;
        margin-bottom: 12px;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 12px;
    }
    .sl-invoice-tab {
        border: none;
        background: transparent;
        color: #475569;
        font-size: 12.5px;
        font-weight: 700;
        padding: 8px 6px;
        border-radius: 9px;
        cursor: pointer;
        font-family: inherit;
        transition: background 0.15s, color 0.15s, box-shadow 0.15s;
    }
    .sl-invoice-tab.active {
        background: #ffffff;
        color: #1d4ed8;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
    }
    .sl-invoice-panel {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 12px;
        text-align: center;
    }
    .sl-invoice-summary {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 10px 12px;
        margin-bottom: 12px;
        text-align: left;
        font-size: 12px;
    }
    .sl-invoice-row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 10px;
        padding: 4px 0;
        border-bottom: 1px dashed #e2e8f0;
    }
    .sl-invoice-row:last-child {
        border-bottom: none;
    }
    .sl-invoice-row span {
        color: #64748b;
        flex-shrink: 0;
    }
    .sl-invoice-row strong {
        color: #0f172a;
        text-align: right;
        word-break: break-word;
    }
    .sl-qris-static-img {
        display: block;
        width: 100%;
        max-width: 245px;
        height: auto;
        margin: 0 auto 10px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
    }
    .sl-copy-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 12px;
        margin-top: 8px;
        text-align: left;
    }
    .sl-copy-btn {
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 12px;
        font-weight: 700;
        padding: 7px 11px;
        border-radius: 8px;
        cursor: pointer;
        flex-shrink: 0;
        font-family: inherit;
    }
    .sl-copy-btn:hover { background: #dbeafe; }
    .sl-checkout-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
        flex-wrap: wrap;
        width: 100%;
    }
    .sl-checkout-btn {
        flex: 1 1 160px;
        min-height: 44px;
        padding: 11px 14px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        cursor: pointer;
        box-sizing: border-box;
        font-family: inherit;
        transition: filter 0.15s, background 0.15s;
    }
    .sl-checkout-btn.primary {
        border: none;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
    }
    .sl-checkout-btn.primary:hover { filter: brightness(1.07); color: #ffffff; }
    .sl-checkout-btn.secondary {
        border: 1.5px solid #bfdbfe;
        background: #eff6ff;
        color: #1d4ed8;
    }
    .sl-checkout-btn.secondary:hover { background: #dbeafe; }
    .sl-checkout-error-box {
        display: none;
        padding: 16px;
        border-radius: 14px;
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        color: #334155;
        font-size: 13px;
        text-align: center;
        line-height: 1.55;
    }
    @media (max-width: 520px) {
        .sl-modal-backdrop {
            padding: 10px;
            align-items: center;
        }
        .sl-checkout-card {
            max-height: calc(100dvh - 20px);
            border-radius: 16px;
        }
        .sl-upgrade-top { padding: 20px 16px 16px; }
        .sl-upgrade-body { padding: 16px; }
        .sl-checkout-body { padding: 12px 14px 16px; }
        .sl-invoice-summary { padding: 8px 10px; margin-bottom: 9px; font-size: 11.5px; }
        .sl-invoice-row { padding: 3px 0; gap: 8px; }
        .sl-invoice-tabs { margin-bottom: 9px; padding: 3px; }
        .sl-invoice-tab { font-size: 12px; padding: 7px 4px; }
        .sl-invoice-panel { padding: 10px; margin-bottom: 9px; }
        .sl-qris-static-img { max-width: 190px; margin-bottom: 8px; }
        .sl-checkout-actions { flex-direction: column; }
        .sl-checkout-btn { width: 100%; flex: none; min-height: 40px; font-size: 12.5px; padding: 9px 12px; }
        #sl-qris-canvas { width: 190px !important; height: 190px !important; }
    }
</style>

<!-- MODAL 1: PAYWALL / UPGRADE PROMPT -->
<div id="sl-upgrade-modal" class="sl-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="sl-upgrade-msg">
    <div class="sl-upgrade-card">
        <div class="sl-upgrade-top">
            <button type="button" class="sl-upgrade-close" onclick="closeUpgradeModal()" aria-label="Tutup">&times;</button>
            <div class="sl-upgrade-icon-circle">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />
                </svg>
            </div>
            <p class="sl-upgrade-title" id="sl-upgrade-msg">
                Buka generasi Bab II–V full &amp; download Word/PDF dengan Paket Kuota mulai Rp15.000.
            </p>
        </div>
        <div class="sl-upgrade-body">
            <ul class="sl-upgrade-perks">
                <li>
                    <span class="sl-sparkle">✦</span>
                    <span>Pilih paket kuota — <strong>tanpa kredit</strong>, mulai Rp15rb</span>
                </li>
                <li>
                    <span class="sl-sparkle">✦</span>
                    <span>Aktivasi paket cepat &amp; dibantu langsung oleh <strong>Admin</strong></span>
                </li>
                <li>
                    <span class="sl-sparkle">✦</span>
                    <span>Pekerjaanmu tersimpan, tidak perlu mengulang dari awal</span>
                </li>
            </ul>

            <button type="button" class="sl-upgrade-cta" onclick="proceedFromUpgradeModal()">
                <span>Pilih Paket Sekarang →</span>
            </button>
            <button type="button" class="sl-upgrade-later" onclick="closeUpgradeModal()">Nanti saja</button>
        </div>
    </div>
</div>

<!-- MODAL 2: KONFIRMASI AKTIVASI PAKET -->
<div id="sl-checkout-modal" class="sl-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="sl-checkout-title">
    <div class="sl-checkout-card">
        <div style="padding: 18px 22px; background: #0f172a; color: #ffffff; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
            <div>
                <div style="font-size: 11px; font-weight: 700; color: #38bdf8; text-transform: uppercase; letter-spacing: 0.05em;">Konfirmasi Pembelian</div>
                <div id="sl-checkout-title" style="font-size: 16px; font-weight: 800; margin-top: 2px;">Aktivasi Paket</div>
            </div>
            <button type="button" onclick="closeCheckoutModal()" style="background: none; border: none; color: #94a3b8; font-size: 22px; cursor: pointer; line-height: 1;">&times;</button>
        </div>

        <div class="sl-checkout-body">
            <div id="sl-checkout-top-summary" style="padding: 12px 14px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; margin-bottom: 14px;">
                <div id="sl-pay-plan-name" style="font-size: 13.5px; font-weight: 700; color: #1e40af;">Menyiapkan Paket...</div>
                <div id="sl-pay-total" style="font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 2px;">-</div>
                <div id="sl-pay-order-id" style="font-size: 11.5px; color: #475569; font-family: monospace; margin-top: 2px;"></div>
            </div>

            <div id="sl-checkout-loading" style="padding: 22px 10px;">
                <div style="font-size: 13.5px; font-weight: 600; color: #334155;">⏳ Menyiapkan rincian paket...</div>
            </div>

            <div id="sl-checkout-error" class="sl-checkout-error-box"></div>

            <div id="sl-checkout-step-pay" style="display:none;">
                <div id="sl-qris-box" style="display:inline-block; padding:14px; background:#ffffff; border:1.5px solid #e2e8f0; border-radius:14px; margin-bottom:10px;">
                    <canvas id="sl-qris-canvas" width="220" height="220" style="display:block; margin:0 auto;"></canvas>
                </div>

                <div id="sl-pay-status-badge" style="font-size:12.5px; font-weight:600; color:#0284c7; margin-bottom:14px;">
                    ⏳ Menunggu konfirmasi pembayaran...
                </div>

                <div class="sl-checkout-actions">
                    <button type="button" onclick="pollPakasirStatusOnce()" class="sl-checkout-btn secondary">
                        🔄 Cek Status Sekarang
                    </button>
                    <a id="sl-pakasir-direct-link" href="#" target="_blank" rel="noopener" class="sl-checkout-btn primary">
                        Buka Halaman Pembayaran ↗
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const PLANS_MAP = @json($modalPlans);
        let activeOrderId = null;
        let activePlanMeta = null;
        let pollTimer = null;

        function trackMetaCheckoutEvent(eventName, planInfo, orderId) {
            if (typeof window.slTrackMetaEvent !== 'function') return;
            const p = planInfo || activePlanMeta || {};
            const code = String(p.code || p.plan_code || '');
            const name = String(p.name || p.plan_name || (code ? ('Paket ' + code) : 'Paket SkripsiLab AI'));
            const val = Number(p.amount ?? p.price ?? 0);
            const prefix = eventName === 'Purchase' ? 'pur_' : 'ic_';
            window.slTrackMetaEvent(eventName, {
                content_name: name,
                content_ids: code ? [code] : [],
                content_type: 'product',
                value: isFinite(val) ? val : 0,
                currency: 'IDR',
                num_items: 1
            }, orderId ? (prefix + String(orderId)) : null);
        }

        window.openUpgradeModal = function (message) {
            const modal = document.getElementById('sl-upgrade-modal');
            const msgEl = document.getElementById('sl-upgrade-msg');
            if (msgEl && message) {
                msgEl.textContent = message;
            }
            if (modal) modal.classList.add('open');
        };

        window.closeUpgradeModal = function () {
            const modal = document.getElementById('sl-upgrade-modal');
            if (modal) modal.classList.remove('open');
        };

        window.proceedFromUpgradeModal = function () {
            closeUpgradeModal();
            if (document.getElementById('pane-pricing') && typeof openTab === 'function') {
                openTab('pricing', 'Paket');
            } else {
                window.location.href = "{{ route('app.create', ['tab' => 'pricing']) }}";
            }
        };

        let activeManualInvoice = null;

        window.slSelectInvoiceMethod = function (methodId) {
            ['qris', 'dana', 'seabank'].forEach((id) => {
                const panel = document.getElementById('sl-inv-panel-' + id);
                if (panel) panel.style.display = id === methodId ? 'block' : 'none';
            });
            document.querySelectorAll('.sl-invoice-tab').forEach((btn) => {
                const active = btn.getAttribute('data-sl-tab') === methodId;
                btn.classList.toggle('active', active);
                btn.setAttribute('aria-selected', active ? 'true' : 'false');
            });

            if (activeManualInvoice) {
                const methods = Array.isArray(activeManualInvoice.methods) ? activeManualInvoice.methods : [];
                const picked = methods.find((m) => m.id === methodId) || { title: methodId.toUpperCase(), wa_label: methodId.toUpperCase() };
                const methodLabelEl = document.getElementById('sl-inv-selected-method');
                if (methodLabelEl) {
                    methodLabelEl.textContent = picked.title || picked.label || methodId.toUpperCase();
                }
                const waBtn = document.getElementById('sl-wa-confirm-btn');
                if (waBtn) {
                    const nominalStr = 'Rp ' + Number(activeManualInvoice.amount || 0).toLocaleString('id-ID');
                    const lines = [
                        '*KONFIRMASI PEMBAYARAN SKRIPSILAB AI*',
                        '',
                        `• *No. Invoice:* ${activeManualInvoice.order_id || '-'}`,
                        `• *Akun:* ${activeManualInvoice.account_email || '-'}`,
                        `• *Paket:* ${activeManualInvoice.plan_name || '-'}`,
                        `• *Metode Bayar:* ${picked.wa_label || picked.title || methodId.toUpperCase()}`,
                        `• *Total Bayar:* ${nominalStr}`,
                        '',
                        'Berikut saya lampirkan bukti pembayaran. Mohon bantu aktivasi paket saya. Terima kasih.'
                    ];
                    waBtn.href = 'https://wa.me/6282195036971?text=' + encodeURIComponent(lines.join('\n'));
                }
            }
        };

        window.slCopyPaymentText = async function (text, btnEl) {
            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(text);
                } else {
                    const ta = document.createElement('textarea');
                    ta.value = text;
                    ta.style.position = 'fixed';
                    ta.style.opacity = '0';
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                }
                if (btnEl) {
                    const orig = btnEl.textContent;
                    btnEl.textContent = '✓ Tersalin';
                    setTimeout(() => { btnEl.textContent = orig; }, 1600);
                }
            } catch (e) { }
        };

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeCheckoutModal();
                closeUpgradeModal();
            }
        });

        window.closeCheckoutModal = function () {
            if (pollTimer) {
                clearInterval(pollTimer);
                pollTimer = null;
            }
            const modal = document.getElementById('sl-checkout-modal');
            if (modal) modal.classList.remove('open');
        };

        window.startPakasirCheckout = async function (planCode) {
            closeUpgradeModal();
            if (pollTimer) {
                clearInterval(pollTimer);
                pollTimer = null;
            }
            activeOrderId = null;
            activePlanMeta = PLANS_MAP[planCode] || { code: planCode, name: 'Paket ' + planCode, price: 0 };

            const modal = document.getElementById('sl-checkout-modal');
            const loadingEl = document.getElementById('sl-checkout-loading');
            const errEl = document.getElementById('sl-checkout-error');
            const payEl = document.getElementById('sl-checkout-step-pay');
            const planInfo = PLANS_MAP[planCode] || null;

            document.getElementById('sl-pay-plan-name').textContent = planInfo ? planInfo.name : ('Paket ' + planCode);
            document.getElementById('sl-pay-total').textContent = planInfo ? ('Rp ' + Number(planInfo.price).toLocaleString('id-ID')) : '-';
            document.getElementById('sl-pay-order-id').textContent = '';

            const topSummaryEl = document.getElementById('sl-checkout-top-summary');
            if (topSummaryEl) topSummaryEl.style.display = 'block';
            if (errEl) errEl.style.display = 'none';
            if (payEl) payEl.style.display = 'none';
            if (loadingEl) loadingEl.style.display = 'block';
            if (modal) modal.classList.add('open');

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            try {
                const res = await fetch("{{ route('payment.create') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({ plan_code: planCode, payment_method: 'qris' })
                });

                if (res.status === 401) {
                    window.location.href = "{{ route('login') }}";
                    return;
                }

                const data = await res.json();
                if (loadingEl) loadingEl.style.display = 'none';

                if (data.status === 'pakasir_unconfigured') {
                    if (topSummaryEl) topSummaryEl.style.display = 'none';
                    const inv = data.invoice || {};
                    activeManualInvoice = {
                        order_id: inv.order_id || data.order_id || '',
                        account_email: inv.account_email || '',
                        plan_name: inv.plan_name || (data.plan && data.plan.name) || (planInfo && planInfo.name) || '',
                        amount: inv.amount ?? (data.plan && data.plan.price) ?? (planInfo && planInfo.price) ?? 0,
                        created_at_wib: inv.created_at_wib || '',
                        methods: Array.isArray(inv.methods) ? inv.methods : []
                    };
                    const ordEl = document.getElementById('sl-pay-order-id');
                    if (ordEl && data.order_id) {
                        ordEl.textContent = 'Invoice: ' + data.order_id + (inv.created_at_wib ? (' • ' + inv.created_at_wib) : '');
                    }
                    trackMetaCheckoutEvent('InitiateCheckout', data.plan || activePlanMeta, data.order_id || null);
                    if (errEl) {
                        const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
                        const qrisUrl = esc(inv.qris_image_url || "{{ asset('images/qris-payment.jpg') }}");
                        const methods = activeManualInvoice.methods;
                        const danaObj = methods.find((m) => m.id === 'dana') || { account: '082195036971', holder: 'Muh Alwi Syahrir' };
                        const seaObj = methods.find((m) => m.id === 'seabank') || { account: '901889425034', holder: 'Muh Alwi Syahrir' };
                        const totalFmt = 'Rp ' + Number(activeManualInvoice.amount || 0).toLocaleString('id-ID');

                        errEl.style.background = '#ffffff';
                        errEl.style.border = 'none';
                        errEl.style.padding = '0';
                        errEl.innerHTML = `
                            <div class="sl-invoice-summary">
                                <div class="sl-invoice-row"><span>No. Invoice</span><strong>${esc(activeManualInvoice.order_id)}</strong></div>
                                <div class="sl-invoice-row"><span>Akun</span><strong>${esc(activeManualInvoice.account_email || '-')}</strong></div>
                                <div class="sl-invoice-row"><span>Paket</span><strong>${esc(activeManualInvoice.plan_name)}</strong></div>
                                <div class="sl-invoice-row"><span>Metode</span><strong id="sl-inv-selected-method">QRIS (Semua E-Wallet &amp; M-Banking)</strong></div>
                                <div class="sl-invoice-row"><span>Total Bayar</span><strong style="color:#1d4ed8;">${esc(totalFmt)}</strong></div>
                            </div>
                            <div style="font-size:12px;font-weight:600;color:#475569;margin-bottom:8px;">
                                Pilih metode pembayaran di bawah ini:
                            </div>
                            <div class="sl-invoice-tabs" role="tablist" aria-label="Metode Pembayaran">
                                <button type="button" class="sl-invoice-tab active" data-sl-tab="qris" role="tab" aria-selected="true" onclick="window.slSelectInvoiceMethod('qris')">QRIS</button>
                                <button type="button" class="sl-invoice-tab" data-sl-tab="dana" role="tab" aria-selected="false" onclick="window.slSelectInvoiceMethod('dana')">DANA</button>
                                <button type="button" class="sl-invoice-tab" data-sl-tab="seabank" role="tab" aria-selected="false" onclick="window.slSelectInvoiceMethod('seabank')">SeaBank</button>
                            </div>
                            <div id="sl-inv-panel-qris" class="sl-invoice-panel">
                                <a href="${qrisUrl}" target="_blank" rel="noopener" title="Klik untuk perbesar QRIS">
                                    <img src="${qrisUrl}" alt="QRIS Pembayaran SkripsiLab AI" class="sl-qris-static-img">
                                </a>
                                <div style="font-size:11.5px;color:#475569;margin-bottom:8px;">
                                    Scan dengan QRIS (DANA, GoPay, OVO, ShopeePay, atau M-Banking apa saja).
                                </div>
                                <a href="${qrisUrl}" download="QRIS-SkripsiLab-AI.jpg" class="sl-checkout-btn secondary" style="width:100%;min-height:38px;font-size:12px;">
                                    ⬇️ Unduh Gambar QRIS
                                </a>
                            </div>
                            <div id="sl-inv-panel-dana" class="sl-invoice-panel" style="display:none;">
                                <div style="font-size:12px;font-weight:700;color:#0f172a;">Transfer / Kirim ke DANA</div>
                                <div class="sl-copy-row">
                                    <div>
                                        <div style="font-size:15px;font-weight:800;color:#0f172a;font-family:monospace;">${esc(danaObj.account)}</div>
                                        <div style="font-size:11.5px;color:#64748b;">a.n. ${esc(danaObj.holder)}</div>
                                    </div>
                                    <button type="button" class="sl-copy-btn" onclick="window.slCopyPaymentText('${esc(danaObj.account)}', this)">Salin</button>
                                </div>
                            </div>
                            <div id="sl-inv-panel-seabank" class="sl-invoice-panel" style="display:none;">
                                <div style="font-size:12px;font-weight:700;color:#0f172a;">Transfer ke Bank SeaBank</div>
                                <div class="sl-copy-row">
                                    <div>
                                        <div style="font-size:15px;font-weight:800;color:#0f172a;font-family:monospace;">${esc(seaObj.account)}</div>
                                        <div style="font-size:11.5px;color:#64748b;">a.n. ${esc(seaObj.holder)}</div>
                                    </div>
                                    <button type="button" class="sl-copy-btn" onclick="window.slCopyPaymentText('${esc(seaObj.account)}', this)">Salin</button>
                                </div>
                            </div>
                            <div style="font-size:11.5px;color:#64748b;margin-bottom:10px;line-height:1.45;">
                                Setelah transfer, kirim screenshot bukti pembayaran agar paket Anda langsung diaktifkan Admin:
                            </div>
                            <a id="sl-wa-confirm-btn" href="${esc(data.whatsapp_url)}" target="_blank" rel="noopener" class="sl-checkout-btn primary" style="width:100%;">
                                Kirim Bukti &amp; Aktifkan via WhatsApp →
                            </a>
                        `;
                        window.slSelectInvoiceMethod('qris');
                        errEl.style.display = 'block';
                    }
                    return;
                }

                if (!res.ok || data.status !== 'success' || !data.transaction) {
                    throw new Error(data.message || 'Gagal menyiapkan pesanan paket.');
                }

                const tx = data.transaction;
                activeOrderId = tx.order_id;
                activePlanMeta = { code: tx.plan_code, name: tx.plan_name, amount: tx.amount };
                trackMetaCheckoutEvent('InitiateCheckout', activePlanMeta, tx.order_id);
                document.getElementById('sl-pay-plan-name').textContent = tx.plan_name;
                document.getElementById('sl-pay-total').textContent = 'Rp ' + Number(tx.total_payment || tx.amount).toLocaleString('id-ID');
                document.getElementById('sl-pay-order-id').textContent = 'Order ID: ' + tx.order_id;

                const qrisBoxEl = document.getElementById('sl-qris-box');
                const statusBadgeEl = document.getElementById('sl-pay-status-badge');
                const directLinkEl = document.getElementById('sl-pakasir-direct-link');
                if (directLinkEl) {
                    directLinkEl.href = tx.checkout_url || '#';
                    directLinkEl.textContent = tx.gateway === 'mayar'
                        ? 'Bayar Sekarang di Mayar ↗'
                        : 'Buka Halaman Pembayaran ↗';
                }

                if (tx.payment_number) {
                    if (qrisBoxEl) qrisBoxEl.style.display = 'inline-block';
                    if (statusBadgeEl) {
                        statusBadgeEl.style.color = '#0284c7';
                        statusBadgeEl.textContent = '⏳ Menunggu konfirmasi pembayaran...';
                    }
                    if (!window.QRCode) {
                        await new Promise((resolve) => {
                            const s = document.createElement('script');
                            s.src = 'https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js';
                            s.onload = resolve;
                            s.onerror = resolve;
                            document.head.appendChild(s);
                        });
                    }
                    if (window.QRCode) {
                        const canvas = document.getElementById('sl-qris-canvas');
                        window.QRCode.toCanvas(canvas, tx.payment_number, { width: 220, margin: 1 });
                    }
                } else {
                    if (qrisBoxEl) qrisBoxEl.style.display = 'none';
                    if (statusBadgeEl) {
                        statusBadgeEl.style.color = '#0284c7';
                        statusBadgeEl.textContent = tx.gateway === 'mayar'
                            ? 'Klik tombol di bawah untuk bayar via QRIS, E-Wallet, atau Virtual Account di Mayar.'
                            : '⏳ Menunggu konfirmasi pembayaran...';
                    }
                }

                if (payEl) payEl.style.display = 'block';
                pollTimer = setInterval(pollPakasirStatusOnce, 4500);
            } catch (e) {
                if (loadingEl) loadingEl.style.display = 'none';
                if (errEl) {
                    errEl.textContent = e.message || 'Gagal memproses permintaan aktivasi.';
                    errEl.style.display = 'block';
                }
            }
        };

        window.pollPakasirStatusOnce = async function () {
            if (!activeOrderId) return;
            try {
                const res = await fetch(`/payment/status/${encodeURIComponent(activeOrderId)}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                const badge = document.getElementById('sl-pay-status-badge');
                if (data.status === 'completed') {
                    if (pollTimer) clearInterval(pollTimer);
                    trackMetaCheckoutEvent('Purchase', data.plan_code ? {
                        code: data.plan_code,
                        name: data.plan_name,
                        amount: data.amount
                    } : activePlanMeta, data.order_id || activeOrderId);
                    if (badge) {
                        badge.style.color = '#16a34a';
                        badge.textContent = '✅ Pembayaran berhasil! Paket langsung aktif. Memuat ulang...';
                    }
                    setTimeout(() => window.location.reload(), 1200);
                }
            } catch (e) { }
        };

        if (!window.__slFetchPatched && typeof window.fetch === 'function') {
            window.__slFetchPatched = true;
            const origFetch = window.fetch.bind(window);
            window.fetch = async function (...args) {
                const res = await origFetch(...args);
                if (res && res.status === 402) {
                    try {
                        const clone = res.clone();
                        const data = await clone.json();
                        if (data && data.status === 'upgrade_required') {
                            openUpgradeModal(data.message);
                        }
                    } catch (e) {
                        openUpgradeModal('Fitur ini membutuhkan Paket Starter atau Unlimited.');
                    }
                }
                return res;
            };
        }

        @if(session('upgrade_modal'))
            document.addEventListener('DOMContentLoaded', function () {
                openUpgradeModal(@json(session('upgrade_modal')));
            });
        @endif
    })();
</script>
