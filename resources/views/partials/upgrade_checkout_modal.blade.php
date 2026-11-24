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
        max-width: 560px;
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
    .sl-plan-chip-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 8px;
        margin-bottom: 14px;
    }
    .sl-plan-chip {
        border: 1.5px solid #e2e8f0;
        border-radius: 11px;
        padding: 10px;
        cursor: pointer;
        text-align: left;
        background: #ffffff;
        transition: all 0.15s;
        font-family: inherit;
    }
    .sl-plan-chip.selected {
        border-color: #2563eb;
        background: #eff6ff;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.16);
    }
</style>

<!-- MODAL 1: PAYWALL / UPGRADE PROMPT (Gaya Dark Header + Sparkle List) -->
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
                    <span>Pilih paket kuota — <strong>tanpa kredit</strong>, langsung aktif (mulai Rp15rb / 1 naskah)</span>
                </li>
                <li>
                    <span class="sl-sparkle">✦</span>
                    <span>Pembayaran <strong>QRIS Pakasir</strong> — paket &amp; download terbuka otomatis</span>
                </li>
                <li>
                    <span class="sl-sparkle">✦</span>
                    <span>Pekerjaanmu sudah tersimpan aman, tidak perlu mengulang dari awal</span>
                </li>
            </ul>

            <button type="button" class="sl-upgrade-cta" onclick="proceedFromUpgradeModal()">
                <span>Lanjut Pilih Paket &amp; Bayar QRIS →</span>
            </button>
            <button type="button" class="sl-upgrade-later" onclick="closeUpgradeModal()">Nanti saja</button>
        </div>
    </div>
</div>

