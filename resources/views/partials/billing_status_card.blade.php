@php
    $bs = $bs ?? [];
    $inWorkspace = $inWorkspace ?? false;
    $bsAdmin = !empty($bs['is_admin']);
    $bsPaid = !empty($bs['is_paid_active']);
    $bsUnlim = !empty($bs['is_unlimited']);
    $bsPlanName = $bsAdmin ? 'Administrator (Unlimited)' : ($bs['plan']['name'] ?? 'Free (Coba Dulu)');

    $quotaLimit = max(0, (int) ($bs['quota_limit'] ?? 0));
    $quotaUsed = max(0, (int) ($bs['quota_used'] ?? 0));
    $remainingQuota = max(0, (int) ($bs['remaining_quota'] ?? 0));
    $quotaPct = $quotaLimit > 0 ? min(100, max(0, (int) round(($remainingQuota / $quotaLimit) * 100))) : 0;

    $remainingDays = $bs['remaining_days'] ?? null;
    $totalDays = $bs['total_days'] ?? null;
    $daysPct = ($totalDays && $remainingDays !== null) ? min(100, max(0, (int) round(($remainingDays / $totalDays) * 100))) : 100;

    $freeProjectUsed = (int) ($bs['free_project_used'] ?? 0);
    $freeProjectLeft = max(0, 1 - $freeProjectUsed);
    $unlockedCount = (int) ($bs['unlocked_projects_count'] ?? 0);
    $totalProjectsCount = (int) ($bs['total_projects_count'] ?? $unlockedCount);
    $freeUsage = $bs['free_tool_usage'] ?? [];
    $toolQuotaStatus = $bs['tool_quota_status'] ?? [];

    $toolItems = [
        ['key' => 'ppt', 'label' => 'Generate PPT Sidang'],
        ['key' => 'defense', 'label' => 'Simulasi Sidang AI'],
        ['key' => 'paraphrase', 'label' => 'Parafrase Bab 1–5'],
        ['key' => 'stats_analyze', 'label' => 'Olah Data SPSS AI'],
        ['key' => 'stats_interpret', 'label' => 'Interpretasi Bab IV'],
    ];
    $refBonusSlots = max(0, min(3, (int) ($freeUsage['_ref_bonus_slots'] ?? 0)));
    $bonusEligibleKeys = ['paraphrase', 'stats_analyze', 'stats_interpret'];
@endphp

@if($bsPaid)
    @php
        $bsCardUid = 'sl-quota-' . substr(md5(($inWorkspace ? 'ws' : 'pub') . '|' . ($bsPlanName ?? 'plan')), 0, 8);
        $quotaSummaryShort = ($bsAdmin || $bsUnlim)
            ? '∞ Unlimited · ' . ($bsAdmin ? 'Selamanya' : ('Aktif s.d. ' . ($bs['expires_at_label'] ?? '-')))
            : ("Sisa {$remainingQuota}/" . max($quotaLimit, $unlockedCount) . " naskah · " . ($remainingDays !== null ? "Sisa {$remainingDays} hari" : 'Aktif'));
    @endphp
