<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GeneratorController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\StudentToolsController;
use App\Http\Controllers\PlagiarismController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\PanduanController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| Web Routes - SKRIPSI AI
|--------------------------------------------------------------------------
*/

// 1. HALAMAN PUBLIK, SEO SITEMAP, PANDUAN PENGGUNA & DOWNLOAD CONTOH ASLI (.DOCX / .PDF)
Route::get('/', [GeneratorController::class, 'landingPage'])->name('home');
Route::get('/panduan', [PanduanController::class, 'index'])->name('panduan.index');
Route::get('/panduan/{slug}', [PanduanController::class, 'show'])->name('panduan.show');
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/kebijakan-privasi', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/syarat-ketentuan', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/tentang', [LegalController::class, 'about'])->name('legal.about');
Route::get('/paket', [PaymentController::class, 'plans'])->name('billing.plans');
Route::post('/demo/cari-judul', [GeneratorController::class, 'demoTitles'])
    ->middleware('throttle:12,1')
    ->name('demo.titles');
Route::post('/webhook/pakasir', [PaymentController::class, 'webhook'])->name('pakasir.webhook');
Route::post('/webhook/mayar', [PaymentController::class, 'mayarWebhook'])->name('mayar.webhook');
Route::get('/sitemap.xml', function () {
    $rawBase = rtrim(url('/'), '/');
    $baseUrl = str_contains($rawBase, 'skripsilabai.com') ? 'https://skripsilabai.com' : $rawBase;
    $today = now()->toDateString();
    $urls = [
        ['loc' => $baseUrl . '/', 'priority' => '1.0', 'changefreq' => 'daily'],
        ['loc' => $baseUrl . '/paket', 'priority' => '0.9', 'changefreq' => 'weekly'],
        ['loc' => $baseUrl . '/panduan', 'priority' => '0.9', 'changefreq' => 'weekly'],
        ['loc' => $baseUrl . '/blog', 'priority' => '0.9', 'changefreq' => 'weekly'],
        ['loc' => $baseUrl . '/kebijakan-privasi', 'priority' => '0.7', 'changefreq' => 'monthly'],
        ['loc' => $baseUrl . '/syarat-ketentuan', 'priority' => '0.7', 'changefreq' => 'monthly'],
        ['loc' => $baseUrl . '/tentang', 'priority' => '0.7', 'changefreq' => 'monthly'],
        ['loc' => $baseUrl . '/register', 'priority' => '0.8', 'changefreq' => 'monthly'],
        ['loc' => $baseUrl . '/login', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ];
    foreach (PanduanController::getGuides() as $g) {
        $urls[] = [
            'loc' => $baseUrl . '/panduan/' . $g['slug'],
            'priority' => '0.8',
            'changefreq' => 'weekly',
        ];
    }
    foreach (BlogController::getArticles() as $art) {
        $urls[] = [
            'loc' => $baseUrl . '/blog/' . $art['slug'],
            'priority' => '0.8',
            'changefreq' => 'weekly',
        ];
    }
    foreach (['manajemen', 'it', 'hukum', 'kedokteran', 'pendidikan', 'teknik'] as $major) {
        $urls[] = [
            'loc' => $baseUrl . '/sample/download/' . $major . '/pdf',
            'priority' => '0.6',
            'changefreq' => 'monthly',
        ];
    }

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $u) {
        $xml .= "  <url>\n";
        $xml .= '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
        $xml .= "    <lastmod>{$today}</lastmod>\n";
        $xml .= "    <changefreq>{$u['changefreq']}</changefreq>\n";
        $xml .= "    <priority>{$u['priority']}</priority>\n";
        $xml .= "  </url>\n";
    }
    $xml .= "</urlset>\n";

    return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
})->name('sitemap');
Route::get('/sample/download/{major}/{format}', [GeneratorController::class, 'downloadSample'])->name('sample.download');
Route::get('/galeri/project/{id}/download/{format}', [GeneratorController::class, 'downloadPublicProject'])->name('public.project.download');

// 2. AUTHENTICATION (Login, Register, Logout)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate']);
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::post('/auth/google/quick', [AuthController::class, 'quickGoogleLogin'])->name('auth.google.quick');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});

Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

