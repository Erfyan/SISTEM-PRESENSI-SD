<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hari_libur', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('keterangan');
            $table->enum('berlaku_untuk', ['semua', 'kelas'])->default('semua');
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->cascadeOnDelete();
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tanggal']);
        });

        Schema::create('pengaturan', function (Blueprint $table) {
            $table->string('kunci')->primary();
            $table->text('nilai')->nullable();
            $table->string('deskripsi')->nullable();
            $table->timestamps();
        });

        // Masukkan default jam batas pengingat absen kosong: 09:00
        DB::table('pengaturan')->insert([
            'kunci' => 'jam_batas_absen',
            'nilai' => '09:00',
            'deskripsi' => 'Batas jam pengingat absen kosong setiap hari sekolah',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('hari_libur');
        Schema::dropIfExists('pengaturan');
    }
};
