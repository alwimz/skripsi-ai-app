<?php

// 0. Serve Static Files directly on Vercel
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
$publicFile = __DIR__ . '/../public' . $uri;
if ($uri !== '/' && file_exists($publicFile) && !is_dir($publicFile)) {
    $ext = strtolower(pathinfo($publicFile, PATHINFO_EXTENSION));
    $mime = match($ext) {
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'txt' => 'text/plain; charset=utf-8',
        'html' => 'text/html; charset=utf-8',
        'xml' => 'application/xml; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'webmanifest' => 'application/manifest+json; charset=utf-8',
        default => 'application/octet-stream'
    };
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=86400');
    readfile($publicFile);
    exit;
}

// 1. Load Composer
require __DIR__ . '/../vendor/autoload.php';

// 2. Load Aplikasi
$app = require_once __DIR__ . '/../bootstrap/app.php';

/* =========================================================
   FIX VERCEL SERVERLESS (READ-ONLY & CACHE ISSUE)
   =========================================================
*/

// A. Paksa Storage ke /tmp (Satu-satunya folder yang bisa ditulis)
$storage = '/tmp/storage';
if (!is_dir($storage)) {
    mkdir($storage . '/framework/views', 0777, true);
    mkdir($storage . '/framework/sessions', 0777, true);
    mkdir($storage . '/framework/cache', 0777, true);
    mkdir($storage . '/logs', 0777, true);
}
$app->useStoragePath($storage);

// B. Paksa Laravel Mengabaikan File Cache Bawaan Laptop
// Kita arahkan pointer cache ke file "/tmp/..." yang pasti kosong.
// Akibatnya, Laravel akan dipaksa memuat ulang Config & Service Provider dari nol (Fresh).
$app->useEnvironmentPath('/tmp');
$_SERVER['APP_CONFIG_CACHE'] = '/tmp/config.php';
$_SERVER['APP_SERVICES_CACHE'] = '/tmp/services.php';
$_SERVER['APP_PACKAGES_CACHE'] = '/tmp/packages.php';
$_SERVER['APP_ROUTES_CACHE'] = '/tmp/routes.php';

// C. Hardcode APP_KEY & Gunakan Cookie Session agar tidak 419 Page Expired di Serverless Vercel
$key = 'base64:7nVjl4abxJYdrnyryvJBcNckBFUKI60T5YSluLwB3lE=';
putenv('APP_KEY=' . $key);
$_ENV['APP_KEY'] = $key;
$_SERVER['APP_KEY'] = $key;

putenv('SESSION_DRIVER=cookie');
$_ENV['SESSION_DRIVER'] = 'cookie';
$_SERVER['SESSION_DRIVER'] = 'cookie';

/* ========================================================= */

// 3. Jalankan Aplikasi
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);