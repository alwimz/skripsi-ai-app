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
        max-height: 92vh;
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
        padding: 20px;
        overflow-y: auto;
        text-align: center;
    }
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
        .sl-modal-backdrop { padding: 12px; }
        .sl-upgrade-top { padding: 22px 18px 18px; }
        .sl-upgrade-body, .sl-checkout-body { padding: 16px; }
        .sl-checkout-actions { flex-direction: column; }
        .sl-checkout-btn { width: 100%; flex: none; }
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
            <div style="padding: 12px 14px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; margin-bottom: 14px;">
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
        let pollTimer = null;

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

            const modal = document.getElementById('sl-checkout-modal');
            const loadingEl = document.getElementById('sl-checkout-loading');
            const errEl = document.getElementById('sl-checkout-error');
            const payEl = document.getElementById('sl-checkout-step-pay');
            const planInfo = PLANS_MAP[planCode] || null;

            document.getElementById('sl-pay-plan-name').textContent = planInfo ? planInfo.name : ('Paket ' + planCode);
            document.getElementById('sl-pay-total').textContent = planInfo ? ('Rp ' + Number(planInfo.price).toLocaleString('id-ID')) : '-';
            document.getElementById('sl-pay-order-id').textContent = '';

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
                    if (data.order_id) {
                        const ordEl = document.getElementById('sl-pay-order-id');
                        if (ordEl) ordEl.textContent = 'Kode Pesanan: ' + data.order_id;
                    }
                    if (errEl) {
                        errEl.innerHTML = `<div style="margin-bottom:12px;color:#334155;">Untuk mengaktifkan paket ini, silakan lanjutkan konfirmasi pembayaran melalui WhatsApp Admin:</div><a href="${data.whatsapp_url}" target="_blank" rel="noopener" class="sl-checkout-btn primary" style="width:100%;">Aktifkan via WhatsApp Admin →</a>`;
                        errEl.style.display = 'block';
                    }
                    return;
                }

                if (!res.ok || data.status !== 'success' || !data.transaction) {
                    throw new Error(data.message || 'Gagal menyiapkan pesanan paket.');
                }

                const tx = data.transaction;
                activeOrderId = tx.order_id;
                document.getElementById('sl-pay-plan-name').textContent = tx.plan_name;
                document.getElementById('sl-pay-total').textContent = 'Rp ' + Number(tx.total_payment || tx.amount).toLocaleString('id-ID');
                document.getElementById('sl-pay-order-id').textContent = 'Order ID: ' + tx.order_id;
                document.getElementById('sl-pakasir-direct-link').href = tx.checkout_url || '#';

                if (tx.payment_number) {
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
