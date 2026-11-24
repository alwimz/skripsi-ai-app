<?php

// Pengalihan permanen (HTTP 301) dari domain lama Vercel (muhalwisyahrir.tech / *.vercel.app) ke skripsilabai.com
$host = strtolower(explode(':', (string) ($_SERVER['HTTP_HOST'] ?? ''))[0]);
$rawUri = str_replace(["\r", "\n", "\0"], '', (string) ($_SERVER['REQUEST_URI'] ?? '/'));
$cleanUri = preg_replace('#^/+#', '/', $rawUri);
if (!is_string($cleanUri) || $cleanUri === '' || $cleanUri[0] !== '/') {
    $cleanUri = '/' . ltrim((string) $cleanUri, '/');
}

if ($host !== 'skripsilabai.com' && $host !== 'www.skripsilabai.com') {
    header('Location: https://skripsilabai.com' . $cleanUri, true, 301);
    header('Cache-Control: public, max-age=300');
    exit;
}

// Fallback keamanan bila skripsilabai.com sempat diarahkan ke sini: teruskan ke public/index.php
require __DIR__ . '/../public/index.php';