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
        $meta['verified_refs'] = [[
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
        ]];
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
        $this->assertStringContainsString('scaleA4SheetsToViewport', $wsHtml);
        $this->assertStringContainsString('measureContainerWithNode', $wsHtml);
        $this->assertStringContainsString('padding: 4cm 3cm 3cm 4cm;', $wsHtml);
        $this->assertStringContainsString('width: 794px;', $wsHtml);
        $this->assertSame(
            preg_match_all('/<script\b/i', $wsHtml),
            preg_match_all('/<\/script>/i', $wsHtml),
            'Every <script> tag in generator workspace HTML must be closed properly.'
        );
    }
}
