<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->string('wa_status', 25)->default('belum_dihubungi')->after('dicatat_offline_pada');
            $table->timestamp('wa_dikirim_pada')->nullable()->after('wa_status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->text('template_wa')->nullable()->after('aktif');
        });
    }

    public function down(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->dropColumn(['wa_status', 'wa_dikirim_pada']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('template_wa');
        });
    }
};
