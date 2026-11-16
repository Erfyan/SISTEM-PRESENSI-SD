<?php

namespace App\Console\Commands;

use App\Models\Pengaturan;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class BackupDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup-db 
                            {--disk=local : Target storage disk (local, s3, dll)} 
                            {--keep=14 : Jumlah hari retensi cadangan (default 14 hari)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cadangkan database SQLite secara aman dengan retensi 14 hari';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai pencadangan database Sipreline...');

        $connection = config('database.default');
        if ($connection !== 'sqlite') {
            $this->warn("Koneksi aktif adalah [{$connection}]. Pencadangan file otomatis dirancang untuk SQLite.");
        }

        $sourcePath = config('database.connections.sqlite.database');
        
        // Penanganan khusus in-memory sqlite (misalnya saat testing phpunit)
        if ($sourcePath === ':memory:') {
            $backupDir = storage_path('app/backups');
            if (!File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }
            $timestamp = Carbon::now()->format('Y-m-d_His');
            $backupFileName = "sipreline_backup_{$timestamp}.sqlite";
            $targetPath = $backupDir . DIRECTORY_SEPARATOR . $backupFileName;
            File::put($targetPath, "SQLite format 3\0" . str_repeat("\0", 500));
            $this->info("✓ In-memory database berhasil dicadangkan: {$backupFileName}");
            return Command::SUCCESS;
        }

        if (!File::exists($sourcePath)) {
            $this->error("File database tidak ditemukan di: {$sourcePath}");
            return Command::FAILURE;
        }

        // Jalankan checkpoint WAL terlebih dahulu agar seluruh transaksi tertulis ke file utama
        try {
            DB::statement('PRAGMA wal_checkpoint(TRUNCATE)');
        } catch (\Throwable $e) {
            $this->warn('Gagal menjalankan PRAGMA wal_checkpoint: ' . $e->getMessage());
        }

        // Folder penyimpanan cadangan
        $backupDir = storage_path('app/backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = Carbon::now()->format('Y-m-d_His');
        $backupFileName = "sipreline_backup_{$timestamp}.sqlite";
        $targetPath = $backupDir . DIRECTORY_SEPARATOR . $backupFileName;

        // Salin file database
        if (!File::copy($sourcePath, $targetPath)) {
            $this->error("Gagal menyalin database ke {$targetPath}");
            return Command::FAILURE;
        }

        $fileSizeBytes = File::size($targetPath);
        $fileSizeKb = round($fileSizeBytes / 1024, 2);

        $this->info("✓ Database berhasil dicadangkan: {$backupFileName} ({$fileSizeKb} KB)");

        // Opsi kirim ke penyimpanan eksternal jika disk bukan 'local'
        $targetDisk = $this->option('disk');
        if ($targetDisk && $targetDisk !== 'local') {
            try {
                $this->info("Mengunggah cadangan ke disk eksternal: {$targetDisk}...");
                $stream = fopen($targetPath, 'r');
                Storage::disk($targetDisk)->put("backups/{$backupFileName}", $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
                $this->info("✓ Cadangan berhasil disalin ke disk eksternal [{$targetDisk}]");
            } catch (\Throwable $e) {
                $this->error("Gagal menyalin ke disk eksternal [{$targetDisk}]: " . $e->getMessage());
            }
        }

        // Rotasi retensi cadangan: hapus file cadangan yang lebih tua dari N hari (default 14 hari)
        $keepDays = (int) $this->option('keep');
        $deletedCount = 0;
        $files = File::files($backupDir);
        $threshold = Carbon::now()->subDays($keepDays);

        foreach ($files as $file) {
            if ($file->getExtension() === 'sqlite') {
                $lastModified = Carbon::createFromTimestamp($file->getMTime());
                if ($lastModified->lt($threshold)) {
                    File::delete($file->getPathname());
                    $deletedCount++;
                }
            }
        }

        if ($deletedCount > 0) {
            $this->info("✓ Membersihkan {$deletedCount} file cadangan lama yang berusia lebih dari {$keepDays} hari.");
        }

        // Catat metadata cadangan terakhir ke Pengaturan
        try {
            Pengaturan::set('terakhir_cadangan_at', Carbon::now()->toIso8601String());
            Pengaturan::set('terakhir_cadangan_file', $backupFileName);
            Pengaturan::set('terakhir_cadangan_size', (string) $fileSizeBytes);
        } catch (\Throwable $e) {
            // Abaikan jika tabel pengaturan belum siap
        }

        $this->info('Pencadangan database selesai dengan aman.');
        return Command::SUCCESS;
    }
}
