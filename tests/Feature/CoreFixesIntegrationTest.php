<?php

namespace Tests\Feature;

use App\Http\Controllers\GeneratorController;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class CoreFixesIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tools_mahasiswa_requires_login_for_guests_and_loads_for_authenticated_users(): void
    {
        $this->get('/tools')->assertRedirect(route('login'));
        $this->post('/tools/flowchart/generate', ['topic' => 'Sistem Kasir'])->assertRedirect(route('login'));
        $this->post('/tools/convert/extract-docx-text')->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)
            ->get('/tools')
            ->assertStatus(200)
            ->assertSee('<h1 class="ws-page-title">Tools Mahasiswa</h1>', false)
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_seo_sitemap_robots_htaccess_and_paket_meta_are_valid(): void
    {
        $sitemap = $this->get('/sitemap.xml');
        $sitemap->assertStatus(200);
        $xml = $sitemap->getContent();
        $this->assertStringContainsString('<loc>' . rtrim(url('/'), '/') . '/</loc>', $xml);
        $this->assertStringContainsString('/paket</loc>', $xml);
        $this->assertStringContainsString('/panduan</loc>', $xml);
        $this->assertStringNotContainsString('/tools</loc>', $xml);

        $robots = file_get_contents(public_path('robots.txt'));
        $this->assertIsString($robots);
        $this->assertStringContainsString('Disallow: /tools', $robots);
        $this->assertStringContainsString('Allow: /paket', $robots);
        $this->assertStringContainsString('Sitemap: https://skripsilabai.com/sitemap.xml', $robots);

        $htaccess = file_get_contents(public_path('.htaccess'));
        $this->assertIsString($htaccess);
        $this->assertStringContainsString('^www\\.skripsilabai\\.com$', $htaccess);
        $this->assertStringContainsString('https://skripsilabai.com/$1 [L,R=301]', $htaccess);

        $this->get('/paket')
            ->assertStatus(200)
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('<meta name="description"', false);

        $landingHtml = $this->get('/')->assertStatus(200)->getContent();
        $hargaPos = strpos($landingHtml, 'id="harga-paket"');
        $faqPos = strpos($landingHtml, 'id="faq"');
        $this->assertNotFalse($hargaPos);
        $this->assertNotFalse($faqPos);
        $this->assertLessThan($faqPos, $hargaPos, 'Pricing section must appear above FAQ on the landing page.');
    }

    public function test_quota_status_card_is_collapsed_by_default_with_lihat_sisa_kuota_toggle(): void
    {
        $admin = User::factory()->create(['email' => 'alwi@gmail.com']);
        $resp = $this->actingAs($admin)->get('/app/create');
        $resp->assertStatus(200);

        $html = $resp->getContent();
        $this->assertStringContainsString('Lihat Sisa Kuota', $html);
        $this->assertStringContainsString('aria-expanded="false"', $html);
        $this->assertStringContainsString('class="sl-status-details"', $html);
        $this->assertStringNotContainsString('class="sl-status-details open"', $html);
    }

    public function test_citation_style_harvard_and_ieee_are_saved_prompted_and_normalized_end_to_end(): void
    {
        $user = User::factory()->create();

        $startResp = $this->actingAs($user)->post('/project/start', [
            'judul' => 'Analisis Pengaruh Kualitas Layanan terhadap Kepuasan Pelanggan',
            'prodi' => 'Manajemen',
            'metode_penelitian' => 'Kuantitatif',
            'gaya_sitasi' => 'Harvard',
            'tahun_min' => '2022',
            'asal_referensi' => 'Indonesia + Internasional',
            'bahasa_penulisan' => 'Indonesia',
            'sumber_referensi' => ['Umum', 'Terindeks Sinta'],
        ]);
        $startResp->assertRedirect();

        $project = Project::where('user_id', $user->id)->latest('id')->first();
        $this->assertNotNull($project);
        $meta = json_decode((string) $project->citation_validation, true);
        $this->assertSame('Harvard', $meta['sitasi']['gaya'] ?? null);

        // Pre-populate cached verified_refs so prompt generation stays 100% offline/deterministic in test
        $meta['verified_refs'] = [
            [
                'in_text' => 'Wijaya et al.',
                'first_surname' => 'Wijaya',
                'year' => 2024,
                'authors_apa' => 'Wijaya, A., & Putra, B.',
                'authors_ieee' => 'A. Wijaya and B. Putra',
                'title' => 'Pengaruh Kualitas Layanan Terhadap Loyalitas',
                'venue' => 'Jurnal Manajemen Indonesia',
                'vol' => '12',
                'iss' => '2',
                'pages' => '45-58',
                'doi' => 'https://doi.org/10.1234/jmi.2024',
            ]
        ];
        $topicHash = substr(md5(mb_strtolower(trim((string) $project->judul)) . '|' . mb_strtolower(trim((string) $project->prodi))), 0, 8);
        $meta['verified_refs_sig'] = "2022|Indonesia + Internasional|Harvard|Umum,Terindeks Sinta|{$topicHash}";
        $project->citation_validation = json_encode($meta, JSON_UNESCAPED_UNICODE);
        $project->save();

        $controller = app(GeneratorController::class);

        $mPromptBab = new ReflectionMethod($controller, 'promptBab');
        $mPromptBab->setAccessible(true);
        $bab5Harvard = $mPromptBab->invoke($controller, 'bab5', $project, []);

        $this->assertStringContainsString('Gaya Harvard', $bab5Harvard);
        $this->assertStringContainsString('TANPA tanda koma', $bab5Harvard);
        $this->assertStringContainsString("Surya, B. & Wijaya, A. (2024) 'Judul artikel", $bab5Harvard);
        $this->assertStringNotContainsString(
            'Nama Penulis. (Tahun). Judul artikel penelitian secara spesifik. Nama Jurnal Bereputasi, Volume(Nomor), Halaman.',
            $bab5Harvard
        );

        // Test IEEE prompts too
        $meta['sitasi']['gaya'] = 'IEEE';
        $meta['verified_refs_sig'] = "2022|Indonesia + Internasional|IEEE|Umum,Terindeks Sinta|{$topicHash}";
        $project->citation_validation = json_encode($meta, JSON_UNESCAPED_UNICODE);
        $project->save();
        $bab5Ieee = $mPromptBab->invoke($controller, 'bab5', $project, []);
        $this->assertStringContainsString('[1] B. Surya and A. Wijaya', $bab5Ieee);
        $this->assertStringContainsString('[Sitasi teks wajib: [1]]', $bab5Ieee);

        // Test deterministic normalization & bibliography formatting
        $mNorm = new ReflectionMethod($controller, 'normalizeInTextCitationsForStyle');
        $mNorm->setAccessible(true);
        $normOut = $mNorm->invoke(
            $controller,
            'Penelitian di kota besar (Jakarta, 2024) sejalan dengan temuan (Surya, 2023) dan (Wijaya et al., 2024).',
            'Harvard'
        );
        $this->assertStringContainsString('(Jakarta, 2024)', $normOut);
        $this->assertStringContainsString('(Surya 2023)', $normOut);
        $this->assertStringContainsString('(Wijaya et al. 2024)', $normOut);

        $mCleanDp = new ReflectionMethod($controller, 'cleanDaftarPustaka');
        $mCleanDp->setAccessible(true);
        $cleanHarvard = $mCleanDp->invoke(
            $controller,
            'Surya, B., & Wijaya, A. (2024). Analisis layanan digital modern. Jurnal Bisnis, 5(1), 10-20.',
            '2022',
            'Harvard'
        );
        $this->assertSame(
            "Surya, B. & Wijaya, A. (2024) 'Analisis layanan digital modern', Jurnal Bisnis, 5(1), 10-20.",
            $cleanHarvard
        );

        // Workspace preview renders SVG flowchart builder, OL start preservation & active citation badge
        $project->bab3_content = "3.1 Desain Penelitian\n\nGambar 3.1 Diagram Alur Penelitian\n```\n[Mulai]\n↓\n[Evaluasi Model]\n↓\n[Selesai]\n```";
        $project->save();
        $wsHtml = $this->actingAs($user)->get('/project/' . $project->id)->assertStatus(200)->getContent();
        $this->assertStringContainsString('buildAcademicFlowchartSvgJs', $wsHtml);
        $this->assertStringContainsString('splitListAtLines', $wsHtml);
        $this->assertStringContainsString('collectSentenceSplitPoints', $wsHtml);
        $this->assertStringContainsString('split-li-cont', $wsHtml);
        $this->assertStringContainsString('measureContainerWithNode', $wsHtml);
        $this->assertStringContainsString('padding: 4cm 3cm 2.15cm 4cm;', $wsHtml);
        $this->assertStringContainsString('max-width: 794px;', $wsHtml);
        $this->assertStringContainsString('function updateMargins(silent = false)', $wsHtml);
        $this->assertStringContainsString('updateMargins(true);', $wsHtml);
        $this->assertStringNotContainsString('scaleA4SheetsToViewport', $wsHtml);
        $this->assertDoesNotMatchRegularExpression('/#sheets-container\s*\{[^}]*overflow\s*:\s*hidden/s', $wsHtml);
        $this->assertSame(
            preg_match_all('/<script\b/i', $wsHtml),
            preg_match_all('/<\/script>/i', $wsHtml),
            'Every <script> tag in generator workspace HTML must be closed properly.'
        );

        // Verify PDF formatter keeps compact tables intact & allows long <li> items to flow without orphan blank pages
        $mPdfFmt = new ReflectionMethod($controller, 'formatAcademicContentForPdf');
        $mPdfFmt->setAccessible(true);
        $longLiText = str_repeat('Kalimat kesimpulan penelitian mengenai klasifikasi multikelas Naive Bayes pada komentar kosmetik lokal yang panjang dan rinci. ', 3);
        $sampleMd = "Tabel 4.4 Confusion Matrix\n| Aktual | Pujian | Keluhan |\n| --- | --- | --- |\n| Pujian | 218 | 12 |\n| Keluhan | 9 | 156 |\n| Total | 227 | 168 |\n\n5.1 Kesimpulan\n1. Item pendek pertama.\n2. {$longLiText}";
        $pdfFormatted = $mPdfFmt->invoke($controller, $sampleMd, 'Judul Uji');
        $this->assertStringContainsString('page-break-inside:avoid;', $pdfFormatted);
        $this->assertStringContainsString('page-break-inside:auto;', $pdfFormatted);

        // Verify Daftar Pustaka linkify adds safe wrapping for DOI links
        $mLinkDp = new ReflectionMethod($controller, 'linkifyDaftarPustakaHtml');
        $mLinkDp->setAccessible(true);
        $linkedDp = $mLinkDp->invoke($controller, 'Surya, B. (2024). Artikel. Jurnal X, 1(1), 1-10. https://doi.org/10.1234/abc');
        $this->assertStringContainsString('overflow-wrap:anywhere;', $linkedDp);

        // Verify Panduan Lengkap mobile CTA bar in /app/create stacks responsively without squeezing text
        $preGenHtml = $this->actingAs($user)->get('/app/create')->assertStatus(200)->getContent();
        $this->assertStringContainsString('panduan-cta-text', $preGenHtml);
        $this->assertMatchesRegularExpression('/@media\s*\(max-width:\s*640px\)\s*\{[^}]*\.panduan-cta-bar\s*\{[^}]*flex-direction:\s*column/s', $preGenHtml);

        // Verify cross-device parity: no-store HTTP headers, sync-state endpoint, DB doc_format persistence & server-first data_riil
        $appCreateResp = $this->actingAs($user)->get('/app/create');
        $appCreateResp->assertStatus(200);
        $this->assertStringContainsString('no-store', (string) $appCreateResp->headers->get('Cache-Control'));

        $wsResp = $this->actingAs($user)->get('/project/' . $project->id);
        $wsResp->assertStatus(200);
        $this->assertStringContainsString('no-store', (string) $wsResp->headers->get('Cache-Control'));
        $wsSyncedHtml = $wsResp->getContent();
        $this->assertStringContainsString('syncProjectFromServer', $wsSyncedHtml);
        $this->assertStringContainsString('applyServerDataRiil', $wsSyncedHtml);
        $this->assertStringContainsString('applyServerDocFormat', $wsSyncedHtml);
        $this->assertStringContainsString('.a4-sheet .paper-editor p', $wsSyncedHtml);
        $this->assertStringContainsString('family=Tinos', $wsSyncedHtml);
        $this->assertStringContainsString('-webkit-text-size-adjust: 100%', $wsSyncedHtml);

        $fmtResp = $this->actingAs($user)->postJson('/project/' . $project->id . '/save-chapter', [
            'chapter' => 'doc_format',
            'font_family' => 'arial',
            'margin_top' => 3,
            'margin_left' => 4,
            'margin_bottom' => 3,
            'margin_right' => 3,
        ])->assertStatus(200);
        $this->assertSame('arial', $fmtResp->json('doc_format.font'));

        $syncResp = $this->actingAs($user)->getJson('/project/' . $project->id . '/sync-state');
        $syncResp->assertStatus(200);
        $this->assertStringContainsString('no-store', (string) $syncResp->headers->get('Cache-Control'));
        $this->assertSame('arial', $syncResp->json('meta.doc_format.font'));
        $this->assertNotEmpty($syncResp->json('state_hash'));

        // Verify GET & POST /logout both redirect cleanly (no 405 Method Not Allowed)
        $this->actingAs($user)->get('/logout')->assertRedirect('/');
        $this->assertGuest();
        $this->get('/logout')->assertRedirect('/');
        $this->actingAs($user)->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_meta_pixel_renders_and_tracks_registration_and_checkout_purchase_events(): void
    {
        config([
            'services.meta.pixel_id' => '1487586500131939',
            'services.meta.capi_token' => 'test-capi-token',
            'services.meta.domain_verification' => 'verify-domain-123',
        ]);
        \Illuminate\Support\Facades\Http::fake([
            'https://graph.facebook.com/*' => \Illuminate\Support\Facades\Http::response(['events_received' => 1], 200),
        ]);

        $this->get('/')
            ->assertStatus(200)
            ->assertSee('https://connect.facebook.net/en_US/fbevents.js', false)
            ->assertSee('fbq(\'init\', "1487586500131939");', false)
            ->assertSee("fbq('track', 'PageView');", false)
            ->assertSee('window.slTrackMetaEvent("ViewContent"', false)
            ->assertSee('data-meta-lead="Hero Coba Gratis"', false)
            ->assertSee('<meta name="facebook-domain-verification" content="verify-domain-123">', false);

        $this->get('/paket')
            ->assertStatus(200)
            ->assertSee('window.slTrackMetaEvent("ViewContent"', false);

        $this->get('/admin')
            ->assertStatus(200)
            ->assertDontSee('connect.facebook.net/en_US/fbevents.js', false);

        $regResp = $this->post('/register', [
            'name' => 'Mahasiswa Uji Pixel',
            'email' => 'pixel.mahasiswa@gmail.com',
            'password' => 'Rahasia123!',
            'password_confirmation' => 'Rahasia123!',
        ]);
        $regResp->assertRedirect(route('app.create'));
        $newUser = User::where('email', 'pixel.mahasiswa@gmail.com')->first();
        $this->assertNotNull($newUser);

        \Illuminate\Support\Facades\Http::assertSent(function ($req) {
            return str_contains($req->url(), 'https://graph.facebook.com/v21.0/1487586500131939/events')
                && ($req['data'][0]['event_name'] ?? '') === 'CompleteRegistration';
        });

        $createHtml = $this->get('/app/create')->assertStatus(200)->getContent();
        $this->assertStringContainsString('window.slTrackMetaEvent("CompleteRegistration"', $createHtml);
        $this->assertStringContainsString('reg_' . $newUser->id, $createHtml);
        $this->assertStringContainsString("trackMetaCheckoutEvent('InitiateCheckout'", $createHtml);
        $this->assertStringContainsString("trackMetaCheckoutEvent('Purchase'", $createHtml);

        \Illuminate\Support\Facades\DB::table('transactions')->insert([
            'user_id' => $newUser->id,
            'order_id' => 'SL-TEST-PIXEL-1',
            'plan_code' => 'starter',
            'plan_name' => 'Paket Starter (1 Naskah)',
            'amount' => 15000,
            'quota_granted' => 1,
            'duration_days' => 30,
            'is_unlimited' => false,
            'payment_method' => 'qris',
            'payment_number' => '',
            'checkout_url' => '',
            'status' => 'completed',
            'paid_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($newUser)
            ->getJson('/payment/status/SL-TEST-PIXEL-1')
            ->assertStatus(200)
            ->assertJson([
                'status' => 'completed',
                'order_id' => 'SL-TEST-PIXEL-1',
                'plan_code' => 'starter',
                'plan_name' => 'Paket Starter (1 Naskah)',
                'amount' => 15000,
            ]);

        config(['services.meta.pixel_id' => '']);
        $this->get('/')
            ->assertStatus(200)
            ->assertDontSee('connect.facebook.net/en_US/fbevents.js', false);
    }

    public function test_mayar_checkout_status_verification_and_webhook_activate_package_idempotently(): void
    {
        config([
            'services.payment_gateway' => 'mayar',
            'services.mayar.api_key' => 'mayar-secret-key',
            'services.mayar.webhook_token' => 'mayar-wh-secret',
            'services.mayar.base_url' => 'https://api.mayar.id/hl/v1',
            'services.meta.pixel_id' => '1487586500131939',
            'services.meta.capi_token' => 'test-capi-token',
        ]);

        \Illuminate\Support\Facades\Http::fake([
            'https://api.mayar.id/hl/v1/payment/create' => \Illuminate\Support\Facades\Http::response([
                'statusCode' => 200,
                'messages' => 'success',
                'data' => [
                    'id' => 'mayar-pay-uuid-1',
                    'transactionId' => 'mayar-tx-uuid-1',
                    'link' => 'https://skripsilab.myr.id/hl/pay/demo-123',
                ],
            ], 200),
            'https://api.mayar.id/hl/v1/payment/mayar-pay-uuid-1' => \Illuminate\Support\Facades\Http::response([
                'statusCode' => 200,
                'messages' => 'success',
                'data' => [
                    'id' => 'mayar-pay-uuid-1',
                    'transactionId' => 'mayar-tx-uuid-1',
                    'status' => 'paid',
                    'amount' => 15000,
                ],
            ], 200),
            'https://graph.facebook.com/*' => \Illuminate\Support\Facades\Http::response(['events_received' => 1], 200),
        ]);

        $user = User::create([
            'name' => 'Pembeli Mayar',
            'email' => 'pembeli.mayar@gmail.com',
            'password' => bcrypt('Secret123!'),
        ]);

        $createResp = $this->actingAs($user)->postJson('/payment/create', [
            'plan_code' => 'starter',
            'payment_method' => 'qris',
        ]);

        $createResp->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'transaction' => [
                    'gateway' => 'mayar',
                    'payment_method' => 'mayar',
                    'checkout_url' => 'https://skripsilab.myr.id/hl/pay/demo-123',
                    'plan_code' => 'starter',
                    'amount' => 15000,
                ],
            ]);

        $orderId = (string) $createResp->json('transaction.order_id');
        $this->assertNotEmpty($orderId);

        \Illuminate\Support\Facades\Http::assertSent(function ($req) {
            return str_contains($req->url(), 'https://graph.facebook.com/v21.0/1487586500131939/events')
                && ($req['data'][0]['event_name'] ?? '') === 'InitiateCheckout';
        });

        // Wrong webhook token must be rejected
        $this->postJson('/webhook/mayar?token=wrong-token', [
            'event' => 'payment.received',
            'data' => [
                'id' => 'mayar-pay-uuid-1',
                'status' => 'paid',
                'amount' => 15000,
            ],
        ])->assertStatus(403);

        // Valid webhook verifies with Mayar API, activates plan, and sends Purchase CAPI
        $this->postJson('/webhook/mayar?token=mayar-wh-secret', [
            'event' => 'payment.received',
            'data' => [
                'id' => 'mayar-pay-uuid-1',
                'transactionId' => 'mayar-tx-uuid-1',
                'status' => 'paid',
                'amount' => 15000,
            ],
        ])->assertStatus(200)->assertJson(['status' => 'success']);

        $user->refresh();
        $this->assertSame('starter', $user->plan_code);
        $this->assertSame(1, (int) $user->project_quota_limit);

        \Illuminate\Support\Facades\Http::assertSent(function ($req) use ($orderId) {
            return str_contains($req->url(), 'https://graph.facebook.com/v21.0/1487586500131939/events')
                && ($req['data'][0]['event_name'] ?? '') === 'Purchase'
                && ($req['data'][0]['event_id'] ?? '') === ('pur_' . $orderId);
        });

        // Replay webhook must be idempotent
        $this->postJson('/webhook/mayar?token=mayar-wh-secret', [
            'event' => 'payment.received',
            'data' => [
                'id' => 'mayar-pay-uuid-1',
                'status' => 'paid',
                'amount' => 15000,
            ],
        ])->assertStatus(200)->assertJson(['status' => 'already_completed']);

        $this->assertSame(1, (int) $user->fresh()->project_quota_limit);

        // Polling status endpoint reflects completed order
        $this->actingAs($user)
            ->getJson('/payment/status/' . $orderId)
            ->assertStatus(200)
            ->assertJson([
                'status' => 'completed',
                'order_id' => $orderId,
                'plan_code' => 'starter',
                'amount' => 15000,
            ]);
    }

    public function test_admin_passcode_falls_back_to_default_when_empty_and_uses_configured_value(): void
    {
        config(['services.admin_password' => '']);

        $this->post('/admin/login', ['pin' => 'admin123'])
            ->assertRedirect(route('admin.dashboard'));
        $this->get('/admin')->assertStatus(200);

        $this->post('/admin/logout');
        config(['services.admin_password' => 'pin-rahasia-2026']);

        $this->post('/admin/login', ['pin' => 'admin123'])
            ->assertSessionHas('error');

        $this->post('/admin/login', ['pin' => 'pin-rahasia-2026'])
            ->assertRedirect(route('admin.dashboard'));
        $this->get('/admin')->assertStatus(200);
    }

    public function test_pdf_export_strips_embedded_daftar_pustaka_from_bab3_and_admin_activation_records_revenue(): void
    {
        config([
            'services.admin_password' => 'admin123',
            'services.meta.pixel_id' => '1487586500131939',
            'services.meta.capi_token' => 'test-capi-token',
        ]);
        \Illuminate\Support\Facades\Http::fake([
            'https://graph.facebook.com/*' => \Illuminate\Support\Facades\Http::response(['events_received' => 1], 200),
        ]);

        $buyer = User::create([
            'name' => 'Pembeli PAI',
            'email' => 'pembeli.pai@gmail.com',
            'password' => bcrypt('Secret123!'),
        ]);

        $project = Project::create([
            'user_id' => $buyer->id,
            'judul' => 'Implementasi Pembelajaran Berdiferensiasi PAI',
            'prodi' => 'Pendidikan Agama Islam',
            'metode_penelitian' => 'Kuantitatif (Survei / Regresi)',
            'bab1_content' => "1.1 Latar Belakang Masalah\nPembelajaran PAI membutuhkan strategi adaptif (Aini et al., 2025).",
            'bab2_content' => "2.1 Landasan Teori\nStrategi diferensiasi mendukung hasil belajar.",
            'bab3_content' => "3.1 Jenis Penelitian\nPenelitian ini bersifat kuantitatif.\n\nDaftar Pustaka\nAini, N. (2025). Strategi PAI. https://doi.org/10.36668/jal.v14i1.1266",
            'daftar_pustaka' => "Aini, N. (2025). Strategi PAI. https://doi.org/10.36668/jal.v14i1.1266",
            'citation_validation' => json_encode(['tahap_pengerjaan' => 'Proposal Skripsi (Bab I-III)']),
        ]);

        // 1. Activate paid package via Admin Panel -> records completed transaction & increases revenue
        $this->post('/admin/login', ['pin' => 'admin123'])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('success');
        $this->post("/admin/users/{$buyer->id}/plan", ['plan_code' => 'starter'])
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertSame('starter', $buyer->fresh()->plan_code);
        $this->assertTrue((bool) $project->fresh()->is_full_unlocked);
        $this->assertSame(15000, (int) \Illuminate\Support\Facades\DB::table('transactions')
            ->where('user_id', $buyer->id)
            ->where('status', 'completed')
            ->sum('amount'));

        $adminHtml = $this->get('/admin')->assertStatus(200)->getContent();
        $this->assertStringContainsString('Rp 15.000', $adminHtml);

        // 2. Sync state must not leak embedded Daftar Pustaka inside bab3
        $syncResp = $this->actingAs($buyer)->getJson("/project/{$project->id}/sync-state");
        $syncResp->assertStatus(200);
        $this->assertStringContainsString('3.1 Jenis Penelitian', (string) $syncResp->json('chapters.bab3'));
        $this->assertStringNotContainsString('Daftar Pustaka', (string) $syncResp->json('chapters.bab3'));

        // 3. Opening project auto-cleans embedded Daftar Pustaka on Bab III in DB
        $this->actingAs($buyer)->get("/project/{$project->id}")->assertStatus(200);
        $this->assertStringNotContainsString('Daftar Pustaka', (string) $project->fresh()->bab3_content);
        $this->assertStringContainsString('Aini, N. (2025)', (string) $project->fresh()->daftar_pustaka);

        // 4. Manual chapter save also strips trailing Daftar Pustaka
        $saveResp = $this->actingAs($buyer)->postJson("/project/{$project->id}/save-chapter", [
            'chapter' => 'bab3',
            'content' => "3.1 Jenis Penelitian\nRevisi manual.\n\nDaftar Pustaka\nHaryaty, E. (2025). Implementasi PAI. https://doi.org/10.22460/p2m.v12i1.5702",
        ]);
        $saveResp->assertStatus(200);
        $this->assertStringNotContainsString('Daftar Pustaka', (string) $saveResp->json('content'));

        // 5. Fallback checkout invoice exposes QRIS image, DANA, and SeaBank details cleanly
        config([
            'services.payment_gateway' => 'auto',
            'services.mayar.api_key' => '',
            'services.pakasir.project' => '',
            'services.pakasir.api_key' => '',
        ]);
        $invResp = $this->actingAs($buyer)->postJson('/payment/create', [
            'plan_code' => 'starter',
            'payment_method' => 'qris',
        ]);
        $invResp->assertStatus(200)
            ->assertJson([
                'status' => 'pakasir_unconfigured',
                'gateway' => 'whatsapp',
                'invoice' => [
                    'account_email' => 'pembeli.pai@gmail.com',
                    'amount' => 15000,
                ],
            ]);
        $this->assertStringContainsString('qris-payment.jpg', (string) $invResp->json('invoice.qris_image_url'));
        $decodedWa = urldecode((string) $invResp->json('whatsapp_url'));
        $this->assertStringContainsString('KONFIRMASI PEMBAYARAN SKRIPSILAB AI', $decodedWa);
        $this->assertStringContainsString('Metode Bayar:', $decodedWa);
        $invJson = json_encode($invResp->json('invoice'));
        $this->assertStringContainsString('082195036971', (string) $invJson);
        $this->assertStringContainsString('901889425034', (string) $invJson);
        $this->assertStringContainsString('Muh Alwi Syahrir', (string) $invJson);
        $this->assertStringContainsString('wa_label', (string) $invJson);

        $this->actingAs($buyer)->get('/paket')
            ->assertStatus(200)
            ->assertSee('slSelectInvoiceMethod', false)
            ->assertSee('sl-invoice-tabs', false)
            ->assertSee('sl-inv-selected-method', false)
            ->assertSee('sl-wa-confirm-btn', false);
    }

    public function test_editor_and_revise_chapter_preserve_flowcharts_and_markdown_tables(): void
    {
        $user = User::create([
            'name' => 'Peneliti Flowchart',
            'email' => 'flowchart.tester@gmail.com',
            'password' => bcrypt('Secret123!'),
            'plan_code' => 'starter',
        ]);

        $bab3Original = <<<'MD'
3.1 Desain Penelitian
Penelitian ini dilaksanakan melalui tahapan sistematis sesuai prosedur berikut.

Gambar 3.1 Diagram Alur (Flowchart) Prosedur Penelitian
```
[Mulai]
↓
[Identifikasi Masalah & Kajian Literatur]
↓
[Pengumpulan Data Lapangan]
↓
[Analisis Data & Uji Hipotesis]
↓
[Selesai]
```

3.2 Jadwal Pelaksanaan Penelitian
Jadwal pelaksanaan kegiatan penelitian disusun selama empat bulan sebagai berikut.

Tabel 3.1 Jadwal Pelaksanaan Penelitian
| No | Kegiatan Penelitian | Bulan ke-1 | Bulan ke-2 | Bulan ke-3 | Bulan ke-4 |
|---|---|---|---|---|---|
| 1 | Penyusunan proposal | ✓ | | | |
| 2 | Pengumpulan data lapangan | | ✓ | ✓ | |
| 3 | Analisis data dan pelaporan | | | ✓ | ✓ |
MD;

        $project = Project::create([
            'user_id' => $user->id,
            'judul' => 'Implementasi Strategi Pembelajaran Berdiferensiasi PAI',
            'prodi' => 'Pendidikan Agama Islam',
            'metode_penelitian' => 'Kuantitatif',
            'bab3_content' => $bab3Original,
            'is_full_unlocked' => true,
        ]);

        $editorHtml = $this->actingAs($user)->get("/project/{$project->id}")->assertStatus(200)->getContent();
        $this->assertStringContainsString('data-raw-diagram', $editorHtml);
        $this->assertStringContainsString('encodeUtf8Base64', $editorHtml);
        $this->assertStringContainsString('serializeTableToMarkdown', $editorHtml);
        $this->assertStringContainsString('serializeFigureToMarkdown', $editorHtml);
        $this->assertStringContainsString('academic-table-wide', $editorHtml);
        $this->assertStringContainsString('[6, 15, 22, 14, 18, 25]', $editorHtml);

        $fakeAi = new class (app(\App\Services\GeminiService::class), app(\App\Services\GroqService::class)) extends \App\Services\AIManagerService {
            public int $callIndex = 0;

            public function generateWithImage(string $prompt, ?string $image = null, string $model = 'auto'): string
            {
                $this->callIndex++;
                if ($this->callIndex === 1) {
                    // Simulasikan AI Lab Revisi yang merusak blok ``` flowchart menjadi box-drawing ASCII
                    return <<<'TXT'
3.1 Desain Penelitian
Penelitian ini dilaksanakan secara terstruktur dan sistematis untuk mengukur efektivitas strategi pembelajaran.

Gambar 3.1 Diagram Alur (Flowchart) Prosedur Penelitian
```
┌───────────────┐
│     Mulai     │
└───────────────┘
```
TXT;
                }

                // Panggilan ke-2: simulasikan AI Lab Revisi yang meratakan Tabel 3.1 tanpa tanda '|'
                return <<<'TXT'
3.2 Jadwal Pelaksanaan Penelitian
Rincian jadwal penelitian yang telah diperbaiki bahasanya disajikan pada tabel di bawah ini.

Tabel 3.1 Jadwal Pelaksanaan Penelitian
No Kegiatan Penelitian Bulan ke-1 Bulan ke-2 Bulan ke-3 Bulan ke-4
1 Penyusunan proposal ✓
2 Pengumpulan data lapangan ✓ ✓
3 Analisis data dan pelaporan ✓ ✓
TXT;
            }
        };
        $this->app->instance(\App\Services\AIManagerService::class, $fakeAi);

        // 1. Revisi 3.1 (memperbaiki kalimat, bukan menghapus diagram) -> flowchart ``` [Mulai] ↓ ... ``` wajib tetap utuh
        $rev1 = $this->actingAs($user)->postJson("/project/{$project->id}/revise", [
            'chapter' => 'bab3',
            'subchapter' => '3.1 Desain Penelitian',
            'revision_mode' => 'format',
            'instruction' => 'Rapikan bahasa agar sesuai EYD V.',
        ]);
        $rev1->assertStatus(200);
        $contentAfterRev1 = (string) $rev1->json('content');
        $this->assertStringContainsString('efektivitas strategi pembelajaran', $contentAfterRev1);
        $this->assertStringContainsString("```\n[Mulai]\n↓", $contentAfterRev1);
        $this->assertStringNotContainsString('┌───────────────┐', $contentAfterRev1);
        $this->assertStringContainsString('| 1 | Penyusunan proposal | ✓ |', $contentAfterRev1);

        // 2. Revisi 3.2 (memperbaiki kalimat, bukan menghapus tabel) -> tabel Markdown |...| wajib tetap utuh
        $rev2 = $this->actingAs($user)->postJson("/project/{$project->id}/revise", [
            'chapter' => 'bab3',
            'subchapter' => '3.2 Jadwal Pelaksanaan Penelitian',
            'revision_mode' => 'format',
            'instruction' => 'Perbaiki kalimat pengantar tabel agar lebih formal.',
        ]);
        $rev2->assertStatus(200);
        $contentAfterRev2 = (string) $rev2->json('content');
        $this->assertStringContainsString('telah diperbaiki bahasanya', $contentAfterRev2);
        $this->assertStringContainsString('| No | Kegiatan Penelitian | Bulan ke-1 | Bulan ke-2 | Bulan ke-3 | Bulan ke-4 |', $contentAfterRev2);
        $this->assertStringContainsString('| 1 | Penyusunan proposal | ✓ |', $contentAfterRev2);
        $this->assertStringNotContainsString('No Kegiatan Penelitian Bulan ke-1', $contentAfterRev2);

        // 3. Pastikan parser PDF tetap merender SVG flowchart dan <table> HTML utuh dari naskah hasil revisi
        $ctrl = app(GeneratorController::class);
        $ref = new ReflectionMethod($ctrl, 'formatAcademicContentForPdf');
        $ref->setAccessible(true);
        $pdfHtml = (string) $ref->invoke($ctrl, $contentAfterRev2, $project->judul);
        $this->assertStringContainsString('data:image/svg+xml;base64,', $pdfHtml);
        $this->assertStringContainsString('<table', $pdfHtml);
    }

    public function test_targeted_structure_repair_only_fixes_mhd_andre_flowcharts_and_tables_once(): void
    {
        config(['services.admin_password' => 'admin123']);

        $otherUser = User::create([
            'name' => 'User Lain',
            'email' => 'user.lain@gmail.com',
            'password' => bcrypt('Secret123!'),
        ]);
        $otherProject = Project::create([
            'user_id' => $otherUser->id,
            'judul' => 'Implementasi Strategi Pembelajaran Berdiferensiasi',
            'prodi' => 'Pendidikan Agama Islam',
            'bab3_content' => "3.2 Jadwal\nTabel 3.1 Jadwal\nNo Kegiatan\n1 Penyusunan",
        ]);

        $andre = User::create([
            'name' => 'Mhd Andre',
            'email' => 'mhdandre22@gmail.com',
            'password' => bcrypt('Secret123!'),
            'plan_code' => 'starter',
        ]);

        $brokenBab2 = <<<'TXT'
2.1 Landasan Teori
Paragraf editan khusus user Andre tetap harus aman utuh 100%.

2.2 Penelitian Terdahulu
Tabel 2.1 Sintesis Penelitian Terdahulu
No Peneliti & Tahun Judul Penelitian Model / Metode Temuan / Peningkatan Posisi Penelitian Ini
1 Trinela et al. (2026) Penerapan Model Kualitatif deskriptif Suasana kelas Menguji pengaruh

2.3 Kerangka Berpikir
Kerangka berpikir disusun dari kondisi awal kelas VII.

Kondisi Awal: Hasil Belajar PAI dan Budi Pekerti Rendah (49% Tuntas, Rata-rata 70)

Tindakan: Penerapan Strategi Pembelajaran Berdiferensiasi

Kondisi Akhir: Peningkatan Hasil Belajar Kognitif, Afektif, dan Psikomotorik Siswa

Gambar 2.1 Bagan Kerangka Berpikir Tindakan Pembelajaran

2.4 Hipotesis Penelitian
Hipotesis nol menyatakan tidak ada pengaruh signifikan.
TXT;

        $brokenBab3 = <<<'TXT'
3.2 Tempat dan Waktu Penelitian
Rincian jadwal penelitian disajikan pada Tabel 3.1.

Tabel 3.1 Jadwal Pelaksanaan Penelitian
No Kegiatan Penelitian Bulan ke-1 Bulan ke-2 Bulan ke-3 Bulan ke-4
1 Penyusunan dan konsultasi proposal ✓
2 Penelaahan literatur dan penyusunan instrumen ✓ ✓

3.3 Populasi dan Sampel
Rincian distribusi sampel disajikan pada Tabel 3.2.

Tabel 3.2 Distribusi Populasi dan Sampel Penelitian
No Rombongan Belajar Jumlah Populasi Perhitungan Sampel Jumlah Sampel
1 Kelas VII-A 30 (30 / 120) × 92 23

3.4 Variabel dan Definisi Operasional
Kisi-kisi instrumen dan definisi operasional variabel disajikan pada Tabel 3.3.

Tabel 3.3 Kisi-kisi Instrumen dan Definisi Operasional Variabel
Variabel Definisi Operasional Dimensi Indikator Empiris Butir Skala
Implementasi (X) Tingkat penerapan Diferensiasi konten Menyesuaikan materi 1–4 Likert 1–5

3.5 Teknik Pengumpulan Data
Angket tertutup diberikan kepada siswa.

3.7 Prosedur Penelitian
Alur prosedur penelitian disajikan pada Gambar 3.1.

MULAI

Observasi Awal & Penyusunan Perangkat Pembelajaran

Evaluasi & Uji Hipotesis (Uji-t & N-Gain Score)
Tidak
Ya

SELESAI

Gambar 3.1 Diagram Alur Prosedur Penelitian
TXT;

        $andreProject = Project::create([
            'user_id' => $andre->id,
            'judul' => 'IMPLEMENTASI STRATEGI PEMBELAJARAN BERDIFERENSIASI DALAM MATA PELAJARAN PAI DAN BUDI PEKERTI',
            'prodi' => 'PENDIDIKAN AGAMA ISLAM',
            'bab2_content' => $brokenBab2,
            'bab3_content' => $brokenBab3,
            'is_full_unlocked' => true,
        ]);

        // Membuka /admin langsung merapikan proyek Mhd Andre satu kali, tanpa menyentuh proyek user lain
        $this->post('/admin/login', ['pin' => 'admin123'])->assertRedirect(route('admin.dashboard'));
        $this->get('/admin')->assertStatus(200);

        $otherFresh = $otherProject->fresh();
        $this->assertStringNotContainsString('| No | Kegiatan |', (string) $otherFresh->bab3_content);

        $andreFresh = $andreProject->fresh();
        $bab2 = (string) $andreFresh->bab2_content;
        $bab3 = (string) $andreFresh->bab3_content;

        // Teks editan lain 100% utuh
        $this->assertStringContainsString('Paragraf editan khusus user Andre tetap harus aman utuh 100%.', $bab2);
        $this->assertStringContainsString('Hipotesis nol menyatakan tidak ada pengaruh signifikan.', $bab2);
        $this->assertStringContainsString('Angket tertutup diberikan kepada siswa.', $bab3);

        // Semua tabel & flowchart pulih rapi dalam format Markdown
        $this->assertStringContainsString('| No | Peneliti & Tahun | Judul Penelitian | Model / Metode | Temuan / Peningkatan | Posisi Penelitian Ini |', $bab2);
        $this->assertStringContainsString("Gambar 2.1 Bagan Kerangka Berpikir Tindakan Pembelajaran\n```\n[Kondisi Awal:", $bab2);
        $this->assertStringContainsString('| No | Kegiatan Penelitian | Bulan ke-1 | Bulan ke-2 | Bulan ke-3 | Bulan ke-4 |', $bab3);
        $this->assertStringContainsString('| No | Rombongan Belajar | Jumlah Populasi (Nᵢ) | Perhitungan Sampel | Jumlah Sampel (nᵢ) |', $bab3);
        $this->assertStringContainsString('| Variabel | Definisi Operasional | Dimensi | Indikator Empiris | Butir | Skala |', $bab3);
        $this->assertStringContainsString("Gambar 3.1 Diagram Alur Prosedur Penelitian\n```\n[MULAI]\n↓", $bab3);

        // Flag satu-kali mencegah penimpaan ulang jika nanti Andre menghapus/mengedit sesuatu lagi
        $meta = json_decode((string) $andreFresh->citation_validation, true);
        $this->assertTrue(!empty($meta[\App\Services\TargetedStructureRepairService::META_FLAG]));
    }

    public function test_multi_major_and_method_prompts_follow_standards_and_form_settings_without_breaking_existing_majors(): void
    {
        $user = User::factory()->create();
        $ctrl = app(GeneratorController::class);
        $promptBab = new ReflectionMethod($ctrl, 'promptBab');
        $promptBab->setAccessible(true);

        $makeProject = function (array $attrs) use ($user): Project {
            $meta = array_merge([
                'tahap_pengerjaan' => 'Proposal Skripsi (Bab I–III)',
                'sitasi' => ['gaya' => 'APA 7th', 'tahun_min' => '2022', 'asal' => 'Indonesia + Internasional', 'bahasa' => 'Indonesia', 'sumber' => ['Terindeks Sinta']],
                'verified_refs' => [['formatted' => 'Contoh, A. (2025). Artikel Ilmiah. Jurnal Sains, 1(1), 1-10. https://doi.org/10.1000/test']],
            ], $attrs['meta'] ?? []);
            unset($attrs['meta']);

            return Project::create(array_merge([
                'user_id' => $user->id,
                'judul' => 'Judul Uji',
                'prodi' => 'Manajemen',
                'metode_penelitian' => 'Kuantitatif (Survei / Regresi)',
                'citation_validation' => json_encode($meta, JSON_UNESCAPED_UNICODE),
            ], $attrs));
        };

        // 1. Ilmu Komputer - Perancangan Sistem Web (TIDAK boleh dipaksa Text Mining / TF-IDF / Confusion Matrix)
        $webSys = $makeProject([
            'judul' => 'Perancangan sistem di kampung parouto dengan berbasis web',
            'prodi' => 'ILMU KOMPUTER',
            'metode_penelitian' => 'Kuantitatif (Survei / Regresi)',
        ]);
        $webBab2 = (string) $promptBab->invoke($ctrl, 'bab2', $webSys);
        $webBab3 = (string) $promptBab->invoke($ctrl, 'bab3', $webSys);
        $webBab4 = (string) $promptBab->invoke($ctrl, 'bab4', $webSys);
        $this->assertStringContainsString('Black-Box Testing', $webBab2);
        $this->assertStringContainsString('PENGEMBANGAN SISTEM / R&D', $webBab3);
        $this->assertStringContainsString('Black-Box Testing', $webBab4);
        $this->assertStringNotContainsString('Text/Data Preprocessing', $webBab2);
        $this->assertStringNotContainsString('Tabel 3.2 Struktur Confusion Matrix', $webBab3);

        // 2. Teknik Informatika - Machine Learning / Sentimen (tetap pakai TF-IDF & Confusion Matrix)
        $mlProj = $makeProject([
            'judul' => 'Pengembangan Modul Klasifikasi Multikelas Komentar TikTok dengan Algoritma Naive Bayes',
            'prodi' => 'Teknik Informatika',
            'metode_penelitian' => 'Kuantitatif (Survei / Regresi)',
        ]);
        $mlBab3 = (string) $promptBab->invoke($ctrl, 'bab3', $mlProj);
        $mlBab4 = (string) $promptBab->invoke($ctrl, 'bab4', $mlProj);
        $this->assertStringContainsString('TF-IDF', $mlBab3);
        $this->assertStringContainsString('Confusion Matrix', $mlBab4);

        // 3. S1 Keperawatan - Hubungan Stres & Hipertensi (Bab IV wajib Univariat & Bivariat Klinis)
        $nurseProj = $makeProject([
            'judul' => 'Hubungan tingkat stres dengan tekanan darah pada pasien hipertensi di wilayah kerja puskesmas x',
            'prodi' => 'S1 Keperawatan',
            'metode_penelitian' => 'Kuantitatif (Survei / Regresi)',
        ]);
        $nurseBab2 = (string) $promptBab->invoke($ctrl, 'bab2', $nurseProj);
        $nurseBab3 = (string) $promptBab->invoke($ctrl, 'bab3', $nurseProj);
        $nurseBab4 = (string) $promptBab->invoke($ctrl, 'bab4', $nurseProj);
        $this->assertStringContainsString('Kerangka Teori dan Kerangka Konsep', $nurseBab2);
        $this->assertStringContainsString('Ethical Clearance', $nurseBab3);
        $this->assertStringContainsString('Analisis Bivariat', $nurseBab4);
        $this->assertStringContainsString('Chi-Square', $nurseBab4);

        // 4. Sosiologi FISIP - Kualitatif (Bab II tanpa hipotesis statistik H0/H1, Bab III/IV kualitatif Miles-Huberman & Triangulasi)
        $socProj = $makeProject([
            'judul' => 'Dinamika Sosial dalam Tata Kelola Birokrasi pada Sistem Pemasyarakatan',
            'prodi' => 'PROGRAM STUDI SOSIOLOGI FISIP',
            'metode_penelitian' => 'Kualitatif (Studi Kasus / Deskriptif)',
        ]);
        $socBab2 = (string) $promptBab->invoke($ctrl, 'bab2', $socProj);
        $socBab3 = (string) $promptBab->invoke($ctrl, 'bab3', $socProj);
        $socBab4 = (string) $promptBab->invoke($ctrl, 'bab4', $socProj);
        $this->assertStringContainsString('DILARANG menuliskan hipotesis statistik H0/H1', $socBab2);
        $this->assertStringContainsString('Miles, Huberman & Saldana', $socBab3);
        $this->assertStringContainsString('Profil Informan', $socBab4);
        $this->assertStringContainsString('Triangulasi', $socBab4);

        // 5. Pendidikan Agama Islam - PTK & Pengaturan Form (struktur_kustom, pedoman_text, data_awal)
        $paiPtk = $makeProject([
            'judul' => 'Implementasi Strategi Pembelajaran Berdiferensiasi PAI',
            'prodi' => 'PENDIDIKAN AGAMA ISLAM',
            'metode_penelitian' => 'PTK (Penelitian Tindakan Kelas)',
            'meta' => [
                'tahap_pengerjaan' => 'Proposal Skripsi (Bab I–III)',
                'data_awal' => 'Ketuntasan siswa awal 49% dengan rata-rata nilai 70.',
                'struktur_kustom' => '2.1 Landasan Teori PAI, 2.2 Kajian Pustaka',
            ],
        ]);
        $paiBab2 = (string) $promptBab->invoke($ctrl, 'bab2', $paiPtk);
        $paiBab4 = (string) $promptBab->invoke($ctrl, 'bab4', $paiPtk);
        $this->assertStringContainsString('PENDIDIKAN AGAMA ISLAM', $paiBab2);
        $this->assertStringContainsString('2.1 Landasan Teori PAI', $paiBab2);
        $this->assertStringContainsString('Siklus I', $paiBab4);
        $this->assertStringContainsString('Siklus II', $paiBab4);

        // 6. Hukum Empiris & Teknik Sipil
        $lawProj = $makeProject([
            'judul' => 'Efektivitas Penegakan Peraturan Daerah Pengelolaan Sampah',
            'prodi' => 'Ilmu Pemerintahan',
            'metode_penelitian' => 'Hukum Empiris',
        ]);
        $this->assertStringContainsString('Yuridis Empiris', (string) $promptBab->invoke($ctrl, 'bab3', $lawProj));
        $this->assertStringContainsString('PEMBAHASAN YURIDIS', (string) $promptBab->invoke($ctrl, 'bab4', $lawProj));

        $civilProj = $makeProject([
            'judul' => 'Analisis Kuat Tekan Beton dengan Substitusi Abu Terbang',
            'prodi' => 'Teknik Sipil',
            'metode_penelitian' => 'Kuantitatif (Survei / Regresi)',
        ]);
        $this->assertStringContainsString('SNI', (string) $promptBab->invoke($ctrl, 'bab2', $civilProj));
        $this->assertStringContainsString('ASTM', (string) $promptBab->invoke($ctrl, 'bab3', $civilProj));
        $this->assertStringContainsString('HASIL PENGUJIAN DAN PEMBAHASAN REKAYASA', (string) $promptBab->invoke($ctrl, 'bab4', $civilProj));

        // 7. Pastikan modal checkout responsif di mobile (100dvh & anti-duplikasi header saat invoice manual)
        $paketHtml = $this->actingAs($user)->get('/paket')->assertStatus(200)->getContent();
        $this->assertStringContainsString('id="sl-checkout-top-summary"', $paketHtml);
        $this->assertStringContainsString('calc(100dvh - 20px)', $paketHtml);
    }

    public function test_abstrak_faculty_prefix_cross_chapter_consistency_and_pdf_toc_calibration(): void
    {
        $user = User::create([
            'name' => 'Muh Alwi Syahrir',
            'email' => 'audit.konsistensi@gmail.com',
            'password' => bcrypt('Secret123!'),
            'plan_code' => 'starter',
        ]);

        $ctrl = app(\App\Http\Controllers\GeneratorController::class);
        $refCtrl = new \ReflectionClass($ctrl);
        $mFaculty = $refCtrl->getMethod('formatFacultyLabel');
        $mFaculty->setAccessible(true);
        $mAbstrak = $refCtrl->getMethod('buildDynamicAbstrakData');
        $mAbstrak->setAccessible(true);
        $mBenang = $refCtrl->getMethod('extractProjectBenangMerah');
        $mBenang->setAccessible(true);
        $mPromptBab = $refCtrl->getMethod('promptBab');
        $mPromptBab->setAccessible(true);
        $mFormatPdf = $refCtrl->getMethod('formatAcademicContentForPdf');
        $mFormatPdf->setAccessible(true);

        // 1. Fakultas prefix normalization (tidak pernah dobel)
        $this->assertSame('Fakultas Teknik', $mFaculty->invoke($ctrl, 'Teknik'));
        $this->assertSame('Fakultas Ilmu Komputer', $mFaculty->invoke($ctrl, 'Ilmu Komputer'));
        $this->assertSame('Fakultas Agama Islam', $mFaculty->invoke($ctrl, 'Fakultas Agama Islam'));
        $this->assertSame('Sekolah Teknik Elektro dan Informatika', $mFaculty->invoke($ctrl, 'Sekolah Teknik Elektro dan Informatika'));
        $this->assertSame('', $mFaculty->invoke($ctrl, '   '));

        // 2. Bangun proyek Skripsi Informatika lengkap dengan kalimat ablasi negatif sebelum headline akurasi + Bab IV panjang
        $filler = str_repeat("Analisis distribusi fitur TF-IDF dan probabilitas posterior pada empat kelas opini menunjukkan pemisahan kosakata yang konsisten antar kategori komentar kosmetik lokal. ", 35);
        $bab1 = <<<'TXT'
1.1 Latar Belakang Masalah
Volume komentar TikTok terhadap produk kosmetik lokal terus meningkat.

1.3 Batasan Masalah
1. Objek penelitian berupa komentar TikTok yang dikategorikan ke dalam empat kelas, yaitu pujian, keluhan, pertanyaan, dan saran.
2. Metode klasifikasi yang diterapkan adalah algoritma Naive Bayes dengan pembobotan TF-IDF.

1.4 Rumusan Masalah
1. Bagaimana kinerja algoritma Naive Bayes dalam mengklasifikasikan komentar TikTok ke dalam empat kelas opini?
2. Bagaimana tahapan pra-pemrosesan teks yang efektif?
3. Bagaimana modul klasifikasi berbasis web diimplementasikan dan diuji secara fungsional?
TXT;
        $bab3 = <<<'TXT'
3.1 Desain Penelitian dan Alur Sistem
Penelitian ini dirancang sebagai penelitian pengembangan perangkat lunak (software development research) dengan eksperimen komputasional.

Gambar 3.1 Diagram Alur (Flowchart) Prosedur Penelitian
```
[Mulai]
↓
[Pengumpulan Dataset 3.000 Komentar]
↓
[Pra-Pemrosesan dan Pembobotan TF-IDF]
↓
[Pelatihan Naive Bayes dan Pengujian]
↓
[Selesai]
```

3.3 Tahapan Pra-Pemrosesan Data
Penerapan stemming Sastrawi berhasil memadatkan kosakata dari 4.913 term menjadi 2.847 term, atau terjadi reduksi sebesar 42,05 persen.
TXT;
        $bab4 = <<<TXT
4.1 Deskripsi dan Karakteristik Dataset Penelitian
Dataset final yang layak dianalisis berjumlah 3.000 komentar berbahasa Indonesia.

4.2 Hasil Pra-Pemrosesan Teks
Setelah penerapan normalisasi slang dan stemming Sastrawi, kosakata berhasil dipadatkan dari 4.913 term menjadi 2.847 term, atau terjadi reduksi sebesar 42,05 persen. Hasil pengujian menunjukkan bahwa tanpa normalisasi slang dan stemming, akurasi model turun menjadi 81,67 persen, atau terjadi penurunan sebesar 5,66 poin persentase dibandingkan akurasi dengan pra-pemrosesan lengkap.

4.3 Hasil Pengujian dan Evaluasi Kinerja Algoritma
Tabel 4.4 Confusion Matrix Hasil Klasifikasi Multikelas pada Data Uji
| Aktual \\ Prediksi | Pujian | Keluhan | Pertanyaan | Saran | Total Aktual |
|---|---|---|---|---|---|
| Pujian | 218 | 12 | 6 | 4 | 240 |
| Keluhan | 9 | 156 | 5 | 10 | 180 |
| Pertanyaan | 4 | 3 | 88 | 5 | 100 |
| Saran | 3 | 11 | 4 | 62 | 80 |
| Total Prediksi | 234 | 182 | 103 | 81 | 600 |

{$filler}

4.4 Pembahasan Kritis Hasil Penelitian
Akurasi keseluruhan sebesar 87,33 persen pada klasifikasi empat kelas menunjukkan bahwa algoritma Naive Bayes mampu menangkap pola linguistik yang membedakan keempat kelas opini komentar TikTok terhadap produk kosmetik lokal. Validasi 5-fold cross validation menunjukkan rata-rata akurasi sebesar 86,12 persen dengan simpangan baku 1,34 persen. Hasil pengujian fungsional black-box menunjukkan bahwa seluruh skenario fungsional berhasil dijalankan dengan tingkat keberhasilan 100 persen.
TXT;

        $project = Project::create([
            'user_id' => $user->id,
            'judul' => 'Pengembangan Modul Klasifikasi Multikelas Komentar TikTok terhadap Produk Kosmetik Lokal dengan Algoritma Naive Bayes pada Aplikasi Berbasis Web',
            'prodi' => 'Teknik Informatika',
            'metode_penelitian' => 'Kuantitatif (Survei / Regresi)',
            'bab1_content' => $bab1,
            'bab2_content' => "2.1 Landasan Teori\nKlasifikasi teks dengan Naive Bayes.\n\nTabel 2.1 Sintesis Penelitian Terdahulu\n| No | Peneliti | Judul | Metode | Hasil | Celah |\n|---|---|---|---|---|---|\n| 1 | Nasution & Sriani (2026) | Emosi TikTok | LDA, SVM | 85% | Domain kesehatan mental |",
            'bab3_content' => $bab3,
            'bab4_content' => $bab4,
            'bab5_content' => "5.1 Kesimpulan\n1. Algoritma Naive Bayes mencapai akurasi keseluruhan 87,33% pada 600 data uji.\n\n5.2 Saran\nPerlu perluasan dataset kelas minoritas.",
            'daftar_pustaka' => "Nasution, L. R., & Sriani. (2026). Klasifikasi emosi komentar pengguna TikTok. Rabit, 11(2), 3786-3800. https://doi.org/10.36341/rabit.v11i2.8185",
            'is_full_unlocked' => true,
            'meta_data' => json_encode([
                'nama' => 'Muh Alwi Syahrir',
                'nim' => '210299',
                'kampus' => 'AI Universitas',
                'fakultas' => 'Teknik',
                'tahap_pengerjaan' => 'Skripsi Penuh (Bab I–V)',
                'sitasi' => ['gaya' => 'APA 7th', 'tahun_min' => '2023', 'bahasa' => 'Indonesia'],
            ]),
        ]);

        // 3. Abstrak ID & Abstract EN memilih capaian utama 87,33% (bukan ablasi 81,67%), metode tepat, tanpa spasi sebelum '%'
        $abstrak = $mAbstrak->invoke($ctrl, $project);
        $this->assertStringContainsString('87,33 persen', $abstrak['id_body']);
        $this->assertStringNotContainsString('81,67', $abstrak['id_body']);
        $this->assertStringNotContainsString('Survei / Regresi', $abstrak['id_body']);
        $this->assertStringContainsString('87.33%', $abstrak['en_body']);
        $this->assertStringNotContainsString('87.33 %', $abstrak['en_body']);

        // 3b. Abstract EN juga mendukung jurusan Kesehatan (p-value) & R&D (SUS)
        $healthProj = new Project([
            'judul' => 'Hubungan Tingkat Stres dengan Tekanan Darah pada Pasien Hipertensi',
            'prodi' => 'S1 Keperawatan',
            'metode_penelitian' => 'Kuantitatif',
            'bab3_content' => 'Penelitian ini menggunakan desain analitik observasional dengan desain cross-sectional pada 96 pasien.',
            'bab4_content' => 'Hasil uji Chi-Square menunjukkan terdapat hubungan yang signifikan antara tingkat stres dengan tekanan darah dengan nilai p-value = 0,002.',
        ]);
        $healthAbs = $mAbstrak->invoke($ctrl, $healthProj);
        $this->assertStringContainsString('0,002', $healthAbs['id_body']);
        $this->assertStringContainsString('p = 0.002', $healthAbs['en_body']);

        // 4. Benang Merah & Prompt Bab IV / Bab V mengunci angka stemming, Black-Box 100%, Batasan Masalah, Confusion Matrix, & APA surname-only
        $benang = (string) $mBenang->invoke($ctrl, $project);
        $this->assertStringContainsString('BATASAN MASALAH, NAMA KELAS & VARIABEL TERKUNCI BAB I', $benang);
        $this->assertStringContainsString('2.847 term', $benang);
        $this->assertStringContainsString('42,05 persen', $benang);
        $this->assertStringContainsString('100 persen', $benang);

        $bab4Prompt = (string) $mPromptBab->invoke($ctrl, 'bab4', $project);
        $this->assertStringContainsString('ATURAN VERIFIKASI MATEMATIKA CONFUSION MATRIX', $bab4Prompt);
        $this->assertStringContainsString('KOLOM prediksi kelas c', $bab4Prompt);
        $this->assertStringContainsString('NAMA BELAKANG (SURNAME) SAJA', $bab4Prompt);

        $bab5Prompt = (string) $mPromptBab->invoke($ctrl, 'bab5', $project);
        $this->assertStringContainsString('100 persen', $bab5Prompt);
        $this->assertStringContainsString('2.847 term', $bab5Prompt);
        $this->assertStringContainsString('DILARANG KERAS MENGARANG ATAU MENGUBAH 1 DIGIT PUN', $bab5Prompt);

        // 5. HTML PDF menandai data-toc-id pada sub-bab, tabel, dan gambar untuk kalibrasi 2-pass Dompdf
        $pdfBodyHtml = (string) $mFormatPdf->invoke($ctrl, $bab3 . "\n\n" . $bab4, $project->judul);
        $this->assertStringContainsString("data-toc-id='sub_3.1'", $pdfBodyHtml);
        $this->assertStringContainsString("data-toc-id='fig_3.1'", $pdfBodyHtml);
        $this->assertStringContainsString("data-toc-id='tbl_4.4'", $pdfBodyHtml);

        // 6. Ekspor Word (.docx) & PDF (.pdf) jalan bersih dengan "Fakultas Teknik" (tidak pernah "Dekan Teknik")
        $docxResp = $this->actingAs($user)->post("/project/{$project->id}/download/docx", [
            'nama' => 'Muh Alwi Syahrir',
            'npm' => '210299',
            'kampus' => 'AI Universitas',
            'fakultas' => 'Teknik',
        ]);
        $docxResp->assertStatus(200);
        $docxPath = $docxResp->getFile()->getPathname();
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($docxPath) === true);
        $docXml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        $this->assertStringContainsString('FAKULTAS TEKNIK', $docXml);
        $this->assertStringContainsString('Dekan Fakultas Teknik', $docXml);
        $this->assertStringNotContainsString('Dekan Teknik', $docXml);
        $this->assertStringContainsString('87,33 persen', $docXml);
        $this->assertStringNotContainsString('87.33 %', $docXml);

        ob_start();
        $pdfResp = $this->actingAs($user)->post("/project/{$project->id}/download/pdf", [
            'nama' => 'Muh Alwi Syahrir',
            'npm' => '210299',
            'kampus' => 'AI Universitas',
            'fakultas' => 'Teknik',
        ]);
        $pdfBytes = (string) ob_get_clean();
        $pdfResp->assertStatus(200);
        $this->assertStringStartsWith('%PDF-', $pdfBytes);

        // Dekompresi stream PDF dan pastikan FAKULTAS TEKNIK & nomor halaman BAB V / DAFTAR PUSTAKA terkalibrasi persis
        $decompressedPages = [];
        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdfBytes, $sm)) {
            foreach ($sm[1] as $rawStream) {
                $plain = @gzuncompress($rawStream);
                if (is_string($plain) && $plain !== '') {
                    $decompressedPages[] = $plain;
                }
            }
        }
        $joinedPdfText = implode("\n", $decompressedPages);
        $this->assertStringContainsString('FAKULTAS TEKNIK', $joinedPdfText);
        $this->assertStringContainsString('Dekan Fakultas Teknik', $joinedPdfText);
        $this->assertStringNotContainsString('Dekan Teknik', $joinedPdfText);
        $this->assertStringContainsString('87,33 persen', $joinedPdfText);
        $this->assertStringNotContainsString('87.33 %', $joinedPdfText);
    }
}
