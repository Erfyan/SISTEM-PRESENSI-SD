<template>
  <AppLayout>
    <div class="space-y-6 max-w-5xl mx-auto pb-16">
      
      <!-- Header -->
      <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
            Kalender Hari Libur Sekolah
          </h1>
          <p class="text-xs text-slate-500 mt-1">
            Hari libur tidak dihitung ke dalam hari efektif rekap bulanan siswa.
          </p>
        </div>

        <!-- Filter Tahun -->
        <div class="flex items-center gap-2">
          <label class="text-xs font-semibold text-slate-600">Tahun:</label>
          <select
            v-model="selectedTahun"
            @change="changeTahun"
            class="bg-slate-50 border border-slate-300 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-800"
          >
            <option v-for="y in [tahun - 1, tahun, tahun + 1]" :key="y" :value="y">
              {{ y }}
            </option>
          </select>
        </div>
      </div>

      <!-- Grid: Form Tambah Libur & Pengaturan Jam Batas -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Left Column: Form Tambah Hari Libur -->
        <div class="md:col-span-1 space-y-6">
          <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <h3 class="font-bold text-sm text-slate-900 mb-3 flex items-center gap-2">
              <i class="fa-solid fa-calendar-plus text-teal-600"></i>
              <span>Tambah Hari Libur</span>
            </h3>

            <form @submit.prevent="submitHariLibur" class="space-y-3.5 text-xs">
              <div>
                <label class="block font-semibold text-slate-700 mb-1">Tanggal Libur</label>
                <input
                  type="date"
                  v-model="formLibur.tanggal"
                  required
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-teal-500"
                />
              </div>

              <div>
                <label class="block font-semibold text-slate-700 mb-1">Keterangan Libur</label>
                <input
                  type="text"
                  v-model="formLibur.keterangan"
                  required
                  placeholder="Contoh: Libur Hari Raya, Ujian Sekolah"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-teal-500"
                />
              </div>

              <div v-if="isAdmin">
                <label class="block font-semibold text-slate-700 mb-1">Berlaku Untuk</label>
                <select
                  v-model="formLibur.berlaku_untuk"
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-teal-500 font-medium"
                >
                  <option value="semua">Semua Kelas</option>
                  <option value="kelas">Kelas Tertentu Saja</option>
                </select>
              </div>

              <div v-if="formLibur.berlaku_untuk === 'kelas' && kelasList.length > 0">
                <label class="block font-semibold text-slate-700 mb-1">Pilih Kelas</label>
                <select
                  v-model="formLibur.kelas_id"
                  required
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-teal-500 font-medium"
                >
                  <option v-for="k in kelasList" :key="k.id" :value="k.id">
                    Kelas {{ k.nama }}
                  </option>
                </select>
              </div>

              <button
                type="submit"
                :disabled="formLibur.processing"
                class="w-full py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-xl transition shadow-sm cursor-pointer disabled:opacity-50 flex items-center justify-center gap-1.5"
              >
                <i class="fa-solid fa-floppy-disk text-xs"></i>
                <span>{{ formLibur.processing ? 'Menyimpan...' : 'Simpan Hari Libur' }}</span>
              </button>
            </form>
          </div>

          <!-- Pengaturan Jam Batas Pengingat Absen Kosong (Khusus Admin) -->
          <div v-if="isAdmin" class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <h3 class="font-bold text-sm text-slate-900 mb-2 flex items-center gap-2">
              <i class="fa-solid fa-clock text-amber-600"></i>
              <span>Jam Batas Pengingat Absen</span>
            </h3>
            <p class="text-[11px] text-slate-500 mb-3">
              Guru akan menerima peringatan jika kelas belum diisi setelah jam ini.
            </p>

            <form @submit.prevent="submitJamBatas" class="space-y-3 text-xs">
              <div>
                <input
                  type="time"
                  v-model="formJam.jam_batas_absen"
                  required
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-mono text-center text-sm font-bold focus:bg-white"
                />
              </div>

              <button
                type="submit"
                :disabled="formJam.processing"
                class="w-full py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl transition cursor-pointer disabled:opacity-50 flex items-center justify-center gap-1.5"
              >
                <i class="fa-solid fa-check text-xs"></i>
                <span>{{ formJam.processing ? 'Menyimpan...' : 'Perbarui Jam Batas' }}</span>
              </button>
            </form>
          </div>
        </div>

        <!-- Right Column: Daftar Hari Libur -->
        <div class="md:col-span-2">
          <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
            <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
              <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-calendar-days text-teal-600"></i>
                <span>Daftar Hari Libur Tahun {{ tahun }} ({{ hariLibur.length }} Hari)</span>
              </h3>
            </div>

            <div v-if="hariLibur.length > 0" class="divide-y divide-slate-100">
              <div
                v-for="item in hariLibur"
                :key="item.id"
                class="p-4 hover:bg-slate-50 flex items-center justify-between gap-4 transition"
              >
                <div class="space-y-0.5">
                  <div class="flex items-center gap-2">
                    <span class="font-bold text-sm text-slate-900">{{ item.keterangan }}</span>
                    <span
                      :class="[
                        'px-2 py-0.5 rounded-full text-[10px] font-semibold',
                        item.berlaku_untuk === 'semua'
                          ? 'bg-purple-100 text-purple-800'
                          : 'bg-blue-100 text-blue-800'
                      ]"
                    >
                      {{ item.nama_kelas }}
                    </span>
                  </div>
                  <p class="text-xs text-slate-500">
                    {{ item.hari }}, <span class="font-semibold text-slate-700">{{ item.tanggal_indo }}</span>
                  </p>
                </div>

                <button
                  type="button"
                  @click="hapusLibur(item)"
                  class="p-2 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition cursor-pointer"
                  title="Hapus hari libur"
                >
                  <i class="fa-solid fa-trash-can text-sm"></i>
                </button>
              </div>
            </div>

            <div v-else class="p-12 text-center text-slate-400 text-xs">
              <i class="fa-regular fa-calendar-check text-3xl text-slate-300 mb-2 block"></i>
              Belum ada hari libur yang ditandai untuk tahun {{ tahun }}.
            </div>
          </div>
        </div>

      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  hariLibur: {
    type: Array,
    default: () => [],
  },
  kelasList: {
    type: Array,
    default: () => [],
  },
  tahun: {
    type: Number,
    required: true,
  },
  jamBatas: {
    type: String,
    default: '09:00',
  },
  isAdmin: {
    type: Boolean,
    default: false,
  },
});

const selectedTahun = ref(props.tahun);

const formLibur = useForm({
  tanggal: '',
  keterangan: '',
  berlaku_untuk: props.isAdmin ? 'semua' : 'kelas',
  kelas_id: props.kelasList[0]?.id || null,
});

const formJam = useForm({
  jam_batas_absen: props.jamBatas,
});

const changeTahun = () => {
  router.get('/kalender-libur', {
    tahun: selectedTahun.value,
  });
};

const submitHariLibur = () => {
  formLibur.post('/kalender-libur', {
    preserveScroll: true,
    onSuccess: () => {
      formLibur.reset('tanggal', 'keterangan');
    },
  });
};

const submitJamBatas = () => {
  formJam.post('/kalender-libur/pengaturan-jam', {
    preserveScroll: true,
  });
};

const hapusLibur = (item) => {
  if (confirm(`Hapus hari libur "${item.keterangan}" tanggal ${item.tanggal_indo}?`)) {
    router.delete(`/kalender-libur/${item.id}`, {
      preserveScroll: true,
    });
  }
};
</script>
