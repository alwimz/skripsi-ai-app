@php
    $plansList = $plans ?? app(\App\Services\BillingService::class)->getPlans(true);
    $activeCode = $billingSummary['plan_code'] ?? (auth()->check() ? (auth()->user()->plan_code ?? 'free') : null);
    $isAdminUser = $billingSummary['is_admin'] ?? false;
    $quotaPlans = array_filter($plansList, fn($p) => in_array($p['group'] ?? '', ['free', 'quota'], true));
    $unlimitedPlans = array_filter($plansList, fn($p) => ($p['group'] ?? '') === 'unlimited');
@endphp

<style>
    .sl-pricing-group-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
        margin: 22px 0 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid #e2e8f0;
    }
    .sl-pricing-group-title h3 {
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
    }
    .sl-pricing-group-title span {
        font-size: 12.5px;
        color: #64748b;
        font-weight: 500;
    }
    .sl-pricing-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(265px, 1fr));
        gap: 16px;
        margin-bottom: 16px;
    }
    .sl-plan-card {
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        border-radius: 16px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    }
    .sl-plan-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
        border-color: #93c5fd;
    }
    .sl-plan-card.popular {
        border: 2px solid #2563eb;
        box-shadow: 0 10px 26px rgba(37, 99, 235, 0.12);
    }
    .sl-plan-top-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 10px;
        flex-wrap: wrap;
    }
    .sl-plan-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 999px;
        color: #ffffff;
    }
    .sl-plan-sold {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 999px;
        background: #fff7ed;
        color: #c2410c;
        border: 1px solid #fed7aa;
    }
    .sl-plan-name {
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 4px;
    }
    .sl-plan-tagline {
        font-size: 12.5px;
        color: #64748b;
        line-height: 1.5;
        margin-bottom: 14px;
        min-height: 38px;
    }
    .sl-price-strike-row {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 2px;
        min-height: 20px;
    }
    .sl-price-old {
        font-size: 13px;
        color: #94a3b8;
        text-decoration: line-through;
        font-weight: 600;
    }
    .sl-discount-pill {
        font-size: 10.5px;
        font-weight: 800;
        padding: 2px 7px;
        border-radius: 6px;
        background: #fee2e2;
        color: #dc2626;
    }
    .sl-price-main {
        font-size: 27px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.02em;
        line-height: 1.15;
    }
    .sl-price-period {
        font-size: 12px;
        color: #475569;
        font-weight: 600;
        margin-top: 4px;
        margin-bottom: 14px;
        padding-bottom: 12px;
        border-bottom: 1px dashed #e2e8f0;
    }
    .sl-plan-features {
        list-style: none;
        padding: 0;
        margin: 0 0 16px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .sl-plan-features li {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        font-size: 12.5px;
        color: #334155;
        line-height: 1.45;
    }
    .sl-plan-features li.locked {
        color: #94a3b8;
    }
    .sl-check-ic {
        color: #16a34a;
        font-weight: 800;
        flex-shrink: 0;
    }
    .sl-lock-ic {
        color: #cbd5e1;
        font-weight: 800;
        flex-shrink: 0;
    }
    .sl-plan-btn {
        width: 100%;
        padding: 11px 14px;
        border-radius: 10px;
        font-size: 13.5px;
        font-weight: 700;
        border: 1.5px solid #cbd5e1;
        background: #f8fafc;
        color: #0f172a;
        cursor: pointer;
        text-align: center;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all 0.15s;
        font-family: inherit;
    }
    .sl-plan-btn:hover {
        background: #eff6ff;
        border-color: #2563eb;
        color: #1d4ed8;
    }
    .sl-plan-btn.primary {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        border-color: #1d4ed8;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
    }
    .sl-plan-btn.primary:hover {
        filter: brightness(1.06);
        color: #ffffff;
    }
    .sl-plan-btn.current-free {
        background: #f1f5f9;
        color: #64748b;
        cursor: default;
        border-color: #e2e8f0;
    }
</style>

<div class="sl-pricing-group-title" style="margin-top: 4px;">
    <h3>1. Tier Gratis &amp; Paket Kuota Naskah (Non-Kredit)</h3>
    <span>Bayar sekali sesuai jumlah naskah Proposal / Skripsi yang Anda kerjakan — bebas revisi</span>
</div>

<div class="sl-pricing-grid">
    @foreach($quotaPlans as $p)
        @php
            $isPopular = $p['code'] === 'hemat';
            $isCurrent = $activeCode === $p['code'] && !$isAdminUser;
        @endphp
        <div class="sl-plan-card {{ $isPopular ? 'popular' : '' }}">
            <div>
                <div class="sl-plan-top-row">
                    <span class="sl-plan-badge" style="background: {{ $p['badge_color'] ?? '#2563eb' }};">
                        {{ $p['badge'] ?? 'Paket' }}
                    </span>
                    <span class="sl-plan-sold">
                        🔥 {{ number_format((int) ($p['sold_count'] ?? 0), 0, ',', '.') }}+ {{ $p['code'] === 'free' ? 'pengguna' : 'terjual' }}
                    </span>
                </div>

                <div class="sl-plan-name">{{ $p['name'] }}</div>
                <div class="sl-plan-tagline">{{ $p['tagline'] }}</div>

                <div class="sl-price-strike-row">
                    @if(!empty($p['original_price']) && $p['original_price'] > $p['price'])
                        <span class="sl-price-old">Rp {{ number_format($p['original_price'], 0, ',', '.') }}</span>
                        <span class="sl-discount-pill">{{ $p['discount_label'] }}</span>
                    @endif
                </div>

                <div class="sl-price-main">
                    {{ $p['price'] === 0 ? 'Rp 0' : 'Rp ' . number_format($p['price'], 0, ',', '.') }}
                </div>
                <div class="sl-price-period">{{ $p['period_label'] }}</div>

                <ul class="sl-plan-features">
                    @foreach(($p['features'] ?? []) as $feat)
                        <li>
                            <span class="sl-check-ic">✓</span>
                            <span>{{ $feat }}</span>
                        </li>
                    @endforeach
                    @foreach(($p['locked_features'] ?? []) as $lFeat)
                        <li class="locked">
                            <span class="sl-lock-ic">✕</span>
                            <span>{{ $lFeat }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div>
                @if($p['code'] === 'free')
                    @auth
                        <span class="sl-plan-btn current-free">
                            {{ $isCurrent ? 'Tier Aktif Saat Ini' : 'Paket Uji Coba Awal' }}
                        </span>
                    @else
                        <a href="{{ route('register') }}" class="sl-plan-btn">Mulai Coba Gratis →</a>
                    @endauth
                @else
                    @auth
                        <button type="button" class="sl-plan-btn {{ $isPopular ? 'primary' : '' }}"
                            onclick="startPakasirCheckout('{{ $p['code'] }}')">
                            <span>Pilih &amp; Bayar QRIS →</span>
                        </button>
                    @else
                        <a href="{{ route('register') }}" class="sl-plan-btn {{ $isPopular ? 'primary' : '' }}">
                            <span>Daftar &amp; Pilih Paket →</span>
                        </a>
                    @endauth
                @endif
            </div>
        </div>
    @endforeach
</div>

<div class="sl-pricing-group-title" style="margin-top: 28px;">
    <h3>2. Paket Unlimited Proposal &amp; Skripsi (Bebas Batas Kuota)</h3>
    <span>Lebih murah dari harga langganan kompetitor — pilih durasi sesuai target sidang Anda</span>
</div>

<div class="sl-pricing-grid">
    @foreach($unlimitedPlans as $p)
        @php
            $isPopular = in_array($p['code'], ['unlimited_1m', 'unlimited_3m'], true);
        @endphp
        <div class="sl-plan-card {{ $p['code'] === 'unlimited_3m' ? 'popular' : '' }}">
            <div>
                <div class="sl-plan-top-row">
                    <span class="sl-plan-badge" style="background: {{ $p['badge_color'] ?? '#4f46e5' }};">
                        {{ $p['badge'] ?? 'Unlimited' }}
                    </span>
                    <span class="sl-plan-sold">
                        🔥 {{ number_format((int) ($p['sold_count'] ?? 0), 0, ',', '.') }}+ terjual
                    </span>
                </div>

                <div class="sl-plan-name">{{ $p['name'] }}</div>
                <div class="sl-plan-tagline">{{ $p['tagline'] }}</div>

                <div class="sl-price-strike-row">
                    @if(!empty($p['original_price']) && $p['original_price'] > $p['price'])
                        <span class="sl-price-old">Rp {{ number_format($p['original_price'], 0, ',', '.') }}</span>
                        <span class="sl-discount-pill">{{ $p['discount_label'] }}</span>
                    @endif
                </div>

                <div class="sl-price-main">
                    Rp {{ number_format($p['price'], 0, ',', '.') }}
                </div>
                <div class="sl-price-period">{{ $p['period_label'] }}</div>

                <ul class="sl-plan-features">
                    @foreach(($p['features'] ?? []) as $feat)
                        <li>
                            <span class="sl-check-ic">✓</span>
                            <span>{{ $feat }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div>
                @auth
                    <button type="button" class="sl-plan-btn {{ $isPopular ? 'primary' : '' }}"
                        onclick="startPakasirCheckout('{{ $p['code'] }}')">
                        <span>Langganan Sekarang →</span>
                    </button>
                @else
                    <a href="{{ route('register') }}" class="sl-plan-btn {{ $isPopular ? 'primary' : '' }}">
                        <span>Daftar &amp; Aktifkan →</span>
                    </a>
                @endauth
            </div>
        </div>
    @endforeach
</div>
