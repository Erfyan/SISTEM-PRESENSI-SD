<template>
  <AppLayout>
    <div class="space-y-6 max-w-6xl mx-auto">
      
      <!-- Top Header & Filter Controls -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
            <i class="fa-solid fa-chart-pie text-teal-600"></i>
            <span>Rekap Kehadiran Bulanan</span>
          </h1>
          <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
            <i class="fa-regular fa-calendar-check text-slate-400"></i>
            {{ kelas ? 'Laporan Kelas ' + kelas.nama + ' · Periode ' + namaBulan + ' · Hari Efektif: ' + hariEfektif + ' hari' : 'Pilih filter untuk melihat rekap kehadiran' }}
          </p>
        </div>

        <!-- Controls & Export Actions -->
        <div class="flex flex-wrap items-center gap-2">
          <!-- Kelas Selector -->
          <div v-if="kelasList.length > 1">
            <select
              v-model="selectedKelasId"
              @change="refreshData"
              class="bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-teal-500 transition"
            >
              <option v-for="k in kelasList" :key="k.id" :value="k.id">
                Kelas {{ k.nama }} ({{ k.tahun_ajaran }})
              </option>
            </select>
          </div>

          <!-- Month Picker -->
          <div>
            <input
              type="month"
              v-model="selectedBulan"
              @change="refreshData"
              class="bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-teal-500 transition"
            />
          </div>

          <!-- Export Excel Button -->
          <a
            v-if="kelas && rekap.length > 0"
            :href="`/rekap/export/excel?kelas_id=${selectedKelasId}&bulan=${selectedBulan}`"
            class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer"
            title="Unduh laporan dalam format Microsoft Excel"
          >
            <i class="fa-solid fa-file-excel text-sm"></i>
            <span>Excel</span>
          </a>

          <!-- Export PDF Button -->
          <a
            v-if="kelas && rekap.length > 0"
            :href="`/rekap/export/pdf?kelas_id=${selectedKelasId}&bulan=${selectedBulan}`"
            class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer"
            title="Unduh laporan siap cetak format PDF"
          >
            <i class="fa-solid fa-file-pdf text-sm"></i>
            <span>PDF</span>
          </a>
        </div>
      </div>

      <!-- Quick Stats (Consistent with Admin Dashboard) -->
      <div v-if="kelas && rekap.length > 0" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4 hover:border-purple-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center text-xl font-bold shadow-xs shrink-0">
            <i class="fa-solid fa-users text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Total Siswa</p>
            <p class="text-2xl font-black text-slate-900 mt-0.5">{{ rekap.length }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Siswa terdaftar</p>
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4 hover:border-teal-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-teal-100 text-teal-700 flex items-center justify-center text-xl font-bold shadow-xs shrink-0">
            <i class="fa-solid fa-calendar-check text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Hari Efektif</p>
            <p class="text-2xl font-black text-slate-900 mt-0.5">{{ hariEfektif }} <span class="text-xs font-normal text-slate-500">Hari</span></p>
            <p class="text-[11px] text-teal-700 mt-0.5">Hari sekolah aktif</p>
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4 hover:border-emerald-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl font-bold shadow-xs shrink-0">
            <i class="fa-solid fa-chart-line text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Rata-rata Hadir</p>
            <p class="text-2xl font-black text-emerald-700 mt-0.5">{{ rataRataKehadiran }}%</p>
            <p class="text-[11px] text-emerald-700 mt-0.5">Persentase kelas</p>
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4 hover:border-amber-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-xl font-bold shadow-xs shrink-0">
            <i class="fa-solid fa-triangle-exclamation text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Belum Diisi</p>
            <p class="text-2xl font-black text-amber-700 mt-0.5">{{ hariBelumDiisi.length }} <span class="text-xs font-normal text-slate-500">Hari</span></p>
            <p class="text-[11px] text-amber-700 mt-0.5">{{ hariBelumDiisi.length === 0 ? 'Semua hari terisi lengkap' : 'Perlu diisi segera' }}</p>
          </div>
        </div>
      </div>

      <!-- Info Hari Belum Diisi & Libur -->
      <div v-if="kelas && (hariBelumDiisi.length > 0 || daftarHariLibur.length > 0)" class="space-y-3">
        <div v-if="hariBelumDiisi.length > 0" class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex items-start gap-3 shadow-xs">
          <div class="w-8 h-8 rounded-xl bg-amber-200 text-amber-800 flex items-center justify-center text-sm font-bold shrink-0">
            <i class="fa-solid fa-triangle-exclamation"></i>
          </div>
          <div>
            <p class="text-xs font-bold">Ada {{ hariBelumDiisi.length }} hari sekolah yang belum diisi absen:</p>
            <p class="text-xs text-amber-800 mt-0.5">{{ hariBelumDiisi.join(', ') }}</p>
            <p class="text-[11px] text-amber-700 mt-1">Hari sekolah yang belum diisi tidak dihitung ke dalam persentase kehadiran.</p>
          </div>
        </div>

        <div v-if="daftarHariLibur.length > 0" class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 flex items-start gap-3 shadow-xs">
          <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center text-sm font-bold shrink-0">
            <i class="fa-solid fa-umbrella-beach"></i>
          </div>
          <div>
            <p class="text-xs font-bold text-slate-800">Hari Libur di Bulan Ini ({{ daftarHariLibur.length }} hari):</p>
            <div class="flex flex-wrap gap-1.5 mt-1.5">
              <span
                v-for="l in daftarHariLibur"
                :key="l.id"
                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] bg-rose-50 text-rose-700 border border-rose-200 font-medium"
              >
                <i class="fa-regular fa-calendar text-[10px]"></i>
                {{ l.tanggal }} ({{ l.keterangan }})
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Table Section Card -->
      <div v-if="kelas && rekap.length > 0" class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <h2 class="font-bold text-slate-900 text-sm sm:text-base flex items-center gap-2">
            <i class="fa-solid fa-clipboard-list text-teal-600"></i>
            <span>Rekap Kehadiran Siswa — Kelas {{ kelas.nama }}</span>
          </h2>
          <span class="text-xs text-slate-500 font-semibold px-2.5 py-1 rounded-full bg-slate-100 inline-flex items-center gap-1.5 self-start sm:self-auto">
            <i class="fa-solid fa-users text-teal-600 text-[10px]"></i>
            {{ rekap.length }} Siswa Terdaftar
          </span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-semibold">
              <tr>
                <th class="py-3 px-4 w-12 text-center">No</th>
                <th class="py-3 px-4 w-28">NIS</th>
                <th class="py-3 px-4">Nama Siswa</th>
                <th class="py-3 px-3 w-14 text-center">L/P</th>
                <th class="py-3 px-3 w-16 text-center text-emerald-700 font-bold bg-emerald-50/50">Hadir</th>
                <th class="py-3 px-3 w-16 text-center text-amber-700 font-bold bg-amber-50/50">Izin</th>
                <th class="py-3 px-3 w-16 text-center text-blue-700 font-bold bg-blue-50/50">Sakit</th>
                <th class="py-3 px-3 w-16 text-center text-rose-700 font-bold bg-rose-50/50">Alpa</th>
                <th class="py-3 px-4 w-36 text-center font-bold">Tingkat Kehadiran</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="(item, index) in rekap"
                :key="item.id"
                class="hover:bg-slate-50 transition"
              >
                <td class="py-3 px-4 text-center font-medium text-slate-400">{{ index + 1 }}</td>
                <td class="py-3 px-4 font-mono font-medium text-slate-700">
                  <span class="px-2 py-0.5 rounded-md bg-slate-100 text-[11px]">{{ item.nis }}</span>
                </td>
                <td class="py-3 px-4 font-bold text-slate-900 text-sm">{{ item.nama }}</td>
                <td class="py-3 px-3 text-center">
                  <span
                    :class="[
                      'px-2 py-0.5 rounded-md text-[10px] font-bold inline-flex items-center justify-center',
                      item.jenis_kelamin === 'L' ? 'bg-blue-100 text-blue-700' : 'bg-pink-100 text-pink-700'
                    ]"
                  >
                    {{ item.jenis_kelamin }}
                  </span>
                </td>
                <td class="py-3 px-3 text-center font-bold text-emerald-700 bg-emerald-50/30">{{ item.hadir }}</td>
                <td class="py-3 px-3 text-center font-bold text-amber-700 bg-amber-50/30">{{ item.izin }}</td>
                <td class="py-3 px-3 text-center font-bold text-blue-700 bg-blue-50/30">{{ item.sakit }}</td>
                <td class="py-3 px-3 text-center font-bold text-rose-700 bg-rose-50/30">{{ item.alpa }}</td>
                <td class="py-3 px-4 text-center">
                  <div class="flex items-center justify-center gap-2">
                    <div class="w-16 bg-slate-200 rounded-full h-2 hidden sm:block overflow-hidden">
                      <div
                        class="h-2 rounded-full transition-all duration-300"
                        :class="item.persentase >= 85 ? 'bg-emerald-500' : (item.persentase >= 75 ? 'bg-amber-500' : 'bg-rose-500')"
                        :style="{ width: `${item.persentase}%` }"
                      ></div>
                    </div>
                    <span
                      class="font-black text-xs min-w-[42px] text-right"
                      :class="item.persentase >= 85 ? 'text-emerald-700' : (item.persentase >= 75 ? 'text-amber-700' : 'text-rose-700')"
                    >
                      {{ item.persentase }}%
                    </span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="p-12 bg-white rounded-2xl border border-slate-200 text-center shadow-xs">
        <i class="fa-regular fa-calendar-xmark text-4xl text-slate-300 mb-2 block"></i>
        <p class="text-slate-600 text-sm font-semibold">
          {{ error_message || 'Belum ada catatan absensi untuk kelas dan bulan yang dipilih.' }}
        </p>
        <p class="text-slate-400 text-xs mt-1">Silakan pilih kelas atau periode bulan lain pada filter di atas.</p>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  kelasList: {
    type: Array,
    default: () => [],
  },
  kelas: {
    type: Object,
    default: null,
  },
  bulan: {
    type: String,
    required: true,
  },
  namaBulan: {
    type: String,
    default: '',
  },
  hariEfektif: {
    type: Number,
    default: 0,
  },
  rekap: {
    type: Array,
    default: () => [],
  },
  hariBelumDiisi: {
    type: Array,
    default: () => [],
  },
  daftarHariLibur: {
    type: Array,
    default: () => [],
  },
  error_message: {
    type: String,
    default: null,
  },
});

const selectedKelasId = ref(props.kelas?.id || (props.kelasList[0]?.id ?? null));
const selectedBulan = ref(props.bulan);

const rataRataKehadiran = computed(() => {
  if (props.rekap.length === 0) return 0;
  const total = props.rekap.reduce((acc, curr) => acc + curr.persentase, 0);
  return (total / props.rekap.length).toFixed(1);
});

const refreshData = () => {
  router.get('/rekap', {
    kelas_id: selectedKelasId.value,
    bulan: selectedBulan.value,
  }, {
    preserveState: false,
    preserveScroll: true,
  });
};
</script>