<style>
    .sl-status-banner {
        background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
        color: #ffffff;
        border-radius: 16px;
        padding: 15px 18px;
        margin-bottom: 18px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.14);
    }
    .sl-status-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }
    .sl-status-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .sl-quota-toggle-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 9px 14px;
        border-radius: 11px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.24);
        color: #ffffff;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        font-family: inherit;
        white-space: nowrap;
        transition: background 0.15s, border-color 0.15s;
    }
    .sl-quota-toggle-btn:hover {
        background: rgba(255, 255, 255, 0.2);
        border-color: rgba(147, 197, 253, 0.55);
    }
    .sl-quota-toggle-btn svg {
        transition: transform 0.2s ease;
        flex-shrink: 0;
    }
    .sl-quota-toggle-btn[aria-expanded="true"] svg.sl-chevron {
        transform: rotate(180deg);
    }
    .sl-status-details {
        display: none;
        margin-top: 14px;
        padding-top: 14px;
        border-top: 1px solid rgba(255, 255, 255, 0.14);
    }
    .sl-status-details.open {
        display: block;
    }
    .sl-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 11px;
        border-radius: 999px;
        background: rgba(56, 189, 248, 0.18);
        border: 1px solid rgba(56, 189, 248, 0.38);
        color: #7dd3fc;
        font-size: 11.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .sl-status-ref-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 9px 14px;
        border-radius: 11px;
        background: #2563eb;
        border: 1px solid #3b82f6;
        color: #ffffff;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        font-family: inherit;
        white-space: nowrap;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
        transition: filter 0.15s;
    }
    .sl-status-ref-btn:hover { filter: brightness(1.08); color: #ffffff; }

    .sl-quota-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr));
        gap: 10px;
        margin-top: 0;
    }
    .sl-quota-box {
        background: rgba(255, 255, 255, 0.09);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 12px;
        padding: 12px 14px;
    }
    .sl-quota-label {
        font-size: 11.5px;
        color: #cbd5e1;
        font-weight: 600;
        margin-bottom: 4px;
    }
    .sl-quota-val {
        font-size: 17px;
        font-weight: 800;
        color: #ffffff;
        line-height: 1.25;
    }
    .sl-quota-sub {
        font-size: 11.5px;
        color: #93c5fd;
        margin-top: 3px;
    }
    .sl-bar-track {
        height: 6px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.16);
        overflow: hidden;
        margin-top: 8px;
    }
    .sl-bar-fill {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #38bdf8 0%, #4ade80 100%);
    }

    .sl-feat-status-wrap {
        margin-top: 14px;
        padding-top: 14px;
        border-top: 1px solid rgba(255, 255, 255, 0.14);
    }
    .sl-feat-status-head {
        font-size: 12px;
        font-weight: 700;
        color: #e2e8f0;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 6px;
    }
    .sl-feat-status-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(min(100%, 210px), 1fr));
        gap: 8px;
    }
    .sl-feat-chip {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 8px 11px;
        border-radius: 10px;
        background: rgba(15, 23, 42, 0.36);
        border: 1px solid rgba(255, 255, 255, 0.12);
        font-size: 12px;
    }
    .sl-feat-chip-name {
        color: #f1f5f9;
        font-weight: 600;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sl-feat-chip-val {
        flex-shrink: 0;
        font-size: 11px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 999px;
    }
    .sl-feat-chip-val.ok {
        background: rgba(74, 222, 128, 0.18);
        color: #86efac;
        border: 1px solid rgba(74, 222, 128, 0.35);
    }
    .sl-feat-chip-val.empty {
        background: rgba(248, 113, 113, 0.18);
        color: #fca5a5;
        border: 1px solid rgba(248, 113, 113, 0.35);
    }
    @media (max-width: 640px) {
        .sl-status-banner { padding: 14px; border-radius: 14px; }
        .sl-status-actions { width: 100%; }
        .sl-quota-toggle-btn, .sl-status-ref-btn { width: 100%; }
    }
</style>

<div class="sl-status-banner" id="{{ $bsCardUid }}">
    <div class="sl-status-top">
        <div style="min-width: 0;">
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                <span class="sl-status-pill">
                    {{ $bsPaid ? '✓ Langganan Aktif' : 'Tier Akun Saat Ini' }}
                </span>
                <span style="font-size: 12px; color: #93c5fd; font-weight: 600;">{{ $quotaSummaryShort }}</span>
            </div>
            <div style="font-size: 18px; font-weight: 800; margin-top: 6px; line-height: 1.25;">
                {{ $bsPlanName }}
            </div>
        </div>
        <div class="sl-status-actions">
            <button type="button"
                class="sl-quota-toggle-btn"
                id="{{ $bsCardUid }}-toggle"
                aria-expanded="false"
                aria-controls="{{ $bsCardUid }}-details"
                onclick="var d=document.getElementById('{{ $bsCardUid }}-details'); var open=!d.classList.contains('open'); d.classList.toggle('open', open); this.setAttribute('aria-expanded', open ? 'true' : 'false'); var lbl=this.querySelector('.sl-toggle-label'); if(lbl) lbl.textContent=open ? 'Tutup Sisa Kuota' : 'Lihat Sisa Kuota';">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1">
                    <path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>
                </svg>
                <span class="sl-toggle-label">Lihat Sisa Kuota</span>
                <svg class="sl-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </button>
            @if($inWorkspace)
                <button type="button" onclick="openTab('referral', 'Ajak Teman')" class="sl-status-ref-btn">
                    🎁 Saldo Referral: Rp {{ number_format($bs['referral_stats']['balance'] ?? 0, 0, ',', '.') }}
                </button>
            @else
                <a href="{{ route('app.create', ['tab' => 'referral']) }}" class="sl-status-ref-btn">
                    🎁 Saldo Referral: Rp {{ number_format($bs['referral_stats']['balance'] ?? 0, 0, ',', '.') }}
                </a>
            @endif
        </div>
    </div>

    <div id="{{ $bsCardUid }}-details" class="sl-status-details">
        <div style="font-size: 12.5px; color: #cbd5e1; margin-bottom: 12px;">
            @if($bsAdmin)
                Akses penuh Administrator · Selamanya
            @elseif($bsUnlim)
                Semua fitur AI &amp; generator Bab 1–5 aktif tanpa batas naskah hingga <strong>{{ $bs['expires_at_label'] ?? '-' }}</strong>
            @elseif($bsPaid)
                Paket kuota aktif hingga <strong>{{ $bs['expires_at_label'] ?? '-' }}</strong> · bebas revisi tanpa sistem kredit
            @else
                Proyek uji coba: <strong>Sisa {{ $freeProjectLeft }} dari 1 proyek</strong> (Sampul, Bab I &amp; Pustaka)
            @endif
        </div>

        <div class="sl-quota-cards">
        <div class="sl-quota-box">
            <div class="sl-quota-label">Kuota Proposal &amp; Skripsi (Bab 1–5)</div>
            @if($bsAdmin || $bsUnlim)
                <div class="sl-quota-val" style="color: #4ade80;">∞ Unlimited Naskah</div>
                <div class="sl-quota-sub">{{ $totalProjectsCount }} naskah dibuat · tanpa batas</div>
                <div class="sl-bar-track"><div class="sl-bar-fill" style="width: 100%;"></div></div>
            @elseif($bsPaid)
                <div class="sl-quota-val" style="color: #4ade80;">Sisa {{ $remainingQuota }} dari {{ max($quotaLimit, $unlockedCount) }} naskah</div>
                <div class="sl-quota-sub">Terpakai {{ $quotaUsed }}/{{ max($quotaLimit, $unlockedCount) }} · {{ $unlockedCount }} naskah full terbuka</div>
                <div class="sl-bar-track"><div class="sl-bar-fill" style="width: {{ $quotaPct }}%;"></div></div>
            @else
                <div class="sl-quota-val">Sisa {{ $freeProjectLeft }} dari 1 proyek</div>
                <div class="sl-quota-sub">Sampul, Bab I &amp; Daftar Pustaka</div>
                <div class="sl-bar-track"><div class="sl-bar-fill" style="width: {{ $freeProjectLeft * 100 }}%;"></div></div>
            @endif
        </div>

        <div class="sl-quota-box">
            <div class="sl-quota-label">Masa Aktif Paket</div>
            @if($bsAdmin)
                <div class="sl-quota-val" style="color: #4ade80;">Selamanya</div>
                <div class="sl-quota-sub">Akun Administrator</div>
                <div class="sl-bar-track"><div class="sl-bar-fill" style="width: 100%;"></div></div>
            @elseif($bsPaid && $remainingDays !== null && $totalDays)
                <div class="sl-quota-val" style="color: {{ $remainingDays <= 3 ? '#fbbf24' : '#4ade80' }};">Sisa {{ $remainingDays }} dari {{ $totalDays }} hari</div>
                <div class="sl-quota-sub">Berlaku s.d. {{ $bs['expires_at_label'] ?? '-' }}</div>
                <div class="sl-bar-track"><div class="sl-bar-fill" style="width: {{ $daysPct }}%;"></div></div>
            @else
                <div class="sl-quota-val">Selamanya (Gratis)</div>
                <div class="sl-quota-sub">Upgrade kapan saja sesuai kebutuhan</div>
                <div class="sl-bar-track"><div class="sl-bar-fill" style="width: 100%;"></div></div>
            @endif
        </div>

        <div class="sl-quota-box">
            <div class="sl-quota-label">Unduh Word (.docx) &amp; PDF</div>
            @if($bsAdmin)
                <div class="sl-quota-val" style="color: #4ade80;">Unlimited Bebas</div>
                <div class="sl-quota-sub">Format 4-4-3-3 siap cetak</div>
            @elseif($bsPaid)
                <div class="sl-quota-val" style="color: #4ade80;">Unlimited</div>
                <div class="sl-quota-sub">Semua paket berlangganan bebas unduh ({{ $remainingDays ?? 30 }} hari)</div>
            @else
                <div class="sl-quota-val">Siap setelah aktif paket</div>
                <div class="sl-quota-sub">Draf Anda tersimpan otomatis</div>
            @endif
        </div>
    </div>

    <div class="sl-feat-status-wrap">
        <div class="sl-feat-status-head">
            <span>{{ $bsPaid ? 'Rincian Sisa & Status Akses Fitur Anda:' : 'Sisa Jatah Coba Fitur AI (Tier Free):' }}</span>
            @if($bsPaid && $remainingDays !== null)
                <span style="color: #93c5fd; font-weight: 600;">
                    {{ $bsUnlim ? "Semua AI Workspace Unlimited selama {$remainingDays} hari lagi" : "Download, Revisi & 10 Tools Unlimited selama {$remainingDays} hari" }}
                </span>
            @endif
        </div>
        <div class="sl-feat-status-grid">
            <div class="sl-feat-chip">
                <span class="sl-feat-chip-name">Cari Ide Judul AI</span>
                <span class="sl-feat-chip-val ok">Gratis Unlimited</span>
            </div>
            <div class="sl-feat-chip">
                <span class="sl-feat-chip-name">Cari Jurnal Ber-DOI</span>
                <span class="sl-feat-chip-val ok">Gratis Unlimited</span>
            </div>
            @foreach($toolItems as $item)
                @php
                    $tKey = $item['key'];
                    $usedCount = (int) ($freeUsage[$tKey] ?? 0);
                    $freeCap = 1 + (in_array($tKey, $bonusEligibleKeys, true) ? $refBonusSlots : 0);
                    $leftFree = max(0, $freeCap - $usedCount);
                    $qInfo = $toolQuotaStatus[$tKey] ?? ['limit' => 2, 'used' => 0, 'remaining' => 2];
                    $qRem = (int) ($qInfo['remaining'] ?? 0);
                    $qLim = (int) ($qInfo['limit'] ?? 0);
                @endphp
                <div class="sl-feat-chip">
                    <span class="sl-feat-chip-name">{{ $item['label'] }}</span>
                    @if($bsAdmin || $bsUnlim)
                        <span class="sl-feat-chip-val ok">Unlimited</span>
                    @elseif($bsPaid && $qLim > 0)
                        <span class="sl-feat-chip-val {{ $qRem > 0 ? 'ok' : 'empty' }}">
                            Sisa {{ $qRem }} dari {{ $qLim }}
                        </span>
                    @else
                        <span class="sl-feat-chip-val {{ $leftFree > 0 ? 'ok' : 'empty' }}">
                            Sisa {{ $leftFree }} dari {{ $freeCap }}
                        </span>
                    @endif
                </div>
            @endforeach
            <div class="sl-feat-chip">
                <span class="sl-feat-chip-name">Lab Revisi AI</span>
                <span class="sl-feat-chip-val ok">{{ $bsPaid ? 'Unlimited' : 'Sisa ' . max(0, 1 - (int) ($freeUsage['revise'] ?? 0)) . ' dari 1' }}</span>
            </div>
            <div class="sl-feat-chip">
                <span class="sl-feat-chip-name">Unduh Word &amp; PDF</span>
                <span class="sl-feat-chip-val ok">{{ $bsPaid ? 'Unlimited' : 'Setelah Aktif' }}</span>
            </div>
            <div class="sl-feat-chip">
                <span class="sl-feat-chip-name">Konsultasi Dosen AI</span>
                <span class="sl-feat-chip-val ok">Gratis Unlimited</span>
            </div>
            <div class="sl-feat-chip">
                <span class="sl-feat-chip-name">10 Tools Mahasiswa</span>
                <span class="sl-feat-chip-val ok">Gratis Unlimited</span>
            </div>
        </div>
    </div>
    </div>
</div>
@endif
