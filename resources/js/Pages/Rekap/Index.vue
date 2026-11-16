<template>
  <AppLayout>
    <div class="space-y-5 max-w-6xl mx-auto">
      
      <!-- Header & Filters -->
      <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
              Rekap Kehadiran Bulanan
            </h1>
            <p class="text-xs text-slate-500 mt-1">
              {{ kelas ? 'Kelas ' + kelas.nama + ' · Periode ' + namaBulan + ' · Hari Efektif: ' + hariEfektif + ' hari' : 'Pilih filter untuk melihat rekap' }}
            </p>
          </div>

          <!-- Controls & Export Actions -->
          <div class="flex flex-wrap items-center gap-2">
            <!-- Kelas Selector -->
            <div v-if="kelasList.length > 1">
              <select
                v-model="selectedKelasId"
                @change="refreshData"
                class="bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-teal-500"
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
                class="bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 focus:ring-2 focus:ring-teal-500"
              />
            </div>

            <!-- Export Excel Button (G4) -->
            <a
              v-if="kelas && rekap.length > 0"
              :href="`/rekap/export/excel?kelas_id=${selectedKelasId}&bulan=${selectedBulan}`"
              class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer"
            >
              <i class="fa-solid fa-file-excel text-sm"></i>
              <span>Unduh Excel</span>
            </a>

            <!-- Export PDF Button (G4) -->
            <a
              v-if="kelas && rekap.length > 0"
              :href="`/rekap/export/pdf?kelas_id=${selectedKelasId}&bulan=${selectedBulan}`"
              class="px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer"
            >
              <i class="fa-solid fa-file-pdf text-sm"></i>
              <span>Unduh PDF</span>
            </a>
          </div>
        </div>

        <!-- Metric Stat Cards -->
        <div v-if="kelas && rekap.length > 0" class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-5 pt-4 border-t border-slate-100">
          <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
            <span class="text-[11px] font-semibold text-slate-500 uppercase flex items-center gap-1">
              <i class="fa-solid fa-users text-slate-400"></i>
              Total Siswa
            </span>
            <p class="text-xl font-black text-slate-800 mt-0.5">{{ rekap.length }}</p>
          </div>
          <div class="p-3 bg-teal-50 rounded-xl border border-teal-200">
            <span class="text-[11px] font-semibold text-teal-700 uppercase flex items-center gap-1">
              <i class="fa-solid fa-calendar-check text-teal-600"></i>
              Hari Efektif Terisi
            </span>
            <p class="text-xl font-black text-teal-900 mt-0.5">{{ hariEfektif }} Hari</p>
          </div>
          <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200">
            <span class="text-[11px] font-semibold text-emerald-700 uppercase flex items-center gap-1">
              <i class="fa-solid fa-chart-line text-emerald-600"></i>
              Rata-rata Hadir
            </span>
            <p class="text-xl font-black text-emerald-900 mt-0.5">{{ rataRataKehadiran }}%</p>
          </div>
          <div class="p-3 bg-amber-50 rounded-xl border border-amber-200">
            <span class="text-[11px] font-semibold text-amber-700 uppercase flex items-center gap-1">
              <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
              Hari Belum Diisi
            </span>
            <p class="text-xl font-black text-amber-900 mt-0.5">{{ hariBelumDiisi.length }} Hari</p>
          </div>
        </div>

        <!-- Warning Hari Belum Diisi & Daftar Hari Libur Info -->
        <div v-if="kelas && (hariBelumDiisi.length > 0 || daftarHariLibur.length > 0)" class="mt-4 pt-3 border-t border-slate-100 space-y-2 text-xs">
          <div v-if="hariBelumDiisi.length > 0" class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 flex items-start gap-2.5">
            <i class="fa-solid fa-triangle-exclamation text-amber-600 shrink-0 mt-0.5 text-sm"></i>
            <div>
              <span class="font-bold">Ada {{ hariBelumDiisi.length }} hari sekolah yang belum diisi absen:</span>
              <span class="ml-1 text-amber-800">{{ hariBelumDiisi.join(', ') }}</span>
              <p class="text-[11px] text-amber-700 mt-0.5">Hari sekolah yang belum diisi tidak dihitung ke persentase kehadiran.</p>
            </div>
          </div>

          <div v-if="daftarHariLibur.length > 0" class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 flex items-start gap-2.5">
            <i class="fa-solid fa-umbrella-beach text-teal-600 shrink-0 mt-0.5 text-sm"></i>
            <div>
              <span class="font-bold">Hari Libur di Bulan Ini ({{ daftarHariLibur.length }}):</span>
              <div class="flex flex-wrap gap-1.5 mt-1">
                <span
                  v-for="l in daftarHariLibur"
                  :key="l.id"
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] bg-rose-50 text-rose-700 border border-rose-200"
                >
                  {{ l.tanggal }} ({{ l.keterangan }})
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Table Section -->
      <div v-if="kelas && rekap.length > 0" class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-semibold">
              <tr>
                <th class="py-3 px-4 w-12 text-center">No</th>
                <th class="py-3 px-4 w-24">NIS</th>
                <th class="py-3 px-4">Nama Siswa</th>
                <th class="py-3 px-3 w-12 text-center">L/P</th>
                <th class="py-3 px-3 w-16 text-center text-emerald-700 font-bold bg-emerald-50/50">Hadir</th>
                <th class="py-3 px-3 w-16 text-center text-amber-700 font-bold bg-amber-50/50">Izin</th>
                <th class="py-3 px-3 w-16 text-center text-blue-700 font-bold bg-blue-50/50">Sakit</th>
                <th class="py-3 px-3 w-16 text-center text-rose-700 font-bold bg-rose-50/50">Alpa</th>
                <th class="py-3 px-4 w-28 text-center font-bold">Kehadiran</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="(item, index) in rekap"
                :key="item.id"
                class="hover:bg-slate-50 transition"
              >
                <td class="py-3 px-4 text-center font-medium text-slate-500">{{ index + 1 }}</td>
                <td class="py-3 px-4 font-mono font-medium text-slate-700">{{ item.nis }}</td>
                <td class="py-3 px-4 font-bold text-slate-900">{{ item.nama }}</td>
                <td class="py-3 px-3 text-center">
                  <span
                    :class="[
                      'px-1.5 py-0.5 rounded text-[10px] font-bold',
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
                  <div class="flex items-center justify-center gap-1.5">
                    <div class="w-16 bg-slate-200 rounded-full h-2 hidden sm:block overflow-hidden">
                      <div
                        class="h-2 rounded-full"
                        :class="item.persentase >= 85 ? 'bg-emerald-500' : (item.persentase >= 75 ? 'bg-amber-500' : 'bg-rose-500')"
                        :style="{ width: `${item.persentase}%` }"
                      ></div>
                    </div>
                    <span
                      class="font-black text-xs"
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
      <div v-else class="p-10 bg-white rounded-2xl border border-slate-200 text-center">
        <p class="text-slate-500 text-sm">
          {{ error_message || 'Belum ada catatan absensi untuk kelas dan bulan yang dipilih.' }}
        </p>
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
