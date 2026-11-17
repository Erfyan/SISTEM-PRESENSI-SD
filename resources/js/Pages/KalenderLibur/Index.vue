<template>
  <AppLayout>
    <div class="space-y-6 max-w-6xl mx-auto pb-16">
      
      <!-- Top Header -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
            <i class="fa-solid fa-calendar-days text-teal-600"></i>
            <span>Kalender Hari Libur Sekolah</span>
          </h1>
          <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
            <i class="fa-regular fa-calendar-xmark text-slate-400"></i>
            Hari libur yang ditandai tidak akan dihitung ke dalam hari efektif rekap absensi bulanan.
          </p>
        </div>

        <!-- Filter Tahun -->
        <div class="flex items-center gap-2 self-start sm:self-auto">
          <label class="text-xs font-semibold text-slate-500 flex items-center gap-1">
            <i class="fa-solid fa-calendar text-slate-400"></i>
            <span>Tahun Ajaran:</span>
          </label>
          <select
            v-model="selectedTahun"
            @change="changeTahun"
            class="bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-teal-500 transition"
          >
            <option v-for="y in [tahun - 1, tahun, tahun + 1]" :key="y" :value="y">
              {{ y }}
            </option>
          </select>
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4 hover:border-purple-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center text-xl font-bold shadow-xs shrink-0">
            <i class="fa-solid fa-calendar-check text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Total Hari Libur</p>
            <p class="text-2xl font-black text-slate-900 mt-0.5">{{ hariLibur.length }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Tercatat di tahun {{ tahun }}</p>
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4 hover:border-teal-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-teal-100 text-teal-700 flex items-center justify-center text-xl font-bold shadow-xs shrink-0">
            <i class="fa-solid fa-school-circle-check text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Libur Semua Kelas</p>
            <p class="text-2xl font-black text-slate-900 mt-0.5">{{ liburSemuaKelas }}</p>
            <p class="text-[11px] text-teal-700 mt-0.5">Libur nasional / sekolah</p>
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4 hover:border-amber-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-xl font-bold shadow-xs shrink-0">
            <i class="fa-solid fa-clock text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Batas Waktu Absen</p>
            <p class="text-2xl font-black text-amber-700 mt-0.5">{{ jamBatas }} <span class="text-xs font-normal text-slate-500">WIB</span></p>
            <p class="text-[11px] text-amber-700 mt-0.5">Peringatan otomatis guru</p>
          </div>
        </div>
      </div>

      <!-- Grid: Form Tambah Libur & Daftar Hari Libur -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Column: Form Tambah Hari Libur & Pengaturan -->
        <div class="lg:col-span-1 space-y-6">
          <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <h3 class="font-bold text-sm text-slate-900 mb-3 flex items-center gap-2 pb-3 border-b border-slate-100">
              <i class="fa-solid fa-calendar-plus text-teal-600"></i>
              <span>Tambah Hari Libur Baru</span>
            </h3>

            <form @submit.prevent="submitHariLibur" class="space-y-4 text-xs">
              <div>
                <label class="block font-semibold text-slate-700 mb-1 flex items-center gap-1.5">
                  <i class="fa-regular fa-calendar text-slate-400"></i>
                  <span>Tanggal Libur</span>
                </label>
                <input
                  type="date"
                  v-model="formLibur.tanggal"
                  required
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-teal-500 text-xs sm:text-sm font-medium transition"
                />
              </div>

              <div>
                <label class="block font-semibold text-slate-700 mb-1 flex items-center gap-1.5">
                  <i class="fa-solid fa-tag text-slate-400"></i>
                  <span>Keterangan Libur</span>
                </label>
                <input
                  type="text"
                  v-model="formLibur.keterangan"
                  required
                  placeholder="Contoh: Hari Raya Idul Fitri, Hari Guru"
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-teal-500 text-xs sm:text-sm font-medium transition placeholder:text-slate-400"
                />
              </div>

              <div v-if="isAdmin">
                <label class="block font-semibold text-slate-700 mb-1 flex items-center gap-1.5">
                  <i class="fa-solid fa-users text-slate-400"></i>
                  <span>Berlaku Untuk</span>
                </label>
                <select
                  v-model="formLibur.berlaku_untuk"
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-teal-500 text-xs sm:text-sm font-medium transition"
                >
                  <option value="semua">Semua Kelas (Seluruh Sekolah)</option>
                  <option value="kelas">Kelas Tertentu Saja</option>
                </select>
              </div>

              <div v-if="formLibur.berlaku_untuk === 'kelas' && kelasList.length > 0">
                <label class="block font-semibold text-slate-700 mb-1 flex items-center gap-1.5">
                  <i class="fa-solid fa-school text-slate-400"></i>
                  <span>Pilih Kelas Terkait</span>
                </label>
                <select
                  v-model="formLibur.kelas_id"
                  required
                  class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-teal-500 text-xs sm:text-sm font-medium transition"
                >
                  <option v-for="k in kelasList" :key="k.id" :value="k.id">
                    Kelas {{ k.nama }}
                  </option>
                </select>
              </div>

              <button
                type="submit"
                :disabled="formLibur.processing"
                class="w-full py-2.5 bg-teal-600 hover:bg-teal-700 active:bg-teal-800 text-white font-bold rounded-xl transition shadow-xs cursor-pointer disabled:opacity-50 flex items-center justify-center gap-2 mt-2"
              >
                <i v-if="formLibur.processing" class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                <i v-else class="fa-solid fa-floppy-disk text-xs"></i>
                <span>{{ formLibur.processing ? 'Menyimpan...' : 'Simpan Hari Libur' }}</span>
              </button>
            </form>
          </div>

          <!-- Pengaturan Jam Batas Pengingat Absen Kosong (Khusus Admin) -->
          <div v-if="isAdmin" class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
            <h3 class="font-bold text-sm text-slate-900 mb-1 flex items-center gap-2">
              <i class="fa-solid fa-clock text-amber-600"></i>
              <span>Jam Batas Pengingat Absen</span>
            </h3>
            <p class="text-[11px] text-slate-500 mb-3">
              Wali kelas akan menerima banner pengingat bila belum menyimpan absen setelah jam ini.
            </p>

            <form @submit.prevent="submitJamBatas" class="space-y-3 text-xs">
              <div>
                <input
                  type="time"
                  v-model="formJam.jam_batas_absen"
                  required
                  class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl font-mono text-center text-sm font-bold focus:bg-white focus:ring-2 focus:ring-teal-500"
                />
              </div>

              <button
                type="submit"
                :disabled="formJam.processing"
                class="w-full py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl transition cursor-pointer disabled:opacity-50 flex items-center justify-center gap-1.5 shadow-xs"
              >
                <i v-if="formJam.processing" class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                <i v-else class="fa-solid fa-check text-xs"></i>
                <span>{{ formJam.processing ? 'Menyimpan...' : 'Perbarui Jam Batas' }}</span>
              </button>
            </form>
          </div>
        </div>

        <!-- Right Column: Daftar Hari Libur -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
            <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
              <h3 class="font-bold text-sm sm:text-base text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-list-ul text-teal-600"></i>
                <span>Daftar Hari Libur Tahun {{ tahun }}</span>
              </h3>
              <span class="text-xs text-slate-500 font-semibold px-2.5 py-1 rounded-full bg-slate-100 inline-flex items-center gap-1.5 self-start sm:self-auto">
                <i class="fa-regular fa-calendar-check text-teal-600 text-[10px]"></i>
                {{ hariLibur.length }} Hari Libur Terdata
              </span>
            </div>

            <div v-if="hariLibur.length > 0" class="divide-y divide-slate-100">
              <div
                v-for="item in hariLibur"
                :key="item.id"
                class="p-4 sm:px-5 hover:bg-slate-50 flex items-center justify-between gap-4 transition"
              >
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-700 flex items-center justify-center text-sm font-bold border border-rose-200 shrink-0">
                    <i class="fa-solid fa-umbrella-beach"></i>
                  </div>
                  <div class="space-y-0.5">
                    <div class="flex items-center gap-2 flex-wrap">
                      <span class="font-bold text-sm text-slate-900">{{ item.keterangan }}</span>
                      <span
                        :class="[
                          'px-2 py-0.5 rounded-full text-[10px] font-bold inline-flex items-center gap-1',
                          item.berlaku_untuk === 'semua'
                            ? 'bg-purple-100 text-purple-800 border border-purple-200'
                            : 'bg-blue-100 text-blue-800 border border-blue-200'
                        ]"
                      >
                        <i :class="item.berlaku_untuk === 'semua' ? 'fa-solid fa-school text-[9px]' : 'fa-solid fa-tag text-[9px]'"></i>
                        {{ item.nama_kelas }}
                      </span>
                    </div>
                    <p class="text-xs text-slate-500">
                      {{ item.hari }}, <span class="font-semibold text-slate-700">{{ item.tanggal_indo }}</span>
                    </p>
                  </div>
                </div>

                <button
                  type="button"
                  @click="hapusLibur(item)"
                  class="p-2 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition cursor-pointer"
                  title="Hapus hari libur ini"
                >
                  <i class="fa-solid fa-trash-can text-sm"></i>
                </button>
              </div>
            </div>

            <div v-else class="p-12 text-center text-slate-400 text-xs">
              <i class="fa-regular fa-calendar-check text-4xl text-slate-300 mb-2 block"></i>
              Belum ada hari libur yang ditandai untuk tahun {{ tahun }}.
            </div>
          </div>
        </div>

      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
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

const liburSemuaKelas = computed(() => {
  return props.hariLibur.filter((l) => l.berlaku_untuk === 'semua').length;
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