// 3. APLIKASI UTAMA (DASHBOARD, WORKSPACE, TOOLS MAHASISWA & AI TOOLS — WAJIB LOGIN)
Route::middleware(['auth'])->group(function () {

    // --- HUB TOOLS MAHASISWA (PDF <-> WORD, FLOWCHART AI, GABUNG/PISAH PDF, SLOVIN, HITUNG KATA) ---
    Route::get('/tools', [StudentToolsController::class, 'index'])->name('tools.hub');
    Route::post('/tools/convert/docx-to-pdf', [StudentToolsController::class, 'docxToPdf'])->name('tools.convert.docxToPdf');
    Route::post('/tools/convert/pdf-to-docx', [StudentToolsController::class, 'pdfToDocx'])->name('tools.convert.pdfToDocx');
    Route::post('/tools/convert/extract-docx-text', [StudentToolsController::class, 'extractDocxText'])->name('tools.extract.docxText');
    Route::post('/tools/pdf/compress', [StudentToolsController::class, 'compressPdf'])->name('tools.pdf.compress');
    Route::post('/tools/pdf/merge', [StudentToolsController::class, 'mergePdf'])->name('tools.pdf.merge');
    Route::post('/tools/image/remove-bg', [StudentToolsController::class, 'removeBg'])->name('tools.image.removeBg');
    Route::post('/tools/flowchart/generate', [StudentToolsController::class, 'generateFlowchart'])->name('tools.flowchart.generate');

    // --- DASHBOARD & PROJECT MANAGEMENT ---
    Route::get('/app/create', [GeneratorController::class, 'preGeneratorForm'])->name('app.create');
    Route::get('/app/pre-generator', [GeneratorController::class, 'preGeneratorForm'])->name('app.preGenerator');
    Route::get('/project/start', function () { return redirect()->route('app.create'); });
    Route::post('/profile/update-name', [GeneratorController::class, 'updateUserName'])->name('profile.updateName');
    Route::post('/project/start', [GeneratorController::class, 'startProject'])->name('project.start');
    Route::delete('/project/{id}/delete', [GeneratorController::class, 'deleteProject'])->name('project.delete');

    // --- WORKSPACE GENERATOR (BAB 1-3 PROPOSAL / BAB 1-5 SKRIPSI) ---
    Route::get('/project/{id}', [GeneratorController::class, 'generatorIndex'])->name('app.index');
    Route::get('/project/{id}/sync-state', [GeneratorController::class, 'syncProjectState'])->name('project.syncState');
    Route::get('/project/{id}/chapters-text', [GeneratorController::class, 'getProjectChaptersText'])->name('project.chapters.text');
    Route::post('/project/{id}/generate', [GeneratorController::class, 'generateChapter'])->name('project.generate');
    Route::post('/project/{id}/revise', [GeneratorController::class, 'reviseChapter'])->name('project.revise');
    Route::post('/project/{id}/save-chapter', [GeneratorController::class, 'saveChapterManual'])->name('project.save.chapter');
    Route::post('/project/{id}/lampiran/extract', [GeneratorController::class, 'extractLampiranFile'])->name('project.lampiran.extract');
    Route::post('/project/{id}/guideline', [GeneratorController::class, 'uploadGuideline'])->name('project.guideline');
    Route::post('/project/{id}/refine-references', [GeneratorController::class, 'refineReferences'])->name('project.refine.references');
    Route::post('/project/{id}/audit', [GeneratorController::class, 'auditProject'])->name('project.audit');
    Route::post('/project/{id}/citation/replace', [GeneratorController::class, 'replaceCitation'])->name('project.citation.replace');
    Route::post('/project/{id}/references/add', [GeneratorController::class, 'addProjectReference'])->name('project.references.add');

    // --- DOWNLOAD DOCX & PDF (DENGAN LOGO KAMPUS) ---
    Route::match(['get', 'post'], '/project/{id}/download/docx', [GeneratorController::class, 'downloadDocx'])->name('app.downloadDocx');
    Route::match(['get', 'post'], '/project/{id}/download/pdf', [GeneratorController::class, 'downloadPdf'])->name('project.download.pdf');

    // --- API KEY & REWARD ---
    Route::post('/api-key/add', [GeneratorController::class, 'addApiKey'])->name('api.key.add');
    Route::delete('/api-key/delete/{id}', [GeneratorController::class, 'deleteApiKey'])->name('api.key.delete');
    Route::post('/api-key/test/{id}', [GeneratorController::class, 'testApiKey'])->name('api.key.test');
    Route::post('/reward/claim', [GeneratorController::class, 'claimReward'])->name('reward.claim');

    // --- PAKASIR PAYMENT & REFERRAL ---
    Route::post('/payment/create', [PaymentController::class, 'createTransaction'])->name('payment.create');
    Route::get('/payment/status/{orderId}', [PaymentController::class, 'checkStatus'])->name('payment.status');
    Route::post('/referral/action', [PaymentController::class, 'referralAction'])->name('referral.action');

    // --- AI TOOLS ---
    Route::post('/tools/generate-titles', [GeneratorController::class, 'generateTitles'])->name('tools.titles');
    Route::post('/tools/find-journals', [GeneratorController::class, 'findJournals'])->name('tools.journals');
    Route::post('/tools/solve-question', [GeneratorController::class, 'solveQuestion'])->name('tools.solve');
    Route::post('/tools/generate-proposal', [GeneratorController::class, 'generateProposal'])->name('tools.proposal');
    Route::post('/tools/paraphrase', [GeneratorController::class, 'paraphraseText'])->name('tools.paraphrase');
    Route::post('/tools/plagiarism/check', [PlagiarismController::class, 'check'])->name('tools.plagiarism.check');
    Route::post('/tools/plagiarism/report-pdf', [PlagiarismController::class, 'downloadReportPdf'])->name('tools.plagiarism.pdf');
    Route::post('/tools/stats/analyze', [StatisticsController::class, 'analyze'])->name('tools.stats.analyze');
    Route::post('/tools/stats/interpret', [StatisticsController::class, 'interpret'])->name('tools.stats.interpret');
    Route::post('/tools/stats/instrument', [StatisticsController::class, 'generateInstrument'])->name('tools.stats.instrument');
    Route::post('/tools/stats/report-pdf', [StatisticsController::class, 'downloadReportPdf'])->name('tools.stats.pdf');
    Route::post('/tools/stats/report-docx', [StatisticsController::class, 'downloadReportDocx'])->name('tools.stats.docx');
    Route::post('/tools/generate-ppt', [GeneratorController::class, 'generatePpt'])->name('tools.ppt');
    Route::post('/tools/ppt/save', [GeneratorController::class, 'savePptDeck'])->name('tools.ppt.save');
    Route::delete('/tools/ppt/{id}', [GeneratorController::class, 'deletePptDeck'])->name('tools.ppt.delete');
    Route::post('/tools/simulate-defense', [GeneratorController::class, 'simulateDefense'])->name('tools.defense');
    Route::post('/chat/ask', [GeneratorController::class, 'askAi'])->name('chat.ask');
});

Route::get('/masuk', function () {
    if (!app()->environment('local')) {
        abort(404);
    }
    $user = \App\Models\User::where('email', 'alwi@gmail.com')->first();
    if ($user) {
        $user->password = bcrypt('admin123');
        $user->save();
        auth()->login($user, true);
        return redirect('/app/create');
    }
    return 'Akun alwi@gmail.com tidak ditemukan';
});

// 4. DEDICATED OWNER / ADMIN PANEL (MONITORING DATA & JUMLAH PENGGUNA)
Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');
Route::get('/admin/users', [AdminController::class, 'index'])->name('admin.users');
Route::post('/admin/login', [AdminController::class, 'login'])->name('admin.login');
Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');
Route::post('/admin/users/{id}/plan', [AdminController::class, 'updateUserPlan'])->name('admin.users.plan');
Route::post('/admin/withdrawals/{id}/paid', [AdminController::class, 'markWithdrawalPaid'])->name('admin.withdrawals.paid');

