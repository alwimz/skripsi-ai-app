<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GeneratorController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes - SKRIPSI AI FINAL
|--------------------------------------------------------------------------
*/

// 1. HALAMAN PUBLIK
Route::get('/', function () {
    return view('landing'); 
})->name('home');


// 2. AUTHENTICATION (Login, Register, Logout)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate']);
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');


// 3. APLIKASI UTAMA (DASHBOARD & TOOLS)
Route::middleware(['auth'])->group(function () {

    // Tambahkan ini di dalam group middleware(['auth'])
    Route::delete('/api-key/delete/{id}', [GeneratorController::class, 'deleteApiKey'])->name('api.key.delete');

    // --- DASHBOARD & HISTORY ---
    Route::get('/app/create', [GeneratorController::class, 'preGeneratorForm'])->name('app.create');
    Route::post('/project/start', [GeneratorController::class, 'startProject'])->name('project.start');
    
    // --- WORKSPACE GENERATOR ---
    Route::get('/project/{id}', [GeneratorController::class, 'generatorIndex'])->name('app.index');
    Route::post('/project/{id}/generate', [GeneratorController::class, 'generateChapter'])->name('project.generate');

    // --- DOWNLOAD ---
    Route::post('/project/{id}/download/docx', [GeneratorController::class, 'downloadDocx'])->name('app.downloadDocx');
    Route::post('/project/{id}/download/pdf', [GeneratorController::class, 'downloadPdf'])->name('project.download.pdf');

    // --- FITUR TAMBAHAN (API KEY & REWARD) ---
    Route::post('/api-key/add', [GeneratorController::class, 'addApiKey'])->name('api.key.add');
    Route::post('/reward/claim', [GeneratorController::class, 'claimReward'])->name('reward.claim');

    // --- AI TOOLS (JUDUL, JURNAL, & CHAT) ---
    Route::post('/tools/generate-titles', [GeneratorController::class, 'generateTitles'])->name('tools.titles');
    Route::post('/tools/find-journals', [GeneratorController::class, 'findJournals'])->name('tools.journals');
    Route::post('/tools/solve-question', [GeneratorController::class, 'solveQuestion'])->name('tools.solve');
    
    // INI YANG SEBELUMNYA HILANG/ERROR:
    Route::post('/chat/ask', [GeneratorController::class, 'askAi'])->name('chat.ask');

    Route::post('/project/{id}/refine-references', [GeneratorController::class, 'refineReferences'])
    ->name('project.refine.references')
    ->middleware('auth');

    
});

Route::get('/reset-pass', function () {
    $users = \App\Models\User::select('id', 'name', 'email', 'created_at')->get();

    if ($users->isEmpty()) {
        return '<h2>Database kosong, belum ada user yang terdaftar sama sekali.</h2>';
    }

    $html = '<h2>Daftar Semua Email yang Terdaftar di Database:</h2><ol>';
    foreach ($users as $user) {
        $html .= "<li><strong>Email:</strong> {$user->email} &nbsp;|&nbsp; <strong>Nama:</strong> {$user->name} &nbsp;|&nbsp; <strong>Terdaftar Pada:</strong> {$user->created_at}</li>";
    }
    $html .= '</ol>';

    return $html;
});


