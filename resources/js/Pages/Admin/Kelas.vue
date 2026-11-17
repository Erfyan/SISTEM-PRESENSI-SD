<template>
  <AppLayout>
    <div class="space-y-6 max-w-6xl mx-auto">
      
      <!-- Top Header -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
            <i class="fa-solid fa-school text-teal-600"></i>
            <span>Kelola Kelas & Wali Kelas</span>
          </h1>
          <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
            <i class="fa-regular fa-folder-open text-slate-400"></i>
            Tambah dan kelola rombongan belajar serta penugasan guru wali kelas SD Negeri 1.
          </p>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="openAddModal"
            class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 active:bg-teal-800 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-xs cursor-pointer"
          >
            <i class="fa-solid fa-plus text-xs"></i>
            <span>Tambah Kelas Baru</span>
          </button>
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4 hover:border-purple-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center text-xl font-bold shadow-xs shrink-0">
            <i class="fa-solid fa-school text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Total Kelas</p>
            <p class="text-2xl font-black text-slate-900 mt-0.5">{{ kelas.length }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Rombongan belajar aktif</p>
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4 hover:border-teal-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-teal-100 text-teal-700 flex items-center justify-center text-xl font-bold shadow-xs shrink-0">
            <i class="fa-solid fa-users text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Total Siswa Terdaftar</p>
            <p class="text-2xl font-black text-slate-900 mt-0.5">{{ totalSiswa }}</p>
            <p class="text-[11px] text-teal-700 mt-0.5">Siswa terbagi di seluruh kelas</p>
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4 hover:border-emerald-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl font-bold shadow-xs shrink-0">
            <i class="fa-solid fa-chalkboard-user text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Kelas Ber-Wali</p>
            <p class="text-2xl font-black text-slate-900 mt-0.5">{{ kelasBerwali }} / {{ kelas.length }}</p>
            <p class="text-[11px] text-emerald-700 mt-0.5">{{ kelas.length - kelasBerwali === 0 ? 'Semua kelas memiliki wali' : (kelas.length - kelasBerwali) + ' kelas belum ada wali' }}</p>
          </div>
        </div>
      </div>

      <!-- Classes Table Card -->
      <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <h2 class="font-bold text-slate-900 text-sm sm:text-base flex items-center gap-2">
            <i class="fa-solid fa-table-list text-teal-600"></i>
            <span>Daftar Rombongan Belajar</span>
          </h2>
          <span class="text-xs text-slate-500 font-semibold px-2.5 py-1 rounded-full bg-slate-100 inline-flex items-center gap-1.5 self-start sm:self-auto">
            <i class="fa-solid fa-check-double text-teal-600 text-[10px]"></i>
            {{ kelas.length }} Rombel Terdata
          </span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-semibold">
              <tr>
                <th class="py-3 px-4 w-12 text-center">No</th>
                <th class="py-3 px-4">Nama Kelas</th>
                <th class="py-3 px-4">Tahun Ajaran</th>
                <th class="py-3 px-4">Wali Kelas</th>
                <th class="py-3 px-3 text-center">Jumlah Siswa</th>
                <th class="py-3 px-4 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(k, index) in kelas" :key="k.id" class="hover:bg-slate-50 transition">
                <td class="py-3.5 px-4 text-center text-slate-400 font-medium">{{ index + 1 }}</td>
                <td class="py-3.5 px-4 font-bold text-slate-900 text-sm">
                  <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-800 font-bold flex items-center justify-center text-xs border border-teal-200 shrink-0">
                      {{ k.nama }}
                    </div>
                    <span>Kelas {{ k.nama }}</span>
                  </div>
                </td>
                <td class="py-3.5 px-4 text-slate-600 font-medium">
                  <span class="px-2 py-0.5 rounded-md bg-slate-100 font-mono text-[11px] text-slate-700">
                    {{ k.tahun_ajaran }}
                  </span>
                </td>
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-1.5 font-semibold text-slate-800">
                    <i class="fa-solid fa-chalkboard-user text-xs text-slate-400"></i>
                    <span>{{ k.wali_nama || '-' }}</span>
                  </div>
                </td>
                <td class="py-3.5 px-3 text-center font-bold text-teal-800">
                  <span class="px-2.5 py-1 rounded-full text-[11px] bg-teal-50 text-teal-800 border border-teal-200 font-bold">
                    {{ k.total_siswa }} Siswa
                  </span>
                </td>
                <td class="py-3.5 px-4 text-center">
                  <div class="flex items-center justify-center gap-1.5">
                    <button
                      type="button"
                      @click="openEditModal(k)"
                      class="px-2.5 py-1.5 text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 cursor-pointer"
                    >
                      <i class="fa-solid fa-pen-to-square text-[11px] text-slate-500"></i>
                      <span>Ubah</span>
                    </button>
                    <button
                      type="button"
                      @click="hapusKelas(k)"
                      class="px-2.5 py-1.5 text-rose-700 bg-rose-50 hover:bg-rose-100 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 cursor-pointer border border-rose-200"
                    >
                      <i class="fa-solid fa-trash-can text-[11px] text-rose-500"></i>
                      <span>Hapus</span>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="kelas.length === 0">
                <td colspan="6" class="p-10 text-center text-slate-400 text-xs">
                  <i class="fa-solid fa-school text-3xl text-slate-300 mb-2 block"></i>
                  Belum ada rombel kelas yang ditambahkan. Silakan klik tombol "Tambah Kelas Baru".
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal Tambah / Edit Kelas -->
      <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full p-6 animate-in fade-in zoom-in-95 duration-150">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="font-bold text-base text-slate-900 flex items-center gap-2">
              <i class="fa-solid fa-school text-teal-600"></i>
              <span>{{ isEditing ? 'Ubah Data Kelas' : 'Tambah Kelas Baru' }}</span>
            </h3>
            <button @click="modalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer">
              <i class="fa-solid fa-xmark text-base"></i>
            </button>
          </div>

          <form @submit.prevent="submitForm" class="mt-4 space-y-4">
            <div>
              <label class="block text-xs font-semibold uppercase text-slate-700 flex items-center gap-1.5">
                <i class="fa-solid fa-tag text-slate-400"></i>
                <span>Nama Kelas</span>
              </label>
              <input
                type="text"
                v-model="form.nama"
                required
                placeholder="Contoh: 1A, 2B, 6A"
                class="mt-1.5 w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase text-slate-700 flex items-center gap-1.5">
                <i class="fa-solid fa-calendar-days text-slate-400"></i>
                <span>Tahun Ajaran</span>
              </label>
              <input
                type="text"
                v-model="form.tahun_ajaran"
                required
                placeholder="Contoh: 2026/2027"
                class="mt-1.5 w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase text-slate-700 flex items-center gap-1.5">
                <i class="fa-solid fa-chalkboard-user text-slate-400"></i>
                <span>Tunjuk Guru Wali Kelas</span>
              </label>
              <select
                v-model="form.wali_id"
                class="mt-1.5 w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition font-medium"
              >
                <option :value="null">-- Belum Ditentukan --</option>
                <option v-for="g in guruList" :key="g.id" :value="g.id">
                  {{ g.nama }} (@{{ g.username }})
                </option>
              </select>
            </div>

            <div class="pt-4 flex justify-end gap-2 border-t border-slate-100">
              <button
                type="button"
                @click="modalOpen = false"
                class="px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition cursor-pointer"
              >
                Batal
              </button>
              <button
                type="submit"
                :disabled="form.processing"
                class="px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 active:bg-teal-800 text-white text-xs font-bold transition disabled:opacity-50 shadow-xs flex items-center gap-1.5 cursor-pointer"
              >
                <i v-if="form.processing" class="fa-solid fa-circle-notch fa-spin text-xs"></i>
                <i v-else class="fa-solid fa-check text-xs"></i>
                <span>{{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Buat Kelas') }}</span>
              </button>
            </div>
          </form>
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
  kelas: {
    type: Array,
    default: () => [],
  },
  guruList: {
    type: Array,
    default: () => [],
  },
});

const totalSiswa = computed(() => {
  return props.kelas.reduce((acc, curr) => acc + (curr.total_siswa || 0), 0);
});

const kelasBerwali = computed(() => {
  return props.kelas.filter((k) => !!k.wali_id || (k.wali_nama && k.wali_nama !== '-')).length;
});

const modalOpen = ref(false);
const isEditing = ref(false);
const currentKelasId = ref(null);

const form = useForm({
  nama: '',
  tahun_ajaran: '2026/2027',
  wali_id: null,
});

const openAddModal = () => {
  isEditing.value = false;
  currentKelasId.value = null;
  form.reset();
  form.tahun_ajaran = '2026/2027';
  modalOpen.value = true;
};

const openEditModal = (k) => {
  isEditing.value = true;
  currentKelasId.value = k.id;
  form.nama = k.nama;
  form.tahun_ajaran = k.tahun_ajaran;
  form.wali_id = k.wali_id;
  modalOpen.value = true;
};

const submitForm = () => {
  if (isEditing.value) {
    form.put(`/admin/kelas/${currentKelasId.value}`, {
      onSuccess: () => {
        modalOpen.value = false;
      },
    });
  } else {
    form.post('/admin/kelas', {
      onSuccess: () => {
        modalOpen.value = false;
        form.reset();
      },
    });
  }
};

const hapusKelas = (k) => {
  if (confirm(`Apakah Anda yakin ingin menghapus kelas ${k.nama}?`)) {
    router.delete(`/admin/kelas/${k.id}`);
  }
};
</script>
