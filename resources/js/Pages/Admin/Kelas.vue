<template>
  <AppLayout>
    <div class="space-y-5 max-w-5xl mx-auto">
      
      <!-- Top Header -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
            Kelola Kelas & Wali Kelas
          </h1>
          <p class="text-xs text-slate-500 mt-1">
            Tambah dan kelola data rombongan belajar serta penugasan guru wali kelas.
          </p>
        </div>
        <div>
          <button
            type="button"
            @click="openAddModal"
            class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer"
          >
            <i class="fa-solid fa-plus text-xs"></i>
            <span>Tambah Kelas Baru</span>
          </button>
        </div>
      </div>

      <!-- Classes Table -->
      <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
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
                <td class="py-3.5 px-4 text-center text-slate-500 font-medium">{{ index + 1 }}</td>
                <td class="py-3.5 px-4 font-bold text-slate-900 text-sm">Kelas {{ k.nama }}</td>
                <td class="py-3.5 px-4 text-slate-600 font-medium">{{ k.tahun_ajaran }}</td>
                <td class="py-3.5 px-4 text-slate-800 font-semibold">{{ k.wali_nama }}</td>
                <td class="py-3.5 px-3 text-center font-bold text-teal-800">{{ k.total_siswa }} Siswa</td>
                <td class="py-3.5 px-4 text-center">
                  <div class="flex items-center justify-center gap-2">
                    <button
                      type="button"
                      @click="openEditModal(k)"
                      class="px-2.5 py-1 text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg text-xs font-semibold transition"
                    >
                      Ubah
                    </button>
                    <button
                      type="button"
                      @click="hapusKelas(k)"
                      class="px-2.5 py-1 text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-lg text-xs font-semibold transition"
                    >
                      Hapus
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal Tambah / Edit Kelas -->
      <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-md w-full p-6 animate-in fade-in zoom-in-95">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="font-bold text-base text-slate-900">
              {{ isEditing ? 'Ubah Data Kelas' : 'Tambah Kelas Baru' }}
            </h3>
            <button @click="modalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer">
              <i class="fa-solid fa-xmark text-base"></i>
            </button>
          </div>

          <form @submit.prevent="submitForm" class="mt-4 space-y-3">
            <div>
              <label class="block text-xs font-semibold uppercase text-slate-700">Nama Kelas</label>
              <input
                type="text"
                v-model="form.nama"
                required
                placeholder="Contoh: 1A, 2B, 6A"
                class="mt-1 w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-teal-500"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase text-slate-700">Tahun Ajaran</label>
              <input
                type="text"
                v-model="form.tahun_ajaran"
                required
                placeholder="Contoh: 2026/2027"
                class="mt-1 w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-teal-500"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase text-slate-700">Tunjuk Wali Kelas</label>
              <select
                v-model="form.wali_id"
                class="mt-1 w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-teal-500"
              >
                <option :value="null">-- Belum Ditentukan --</option>
                <option v-for="g in guruList" :key="g.id" :value="g.id">
                  {{ g.nama }} (@{{ g.username }})
                </option>
              </select>
            </div>

            <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
              <button
                type="button"
                @click="modalOpen = false"
                class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition"
              >
                Batal
              </button>
              <button
                type="submit"
                :disabled="form.processing"
                class="px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition disabled:opacity-50"
              >
                {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Buat Kelas') }}
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  kelas: {
    type: Array,
    default: () => [],
  },
  guruList: {
    type: Array,
    default: () => [],
  },
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