<!-- MODAL 2: PAKASIR QRIS CHECKOUT & PILIH PAKET CEPAT -->
<div id="sl-checkout-modal" class="sl-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="sl-checkout-title">
    <div class="sl-checkout-card">
        <div style="padding: 18px 22px; background: #0f172a; color: #ffffff; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;">
            <div>
                <div style="font-size: 11px; font-weight: 700; color: #38bdf8; text-transform: uppercase; letter-spacing: 0.05em;">Pembayaran Resmi · Pakasir QRIS</div>
                <div id="sl-checkout-title" style="font-size: 16px; font-weight: 800; margin-top: 2px;">Aktivasi Paket SkripsiLab AI</div>
            </div>
            <button type="button" onclick="closeCheckoutModal()" style="background: none; border: none; color: #94a3b8; font-size: 22px; cursor: pointer; line-height: 1;">&times;</button>
        </div>

        <div style="padding: 20px 22px; overflow-y: auto;">
            <div id="sl-checkout-step-select">
                <div style="font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 8px;">1. Pilih Paket:</div>
                <div class="sl-plan-chip-list">
                    @foreach($modalPlans as $mp)
                        <button type="button" class="sl-plan-chip" data-plan-code="{{ $mp['code'] }}"
                            onclick="selectCheckoutPlan('{{ $mp['code'] }}')">
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:4px;">
                                <span style="font-size:11px; font-weight:800; color:{{ $mp['badge_color'] ?? '#2563eb' }};">{{ $mp['badge'] }}</span>
                                <span style="font-size:10px; color:#dc2626; font-weight:700;">{{ $mp['discount_label'] }}</span>
                            </div>
                            <div style="font-size:12.5px; font-weight:700; color:#0f172a; margin-top:3px;">{{ $mp['name'] }}</div>
                            <div style="margin-top:4px;">
                                <span style="font-size:10.5px; color:#94a3b8; text-decoration:line-through;">Rp{{ number_format($mp['original_price'], 0, ',', '.') }}</span>
                                <span style="font-size:13.5px; font-weight:800; color:#0f172a; margin-left:4px;">Rp{{ number_format($mp['price'], 0, ',', '.') }}</span>
                            </div>
                        </button>
                    @endforeach
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display:block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">2. Metode Pembayaran:</label>
                    <select id="sl-payment-method" style="width:100%; padding:10px 12px; border:1px solid #cbd5e1; border-radius:10px; font-size:13.5px; font-family:inherit; background:#fff;">
                        <option value="qris" selected>QRIS Instan (Dana, GoPay, OVO, ShopeePay, BCA/BRI/Mandiri Mobile)</option>
                        <option value="bni_va">Virtual Account BNI</option>
                        <option value="bri_va">Virtual Account BRI</option>
                        <option value="cimb_niaga_va">Virtual Account CIMB Niaga</option>
                        <option value="permata_va">Virtual Account Permata</option>
                    </select>
                </div>

                <div id="sl-checkout-error" style="display:none; padding:10px 12px; border-radius:10px; background:#fef2f2; border:1px solid #fecaca; color:#991b1b; font-size:12.5px; margin-bottom:12px;"></div>

                <button type="button" id="sl-btn-create-invoice" onclick="submitPakasirInvoice()"
                    style="width:100%; padding:12px 16px; border:none; border-radius:12px; background:#2563eb; color:#ffffff; font-size:14px; font-weight:700; cursor:pointer; font-family:inherit;">
                    Buat Pembayaran QRIS / VA Sekarang →
                </button>
            </div>

            <div id="sl-checkout-step-pay" style="display:none; text-align:center;">
                <div style="padding:10px 14px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:12px; margin-bottom:14px;">
                    <div id="sl-pay-plan-name" style="font-size:13px; font-weight:700; color:#1e40af;"></div>
                    <div id="sl-pay-total" style="font-size:24px; font-weight:800; color:#0f172a; margin-top:2px;"></div>
                    <div id="sl-pay-order-id" style="font-size:11.5px; color:#475569; font-family:monospace; margin-top:2px;"></div>
                </div>

                <div id="sl-qris-box" style="display:inline-block; padding:14px; background:#ffffff; border:1.5px solid #e2e8f0; border-radius:14px; margin-bottom:12px;">
                    <canvas id="sl-qris-canvas" width="220" height="220" style="display:block; margin:0 auto;"></canvas>
                </div>

                <div id="sl-va-box" style="display:none; padding:14px; background:#f8fafc; border:1.5px solid #cbd5e1; border-radius:12px; margin-bottom:12px;">
                    <div style="font-size:11.5px; color:#64748b; font-weight:600;">Nomor Virtual Account / Kode Bayar</div>
                    <div id="sl-payment-number-text" style="font-size:18px; font-weight:800; font-family:monospace; color:#0f172a; margin-top:4px; word-break:break-all;"></div>
                </div>

                <div id="sl-pay-status-badge" style="font-size:12.5px; font-weight:600; color:#0284c7; margin-bottom:12px;">
                    ⏳ Menunggu pembayaran... Halaman ini mengecek status otomatis.
                </div>

                <div style="display:flex; gap:8px; justify-content:center; flex-wrap:wrap;">
                    <button type="button" onclick="pollPakasirStatusOnce()" style="padding:10px 14px; border-radius:10px; border:1px solid #bfdbfe; background:#eff6ff; color:#1d4ed8; font-size:13px; font-weight:700; cursor:pointer; font-family:inherit;">
                        🔄 Cek Status Sekarang
                    </button>
                    <a id="sl-pakasir-direct-link" href="#" target="_blank" rel="noopener"
                        style="padding:10px 14px; border-radius:10px; border:none; background:#16a34a; color:#ffffff; font-size:13px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center;">
                        Buka Halaman Bayar Pakasir ↗
                    </a>
                </div>

                <button type="button" onclick="resetCheckoutView()" style="margin-top:12px; background:none; border:none; color:#64748b; font-size:12px; cursor:pointer; text-decoration:underline; font-family:inherit;">
                    Ganti Paket / Metode Pembayaran
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js" defer></script>
<script>
    (function () {
        let selectedPlanCode = 'hemat';
        let activeOrderId = null;
        let pollTimer = null;

        window.openUpgradeModal = function (message, reason) {
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
            startPakasirCheckout('hemat');
        };

        window.selectCheckoutPlan = function (code) {
            selectedPlanCode = code || 'hemat';
            document.querySelectorAll('#sl-checkout-modal .sl-plan-chip').forEach(btn => {
                btn.classList.toggle('selected', btn.getAttribute('data-plan-code') === selectedPlanCode);
            });
        };

        window.startPakasirCheckout = function (planCode) {
            closeUpgradeModal();
            selectCheckoutPlan(planCode || 'hemat');
            resetCheckoutView();
            const modal = document.getElementById('sl-checkout-modal');
            if (modal) modal.classList.add('open');
        };

        window.closeCheckoutModal = function () {
            if (pollTimer) {
                clearInterval(pollTimer);
                pollTimer = null;
            }
            const modal = document.getElementById('sl-checkout-modal');
            if (modal) modal.classList.remove('open');
        };

        window.resetCheckoutView = function () {
            if (pollTimer) {
                clearInterval(pollTimer);
                pollTimer = null;
            }
            activeOrderId = null;
            const err = document.getElementById('sl-checkout-error');
            if (err) err.style.display = 'none';
            const s1 = document.getElementById('sl-checkout-step-select');
            const s2 = document.getElementById('sl-checkout-step-pay');
            if (s1) s1.style.display = 'block';
            if (s2) s2.style.display = 'none';
        };

        window.submitPakasirInvoice = async function () {
            const btn = document.getElementById('sl-btn-create-invoice');
            const errEl = document.getElementById('sl-checkout-error');
            const method = document.getElementById('sl-payment-method')?.value || 'qris';
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            if (errEl) errEl.style.display = 'none';
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Membuat tagihan Pakasir...';
            }

            try {
                const res = await fetch("{{ route('payment.create') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({ plan_code: selectedPlanCode, payment_method: method })
                });

                if (res.status === 401) {
                    window.location.href = "{{ route('login') }}";
                    return;
                }

                const data = await res.json();
                if (data.status === 'pakasir_unconfigured') {
                    if (errEl) {
                        errEl.innerHTML = `${data.message}<br><a href="${data.whatsapp_url}" target="_blank" rel="noopener" style="display:inline-block;margin-top:8px;padding:7px 12px;background:#16a34a;color:#fff;border-radius:8px;text-decoration:none;font-weight:700;">Aktivasi Cepat via WhatsApp Admin →</a>`;
                        errEl.style.display = 'block';
                    }
                    return;
                }

                if (!res.ok || data.status !== 'success' || !data.transaction) {
                    throw new Error(data.message || 'Gagal membuat transaksi pembayaran.');
                }

                const tx = data.transaction;
                activeOrderId = tx.order_id;
                document.getElementById('sl-checkout-step-select').style.display = 'none';
                document.getElementById('sl-checkout-step-pay').style.display = 'block';
                document.getElementById('sl-pay-plan-name').textContent = tx.plan_name;
                document.getElementById('sl-pay-total').textContent = 'Rp ' + Number(tx.total_payment || tx.amount).toLocaleString('id-ID');
                document.getElementById('sl-pay-order-id').textContent = 'Order ID: ' + tx.order_id;
                document.getElementById('sl-pakasir-direct-link').href = tx.checkout_url || '#';

                const qrisBox = document.getElementById('sl-qris-box');
                const vaBox = document.getElementById('sl-va-box');
                const vaTxt = document.getElementById('sl-payment-number-text');

                if (tx.payment_method === 'qris' && tx.payment_number && window.QRCode) {
                    qrisBox.style.display = 'inline-block';
                    vaBox.style.display = 'none';
                    const canvas = document.getElementById('sl-qris-canvas');
                    window.QRCode.toCanvas(canvas, tx.payment_number, { width: 220, margin: 1 });
                } else {
                    qrisBox.style.display = 'none';
                    vaBox.style.display = 'block';
                    vaTxt.textContent = tx.payment_number || 'Klik tombol "Buka Halaman Bayar Pakasir" di bawah';
                }

                if (pollTimer) clearInterval(pollTimer);
                pollTimer = setInterval(pollPakasirStatusOnce, 4500);
            } catch (e) {
                if (errEl) {
                    errEl.textContent = e.message || 'Gagal memproses pembayaran.';
                    errEl.style.display = 'block';
                }
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'Buat Pembayaran QRIS / VA Sekarang →';
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
                        badge.textContent = '✅ Pembayaran berhasil! Paket langsung aktif. Memuat ulang workspace...';
                    }
                    setTimeout(() => window.location.reload(), 1200);
                }
            } catch (e) { }
        };

        // Global interceptor untuk semua fetch endpoint AI/Download yang mengembalikan 402 upgrade_required
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
                            openUpgradeModal(data.message, data.reason);
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
