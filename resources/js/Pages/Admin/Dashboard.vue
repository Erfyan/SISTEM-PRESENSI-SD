<template>
  <AppLayout>
    <div class="space-y-6 max-w-6xl mx-auto">
      
      <!-- Welcome Header -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
            <i class="fa-solid fa-gauge-high text-teal-600"></i>
            <span>Dashboard & Overview Presensi Sekolah</span>
          </h1>
          <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
            <i class="fa-regular fa-calendar-check text-slate-400"></i>
            Status pemantauan kehadiran seluruh kelas hari ini: <span class="font-bold text-slate-800">{{ tanggal }}</span>
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Link
            href="/rekap"
            class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-2"
          >
            <i class="fa-solid fa-chart-pie"></i>
            <span>Lihat Rekap Bulanan</span>
          </Link>
        </div>
      </div>

      <!-- Quick Stats with Font Awesome Icons -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4 hover:border-purple-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center text-xl font-bold shadow-xs">
            <i class="fa-solid fa-school text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase">Total Kelas</p>
            <p class="text-2xl font-black text-slate-900 mt-0.5">{{ totalKelas }}</p>
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4 hover:border-teal-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-teal-100 text-teal-700 flex items-center justify-center text-xl font-bold shadow-xs">
            <i class="fa-solid fa-users text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase">Total Siswa Aktif</p>
            <p class="text-2xl font-black text-slate-900 mt-0.5">{{ totalSiswa }}</p>
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4 hover:border-blue-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center text-xl font-bold shadow-xs">
            <i class="fa-solid fa-chalkboard-user text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase">Guru Terdaftar</p>
            <p class="text-2xl font-black text-slate-900 mt-0.5">{{ totalGuru }}</p>
          </div>
        </div>
      </div>

      <!-- Attendance status per class today (A3) -->
      <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
          <h2 class="font-bold text-slate-900 text-sm sm:text-base flex items-center gap-2">
            <i class="fa-solid fa-clipboard-list text-teal-600"></i>
            <span>Status Pengisian Absensi Per Kelas Hari Ini</span>
          </h2>
          <span class="text-xs text-slate-500 font-semibold px-2.5 py-1 rounded-full bg-slate-100">
            {{ kelasList.filter(k => k.terisi).length }} dari {{ kelasList.length }} Kelas Sudah Mengisi
          </span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-semibold">
              <tr>
                <th class="py-3 px-4 w-28">Kelas</th>
                <th class="py-3 px-4">Wali Kelas</th>
                <th class="py-3 px-3 text-center">Total Siswa</th>
                <th class="py-3 px-4 text-center">Status Absen</th>
                <th class="py-3 px-3 text-center text-emerald-700 font-bold bg-emerald-50/50">Hadir</th>
                <th class="py-3 px-3 text-center text-amber-700 font-bold bg-amber-50/50">Izin</th>
                <th class="py-3 px-3 text-center text-blue-700 font-bold bg-blue-50/50">Sakit</th>
                <th class="py-3 px-3 text-center text-rose-700 font-bold bg-rose-50/50">Alpa</th>
                <th class="py-3 px-4 text-center">Laporan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="k in kelasList" :key="k.id" class="hover:bg-slate-50 transition">
                <td class="py-3.5 px-4 font-bold text-slate-900">
                  Kelas {{ k.nama }}
                  <span class="block text-[10px] text-slate-400 font-normal font-mono">{{ k.tahun_ajaran }}</span>
                </td>
                <td class="py-3.5 px-4 text-slate-700 font-medium">{{ k.wali_nama }}</td>
                <td class="py-3.5 px-3 text-center font-semibold text-slate-800">{{ k.total_siswa }}</td>
                <td class="py-3.5 px-4 text-center">
                  <span
                    :class="[
                      'px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1',
                      k.terisi ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-rose-100 text-rose-800 border border-rose-300'
                    ]"
                  >
                    <i :class="k.terisi ? 'fa-solid fa-check' : 'fa-solid fa-xmark'"></i>
                    <span>{{ k.terisi ? 'Sudah Terisi' : 'Belum Terisi' }}</span>
                  </span>
                </td>
                <td class="py-3.5 px-3 text-center font-bold text-emerald-700 bg-emerald-50/20">{{ k.hadir }}</td>
                <td class="py-3.5 px-3 text-center font-bold text-amber-700 bg-amber-50/20">{{ k.izin }}</td>
                <td class="py-3.5 px-3 text-center font-bold text-blue-700 bg-blue-50/20">{{ k.sakit }}</td>
                <td class="py-3.5 px-3 text-center font-bold text-rose-700 bg-rose-50/20">{{ k.alpa }}</td>
                <td class="py-3.5 px-4 text-center">
                  <Link
                    :href="`/rekap?kelas_id=${k.id}`"
                    class="px-3 py-1 bg-teal-50 hover:bg-teal-100 text-teal-800 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1 border border-teal-200 shadow-xs"
                    title="Lihat rekap presensi kelas"
                  >
                    <i class="fa-solid fa-chart-column text-[10px]"></i>
                    <span>Lihat Rekap</span>
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  tanggal: String,
  totalKelas: Number,
  totalSiswa: Number,
  totalGuru: Number,
  kelasList: Array,
});
</script>
