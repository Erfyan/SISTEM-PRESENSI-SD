<?php

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

define('LARAVEL_START', microtime(true));

// ─── Layani file statis dari folder public secara mandiri ─────────────────────
$publicDir = realpath(__DIR__ . '/../public');
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));

if ($uri !== '/' && $publicDir) {
    $filePath = realpath($publicDir . $uri);
    if ($filePath && is_file($filePath) && str_starts_with($filePath, $publicDir)) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'css'         => 'text/css; charset=utf-8',
            'js'          => 'application/javascript; charset=utf-8',
            'mjs'         => 'application/javascript; charset=utf-8',
            'json'        => 'application/json; charset=utf-8',
            'webmanifest' => 'application/manifest+json; charset=utf-8',
            'png'         => 'image/png',
            'jpg'         => 'image/jpeg',
            'jpeg'        => 'image/jpeg',
            'gif'         => 'image/gif',
            'ico'         => 'image/x-icon',
            'svg'         => 'image/svg+xml',
            'woff'        => 'font/woff',
            'woff2'       => 'font/woff2',
            'ttf'         => 'font/ttf',
            'txt'         => 'text/plain',
        ];

        $mime = $mimeTypes[$ext] ?? (function_exists('mime_content_type') ? mime_content_type($filePath) : 'application/octet-stream');

        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=31536000, immutable');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
}

// ─── Vercel Environment Setup ───────────────────────────────────────────────

putenv('VERCEL=1');
$_SERVER['VERCEL'] = '1';
$_ENV['VERCEL'] = '1';

// APP_KEY fallback jika belum diset di Vercel Dashboard
if (empty($_SERVER['APP_KEY']) && empty($_ENV['APP_KEY']) && !getenv('APP_KEY')) {
    $key = 'base64:6dLudkxaS66sd6X6EIgKXNzVg6aoupfjR5qaG8+xpR4=';
    putenv("APP_KEY={$key}");
    $_ENV['APP_KEY'] = $key;
    $_SERVER['APP_KEY'] = $key;
}

// APP_URL otomatis dari VERCEL_URL jika belum diset
if (empty($_SERVER['APP_URL']) && empty($_ENV['APP_URL']) && !getenv('APP_URL')) {
    $vercelUrl = $_SERVER['VERCEL_URL'] ?? $_ENV['VERCEL_URL'] ?? getenv('VERCEL_URL') ?: null;
    if ($vercelUrl) {
        $appUrl = 'https://' . $vercelUrl;
        putenv("APP_URL={$appUrl}");
        $_ENV['APP_URL'] = $appUrl;
        $_SERVER['APP_URL'] = $appUrl;
    }
}

// ─── Siapkan direktori writable di /tmp ─────────────────────────────────────

$tmpDirs = [
    '/tmp/storage/app/public',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/framework/views',
    '/tmp/storage/logs',
    '/tmp/bootstrap/cache',
];

foreach ($tmpDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// ─── Bootstrap Laravel ──────────────────────────────────────────────────────

require __DIR__ . '/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Inisialisasi Console Kernel untuk mendaftarkan Facades & Providers
/** @var ConsoleKernel $console */
$console = $app->make(ConsoleKernel::class);
$console->bootstrap();

// Auto-Setup Database Supabase: Jika tabel 'users' belum ada, otomatis migrasi & seed
try {
    if (!Schema::hasTable('users')) {
        $console->call('migrate', ['--force' => true]);
        $console->call('db:seed', ['--force' => true]);
    }
} catch (\Throwable $e) {
    error_log('[Supabase Setup] ' . $e->getMessage());
}

// ─── Tangani HTTP Request ───────────────────────────────────────────────────

$app->handleRequest(Request::capture());
