<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GrowthUpgradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_cari_judul_and_cari_jurnal_are_unlimited_across_all_plans_while_ppt_and_defense_remain_metered(): void
    {
        $billing = app(BillingService::class);
        $freeUser = User::factory()->create(['plan_code' => 'free']);

        // Cari Judul & Cari Jurnal harus tetap diizinkan berulang kali di paket Free
        for ($i = 0; $i < 5; $i++) {
            $resTitles = $billing->reserveToolAccess($freeUser, 'titles', 'Generator Ide Judul');
            $resJournals = $billing->reserveToolAccess($freeUser, 'journals', 'Pencari Jurnal Ilmiah');
            $this->assertTrue($resTitles['allowed']);
            $this->assertFalse($resTitles['reserved']);
            $this->assertTrue($resJournals['allowed']);
            $this->assertFalse($resJournals['reserved']);
        }

        // PPT Sidang & Simulasi Sidang tetap 1x pada akun Free
        $this->assertTrue($billing->reserveToolAccess($freeUser, 'ppt', 'Generate PPT Sidang')['allowed']);
        $pptSecond = $billing->reserveToolAccess($freeUser, 'ppt', 'Generate PPT Sidang');
        $this->assertFalse($pptSecond['allowed']);
        $this->assertSame('free_tool_limit', $pptSecond['reason']);

        $this->assertTrue($billing->reserveToolAccess($freeUser, 'defense', 'Simulasi Sidang AI')['allowed']);
        $defSecond = $billing->reserveToolAccess($freeUser, 'defense', 'Simulasi Sidang AI');
        $this->assertFalse($defSecond['allowed']);
        $this->assertSame('free_tool_limit', $defSecond['reason']);

        // Pada paket Starter: Cari Judul & Jurnal Unlimited, PPT Sidang tetap 2x
        $starterUser = User::factory()->create(['plan_code' => 'free']);
        $billing->activatePackageForUser($starterUser, 'starter');
        $starterUser->refresh();

        $this->assertTrue($billing->reserveToolAccess($starterUser, 'titles', 'Generator Ide Judul')['allowed']);
        $this->assertTrue($billing->reserveToolAccess($starterUser, 'journals', 'Pencari Jurnal Ilmiah')['allowed']);
        $this->assertTrue($billing->reserveToolAccess($starterUser, 'ppt', 'Generate PPT Sidang')['allowed']);
        $this->assertTrue($billing->reserveToolAccess($starterUser, 'ppt', 'Generate PPT Sidang')['allowed']);
        $pptThird = $billing->reserveToolAccess($starterUser, 'ppt', 'Generate PPT Sidang');
        $this->assertFalse($pptThird['allowed']);
        $this->assertSame('paid_tool_limit', $pptThird['reason']);
    }

    public function test_referral_signup_grants_bonus_for_paraphrase_and_stats_but_not_ppt_or_defense(): void
    {
        $referrer = User::factory()->create([
            'plan_code' => 'free',
            'referral_code' => 'SKRIPSI-REFBNS',
        ]);

        $this->post('/register', [
            'name' => 'Teman Kuliah',
            'email' => 'teman.kuliah@gmail.com',
            'password' => 'Rahasia123!',
            'password_confirmation' => 'Rahasia123!',
            'referral_code' => 'SKRIPSI-REFBNS',
        ])->assertRedirect(route('app.create'));

        $referrer->refresh();
        $billing = app(BillingService::class);
        $usage = $billing->getFreeToolUsage($referrer);
        $this->assertSame(1, $usage['_ref_bonus_slots'] ?? 0);

        // Parafrase mendapat 1 + 1 = 2x pada Free
        $this->assertTrue($billing->reserveToolAccess($referrer, 'paraphrase', 'Parafrase')['allowed']);
        $this->assertTrue($billing->reserveToolAccess($referrer, 'paraphrase', 'Parafrase')['allowed']);
        $this->assertFalse($billing->reserveToolAccess($referrer, 'paraphrase', 'Parafrase')['allowed']);

        // PPT Sidang tetap hanya 1x
        $this->assertTrue($billing->reserveToolAccess($referrer, 'ppt', 'PPT')['allowed']);
        $this->assertFalse($billing->reserveToolAccess($referrer, 'ppt', 'PPT')['allowed']);
    }

    public function test_demo_titles_and_prefill_onboarding_work(): void
    {
        Http::fake([
            '*' => Http::response(['error' => ['message' => 'offline fallback test']], 503),
        ]);

        // Demo widget endpoint mengembalikan 3 judul + rumusan masalah meski AI eksternal offline
        $demo = $this->postJson('/demo/cari-judul', [
            'topik' => 'TikTok Live UMKM',
            'prodi' => 'Manajemen',
            'metode' => 'Kuantitatif (Survei / Regresi)',
        ]);
        $demo->assertStatus(200)
            ->assertJson(['status' => 'success', 'prodi' => 'Manajemen'])
            ->assertJsonCount(3, 'titles');

        // Prefill dari landing widget terbawa ke register -> app/create
        $this->get('/register?prefill_judul=Judul+Skripsi+Pilihan&prefill_prodi=Manajemen&prefill_metode=Kuantitatif')
            ->assertStatus(200);
        $this->assertSame('Judul Skripsi Pilihan', session('pending_prefill_project.judul'));

        $this->post('/register', [
            'name' => 'Mahasiswa Demo',
            'email' => 'mahasiswa.demo@gmail.com',
            'password' => 'Rahasia123!',
            'password_confirmation' => 'Rahasia123!',
        ])->assertRedirect(route('app.create'));

        $this->get('/app/create')
            ->assertStatus(200)
            ->assertSee('Judul Skripsi Pilihan');
    }

    public function test_pdf_to_docx_escapes_xml_special_characters_for_valid_word_output(): void
    {
        $user = User::factory()->create(['plan_code' => 'free']);
        $this->actingAs($user);

        $res = $this->postJson(route('tools.convert.pdfToDocx'), [
            'filename' => 'Laporan_Olah_Data_Statistik-2.pdf',
            'academic_format' => true,
            'pages' => [
                [
                    'pageNum' => 1,
                    'blocks' => [
                        ['type' => 'heading', 'text' => 'BAB IV HASIL & PEMBAHASAN'],
                        ['type' => 'paragraph', 'text' => 'Nilai Sig = 0.000 < 0.05 dan R2 = 68.4% > 0.50 menunjukkan pengaruh signifikan.'],
                    ],
                ],
            ],
        ]);

        $res->assertStatus(200);
        $filePath = $res->getFile()->getPathname();
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($filePath) === true);
        $docXml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertNotFalse($docXml);
        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($docXml), 'word/document.xml must be valid XML');
        $this->assertStringContainsString('HASIL &amp; PEMBAHASAN', $docXml);
        $this->assertStringContainsString('&lt; 0.05', $docXml);
    }

    public function test_docx_flowchart_renders_clean_image_inside_valid_word_package(): void
    {
        $user = User::factory()->create(['plan_code' => 'unlimited', 'plan_expires_at' => now()->addMonth()]);
        $this->actingAs($user);

        $project = \App\Models\Project::create([
            'user_id' => $user->id,
            'judul' => 'Klasifikasi Sentimen Komentar TikTok Menggunakan Naive Bayes',
            'prodi' => 'Teknik Informatika',
            'metode_penelitian' => 'Kuantitatif',
            'is_full_unlocked' => true,
            'bab3_content' => "3.1 Prosedur Penelitian\n\n```\n[Mulai]\n↓\n[Pengumpulan Dataset & Normalisasi]\n↓\n[Validasi 5-Fold Cross Validation?]\n↓\n[Evaluasi Confusion Matrix]\n↓\n[Selesai]\n```\n\nGambar 3.1 Diagram Alur Prosedur Penelitian",
        ]);

        $res = $this->get(route('app.downloadDocx', ['id' => $project->id]));
        $res->assertStatus(200);

        $filePath = $res->getFile()->getPathname();
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($filePath) === true);
        $docXml = $zip->getFromName('word/document.xml');
        $hasFlowchartMedia = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (str_starts_with($zip->getNameIndex($i), 'word/media/')) {
                $hasFlowchartMedia = true;
                break;
            }
        }
        $zip->close();

        $this->assertNotFalse($docXml);
        $this->assertTrue($hasFlowchartMedia, 'Flowchart PNG image must be embedded inside word/media/');
        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($docXml), 'DOCX document.xml must remain valid XML');
    }
}
