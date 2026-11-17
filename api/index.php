<?php

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// ─── Vercel Environment Setup ───────────────────────────────────────────────

putenv('VERCEL=1');
$_SERVER['VERCEL'] = '1';
$_ENV['VERCEL'] = '1';

// APP_KEY fallback (sebaiknya diset di Vercel Dashboard → Environment Variables)
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

// ─── Siapkan database SQLite di /tmp ────────────────────────────────────────

$targetDb = '/tmp/database.sqlite';
$sourceDb = __DIR__ . '/../database/database.sqlite';

if (!file_exists($targetDb) || filesize($targetDb) === 0) {
    if (file_exists($sourceDb) && filesize($sourceDb) > 0) {
        // Salin database yang sudah ter-migrasi & ter-seed dari repo
        @copy($sourceDb, $targetDb);
    } else {
        // Database sumber tidak ada → buat kosong, lalu migrasi runtime
        @touch($targetDb);
    }
}

// ─── Bootstrap Laravel ──────────────────────────────────────────────────────

require __DIR__ . '/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Jika database masih kosong (tidak ada tabel), jalankan migrasi
if (file_exists($targetDb) && filesize($targetDb) === 0) {
    try {
        /** @var ConsoleKernel $console */
        $console = $app->make(ConsoleKernel::class);
        $console->call('migrate', ['--force' => true]);
        $console->call('db:seed', ['--force' => true]);
    } catch (\Throwable $e) {
        error_log('[Vercel] Auto-migration error: ' . $e->getMessage());
    }
}

// ─── Tangani HTTP Request ───────────────────────────────────────────────────

$app->handleRequest(Request::capture());
