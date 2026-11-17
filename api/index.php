<?php

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Set penanda lingkungan Vercel
putenv('VERCEL=1');
$_SERVER['VERCEL'] = '1';

// Fallback APP_KEY jika belum diset di Vercel Dashboard
if (!getenv('APP_KEY')) {
    putenv('APP_KEY=base64:6dLudkxaS66sd6X6EIgKXNzVg6aoupfjR5qaG8+xpR4=');
    $_ENV['APP_KEY'] = 'base64:6dLudkxaS66sd6X6EIgKXNzVg6aoupfjR5qaG8+xpR4=';
    $_SERVER['APP_KEY'] = 'base64:6dLudkxaS66sd6X6EIgKXNzVg6aoupfjR5qaG8+xpR4=';
}

// Pastikan direktori writable di /tmp siap untuk Laravel
$directories = [
    '/tmp/storage/app/public',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/framework/views',
    '/tmp/storage/logs',
    '/tmp/bootstrap/cache',
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Inisialisasi file SQLite di /tmp jika belum ada
$targetDb = '/tmp/database.sqlite';
$sourceDb = __DIR__ . '/../database/database.sqlite';

$needsMigration = false;
if (!file_exists($targetDb) || filesize($targetDb) === 0) {
    if (file_exists($sourceDb) && filesize($sourceDb) > 0) {
        @copy($sourceDb, $targetDb);
    } else {
        @touch($targetDb);
        $needsMigration = true;
    }
}

// Autoloader Composer
require __DIR__ . '/../vendor/autoload.php';

// Bootstrap aplikasi Laravel
/** @var Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Jika database baru kosong di /tmp, jalankan migrasi & seeding awal melalui Console Kernel
if ($needsMigration) {
    try {
        /** @var ConsoleKernel $consoleKernel */
        $consoleKernel = $app->make(ConsoleKernel::class);
        $consoleKernel->call('migrate', ['--force' => true]);
        $consoleKernel->call('db:seed', ['--force' => true]);
    } catch (\Throwable $e) {
        error_log('Vercel SQLite auto-migration error: ' . $e->getMessage());
    }
}

// Tangani HTTP request
$app->handleRequest(Request::capture());
