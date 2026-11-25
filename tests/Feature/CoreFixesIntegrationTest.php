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
        $invJson = json_encode($invResp->json('invoice'));
        $this->assertStringContainsString('082195036971', (string) $invJson);
        $this->assertStringContainsString('901889425034', (string) $invJson);
        $this->assertStringContainsString('Muh Alwi Syahrir', (string) $invJson);

        $this->actingAs($buyer)->get('/paket')
            ->assertStatus(200)
            ->assertSee('slSelectInvoiceMethod', false)
            ->assertSee('sl-invoice-tabs', false);
    }
}
