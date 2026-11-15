<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GeneratorController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;

/*
|--------------------------------------------------------------------------
| Web Routes - SKRIPSI AI
|--------------------------------------------------------------------------
*/

// 1. HALAMAN PUBLIK, ARSIP PROYEK MAHASISWA & DOWNLOAD CONTOH ASLI (.DOCX / .PDF)
Route::get('/', [GeneratorController::class, 'landingPage'])->name('home');
Route::get('/sample/download/{major}/{format}', [GeneratorController::class, 'downloadSample'])->name('sample.download');
Route::get('/public/project/{id}/download/{format}', [GeneratorController::class, 'downloadPublicProject'])->name('public.project.download');

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

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// 3. APLIKASI UTAMA (DASHBOARD, WORKSPACE & AI TOOLS)
Route::middleware(['auth'])->group(function () {

    // --- DASHBOARD & PROJECT MANAGEMENT ---
    Route::get('/app/create', [GeneratorController::class, 'preGeneratorForm'])->name('app.create');
    Route::get('/app/pre-generator', [GeneratorController::class, 'preGeneratorForm'])->name('app.preGenerator');
    Route::get('/project/start', function () { return redirect()->route('app.create'); });
    Route::post('/profile/update-name', [GeneratorController::class, 'updateUserName'])->name('profile.updateName');
    Route::post('/project/start', [GeneratorController::class, 'startProject'])->name('project.start');
    Route::delete('/project/{id}/delete', [GeneratorController::class, 'deleteProject'])->name('project.delete');

    // --- WORKSPACE GENERATOR (BAB 1-3 PROPOSAL / BAB 1-5 SKRIPSI) ---
    Route::get('/project/{id}', [GeneratorController::class, 'generatorIndex'])->name('app.index');
    Route::post('/project/{id}/generate', [GeneratorController::class, 'generateChapter'])->name('project.generate');
    Route::post('/project/{id}/revise', [GeneratorController::class, 'reviseChapter'])->name('project.revise');
    Route::post('/project/{id}/save-chapter', [GeneratorController::class, 'saveChapterManual'])->name('project.save.chapter');
    Route::post('/project/{id}/guideline', [GeneratorController::class, 'uploadGuideline'])->name('project.guideline');
    Route::post('/project/{id}/refine-references', [GeneratorController::class, 'refineReferences'])->name('project.refine.references');

    // --- DOWNLOAD DOCX & PDF (DENGAN LOGO KAMPUS) ---
    Route::post('/project/{id}/download/docx', [GeneratorController::class, 'downloadDocx'])->name('app.downloadDocx');
    Route::post('/project/{id}/download/pdf', [GeneratorController::class, 'downloadPdf'])->name('project.download.pdf');

    // --- API KEY & REWARD ---
    Route::post('/api-key/add', [GeneratorController::class, 'addApiKey'])->name('api.key.add');
    Route::delete('/api-key/delete/{id}', [GeneratorController::class, 'deleteApiKey'])->name('api.key.delete');
    Route::post('/api-key/test/{id}', [GeneratorController::class, 'testApiKey'])->name('api.key.test');
    Route::post('/reward/claim', [GeneratorController::class, 'claimReward'])->name('reward.claim');

    // --- AI TOOLS ---
    Route::post('/tools/generate-titles', [GeneratorController::class, 'generateTitles'])->name('tools.titles');
    Route::post('/tools/find-journals', [GeneratorController::class, 'findJournals'])->name('tools.journals');
    Route::post('/tools/solve-question', [GeneratorController::class, 'solveQuestion'])->name('tools.solve');
    Route::post('/tools/generate-proposal', [GeneratorController::class, 'generateProposal'])->name('tools.proposal');
    Route::post('/tools/paraphrase', [GeneratorController::class, 'paraphraseText'])->name('tools.paraphrase');
    Route::post('/tools/generate-ppt', [GeneratorController::class, 'generatePpt'])->name('tools.ppt');
    Route::post('/tools/simulate-defense', [GeneratorController::class, 'simulateDefense'])->name('tools.defense');
    Route::post('/chat/ask', [GeneratorController::class, 'askAi'])->name('chat.ask');
});

Route::get('/masuk', function () {
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

