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

    public function test_paraphrase_chunks_long_text_and_locks_citations_verbatim(): void
    {
        $user = User::factory()->create(['plan_code' => 'unlimited', 'plan_expires_at' => now()->addMonth()]);
        $this->actingAs($user);

        $calls = 0;
        $fakeAi = new class($calls) extends \App\Services\GroqService {
            public int $callCount = 0;
            public function __construct(&$ref) {
                $this->callCount = 0;
            }
            public function generateText(string $prompt, string $model = 'auto', array $images = []): string
            {
                $this->callCount++;
                // Ekstrak isi TEKS ASLI dan pastikan token [[SITASI_N]] ada, lalu kembalikan teks baru dengan token utuh
                if (preg_match('/TEKS ASLI:\s*"""\s*([\s\S]*?)\s*"""/u', $prompt, $m)) {
                    $chunk = trim($m[1]);
                    return 'Versi parafrase bagian ' . $this->callCount . ' — ' . preg_replace('/\bpenelitian\b/i', 'kajian ilmiah', $chunk);
                }
                return 'Hasil parafrase uji';
            }
        };
        $this->app->instance(\App\Services\GroqService::class, $fakeAi);

        $p1 = str_repeat('Perkembangan penelitian analisis sentimen komentar produk kosmetik lokal di platform TikTok meningkat pesat. ', 22)
            . 'Temuan ini sejalan dengan Luftia Rahma Nasution & Sriani (2026) serta Abdurohim et al. (2024).';
        $p2 = str_repeat('Selanjutnya metode penelitian klasifikasi multikelas berbasis Naive Bayes dan TF-IDF dievaluasi menyeluruh. ', 22)
            . 'Hal tersebut diperkuat oleh Yusra dan Putri (2026) serta (Wijaya & Putra, 2025).';

        $res = $this->postJson(route('tools.paraphrase'), [
            'text' => $p1 . "\n\n" . $p2,
            'style' => 'natural',
            'intensity' => 'sedang',
        ]);

        $res->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertGreaterThanOrEqual(2, $fakeAi->callCount, 'Long text must be split into multiple chunks so AI paraphrases every paragraph');
        $out = (string) $res->json('result');
        $this->assertStringContainsString('Luftia Rahma Nasution & Sriani (2026)', $out);
        $this->assertStringContainsString('Abdurohim et al. (2024)', $out);
        $this->assertStringContainsString('Yusra dan Putri (2026)', $out);
        $this->assertStringContainsString('(Wijaya & Putra, 2025)', $out);
        $this->assertStringNotContainsString('[[SITASI_', $out);
        $this->assertSame(4, $res->json('metrics.citations_detected'));
        $this->assertSame(4, $res->json('metrics.citations_preserved'));
    }

    public function test_docx_to_pdf_parser_preserves_section_breaks_and_borderless_tables(): void
    {
        $tmpDocx = tempnam(sys_get_temp_dir(), 'sect_tbl_') . '.docx';
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($tmpDocx, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            . '<w:p><w:pPr><w:jc w:val="center"/><w:sectPr/></w:pPr><w:r><w:t>SKRIPSI COVER</w:t></w:r></w:p>'
            . '<w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:t>LEMBAR PENGESAHAN</w:t></w:r></w:p>'
            . '<w:tbl><w:tblPr><w:tblBorders>'
            . '<w:top w:val="none" w:sz="0"/><w:bottom w:val="none" w:sz="0"/>'
            . '<w:left w:val="none" w:sz="0"/><w:right w:val="none" w:sz="0"/>'
            . '<w:insideH w:val="none" w:sz="0"/><w:insideV w:val="none" w:sz="0"/>'
            . '</w:tblBorders></w:tblPr>'
            . '<w:tr><w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:t>Dosen Pembimbing I</w:t></w:r></w:p></w:tc></w:tr>'
            . '</w:tbl>'
            . '</w:body></w:document>';
        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        $ctrl = app(\App\Http\Controllers\StudentToolsController::class);
        $m = new \ReflectionMethod($ctrl, 'parseDocxToHtml');
        $m->setAccessible(true);
        $html = $m->invoke($ctrl, $tmpDocx, false);
        @unlink($tmpDocx);

        $this->assertStringContainsString("<div class='page-break'></div>", $html);
        $this->assertStringContainsString("<table class='borderless'>", $html);
        $this->assertStringContainsString("<td class='td-center'>Dosen Pembimbing I</td>", $html);
    }

    public function test_cloudconvert_1to1_converter_works_for_docx_and_pdf_with_local_fallback(): void
    {
        $user = User::factory()->create(['plan_code' => 'free']);
        $this->actingAs($user);

        config(['services.converter.cloudconvert_key' => 'cc_test_key_123']);

        $fakePdfBytes = '%PDF-1.7 ' . str_repeat('A', 300);
        $fakeDocxBytes = "PK\x03\x04" . str_repeat('B', 300);

        Http::fake([
            'https://api.cloudconvert.com/v2/jobs' => Http::sequence()
                ->push([
                    'data' => [
                        'id' => 'job-docx-1',
                        'tasks' => [
                            ['name' => 'import-file', 'result' => ['form' => ['url' => 'https://upload.cc.test/docx', 'parameters' => ['token' => 'abc']]]],
                        ],
                    ],
                ], 201)
                ->push([
                    'data' => [
                        'id' => 'job-pdf-2',
                        'tasks' => [
                            ['name' => 'import-file', 'result' => ['form' => ['url' => 'https://upload.cc.test/pdf', 'parameters' => ['token' => 'xyz']]]],
                        ],
                    ],
                ], 201),
            'https://upload.cc.test/*' => Http::response('OK', 201),
            'https://sync.api.cloudconvert.com/v2/jobs/job-docx-1' => Http::response([
                'data' => [
                    'tasks' => [
                        ['name' => 'export-file', 'result' => ['files' => [['url' => 'https://storage.cc.test/result.pdf']]]],
                    ],
                ],
            ], 200),
            'https://sync.api.cloudconvert.com/v2/jobs/job-pdf-2' => Http::response([
                'data' => [
                    'tasks' => [
                        ['name' => 'export-file', 'result' => ['files' => [['url' => 'https://storage.cc.test/result.docx']]]],
                    ],
                ],
            ], 200),
            'https://storage.cc.test/result.pdf' => Http::response($fakePdfBytes, 200, ['Content-Type' => 'application/pdf']),
            'https://storage.cc.test/result.docx' => Http::response($fakeDocxBytes, 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']),
        ]);

        $tmpDocx = \Illuminate\Http\UploadedFile::fake()->createWithContent('naskah.docx', "PK\x03\x04dummy");
        $resPdf = $this->post(route('tools.convert.docxToPdf'), ['file' => $tmpDocx]);
        $resPdf->assertStatus(200);
        $this->assertSame('remote-1to1', $resPdf->headers->get('X-Converter-Engine'));
        $this->assertStringStartsWith('%PDF-1.7', $resPdf->getContent());

        $tmpPdf = \Illuminate\Http\UploadedFile::fake()->createWithContent('laporan.pdf', '%PDF-1.7 dummy');
        $resDocx = $this->post(route('tools.convert.pdfToDocx'), [
            'file' => $tmpPdf,
            'filename' => 'laporan.pdf',
            'pages' => json_encode([['pageNum' => 1, 'blocks' => [['type' => 'paragraph', 'text' => 'Fallback']]]]),
        ]);
        $resDocx->assertStatus(200);
        $this->assertSame('remote-1to1', $resDocx->headers->get('X-Converter-Engine'));
        $this->assertStringStartsWith("PK\x03\x04", $resDocx->getContent());
    }

    public function test_iloveapi_powers_docx_to_pdf_compress_merge_and_removebg(): void
    {
        $user = User::factory()->create(['plan_code' => 'free']);
        $this->actingAs($user);

        config(['services.ilovepdf.public_key' => 'project_public_test_123']);
        config(['services.ilovepdf.secret_key' => 'secret_key_test_123']);
        config(['services.converter.cloudconvert_key' => '']);

        $fakePdf = '%PDF-1.7 ' . str_repeat('X', 300);
        $fakePng = "\x89PNG\r\n\x1a\n" . str_repeat('Y', 300);

        Http::fake([
            'https://api.ilovepdf.com/v1/auth' => Http::response(['token' => 'jwt-pdf-tok'], 200),
            'https://api.iloveimg.com/v1/auth' => Http::response(['token' => 'jwt-img-tok'], 200),
            'https://api.ilovepdf.com/v1/start/*' => Http::response(['server' => 'api1.ilovepdf.com', 'task' => 'task-pdf-1'], 200),
            'https://api.iloveimg.com/v1/start/removebackground' => Http::response(['server' => 'api1.iloveimg.com', 'task' => 'task-img-1'], 200),
            'https://api1.ilovepdf.com/v1/upload' => Http::response(['server_filename' => 'srv_file.bin'], 200),
            'https://api1.iloveimg.com/v1/upload' => Http::response(['server_filename' => 'srv_img.png'], 200),
            'https://api1.ilovepdf.com/v1/process' => Http::response(['status' => 'TaskSuccess'], 200),
            'https://api1.iloveimg.com/v1/process' => Http::response(['status' => 'TaskSuccess'], 200),
            'https://api1.ilovepdf.com/v1/download/task-pdf-1' => Http::response($fakePdf, 200, ['Content-Type' => 'application/pdf']),
            'https://api1.iloveimg.com/v1/download/task-img-1' => Http::response($fakePng, 200, ['Content-Type' => 'image/png']),
        ]);

        // 1. Word -> PDF via iLovePDF officepdf
        $docx = \Illuminate\Http\UploadedFile::fake()->createWithContent('skripsi.docx', "PK\x03\x04dummy");
        $r1 = $this->post(route('tools.convert.docxToPdf'), ['file' => $docx]);
        $r1->assertStatus(200);
        $this->assertSame('remote-1to1', $r1->headers->get('X-Converter-Engine'));
        $this->assertStringStartsWith('%PDF-1.7', $r1->getContent());

        // 2. Kompres PDF via iLovePDF compress
        $pdf1 = \Illuminate\Http\UploadedFile::fake()->createWithContent('bab1.pdf', '%PDF-1.7 dummy');
        $r2 = $this->post(route('tools.pdf.compress'), ['file' => $pdf1, 'level' => 'recommended']);
        $r2->assertStatus(200);
        $this->assertSame('ilovepdf', $r2->headers->get('X-Converter-Engine'));

        // 3. Gabung PDF via iLovePDF merge
        $pdf2 = \Illuminate\Http\UploadedFile::fake()->createWithContent('bab2.pdf', '%PDF-1.7 dummy2');
        $r3 = $this->post(route('tools.pdf.merge'), ['files' => [$pdf1, $pdf2], 'output_name' => 'Skripsi_Gabung']);
        $r3->assertStatus(200);
        $this->assertSame('ilovepdf', $r3->headers->get('X-Converter-Engine'));

        // 4. Hapus Background AI via iLoveIMG removebackground
        $img = \Illuminate\Http\UploadedFile::fake()->createWithContent('foto.png', $fakePng);
        $r4 = $this->post(route('tools.image.removeBg'), ['file' => $img]);
        $r4->assertStatus(200);
        $this->assertSame('iloveimg', $r4->headers->get('X-Converter-Engine'));
        $this->assertStringStartsWith("\x89PNG", $r4->getContent());
    }
}
