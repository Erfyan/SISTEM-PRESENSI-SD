<template>
  <AppLayout>
    <div class="space-y-6 max-w-6xl mx-auto">
      
      <!-- Top Action Bar -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
            <i class="fa-solid fa-users text-teal-600"></i>
            <span>Data Siswa & Wali Murid</span>
          </h1>
          <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
            <i class="fa-regular fa-id-card text-slate-400"></i>
            {{ kelas ? 'Daftar Siswa Kelas ' + kelas.nama + ' (' + kelas.tahun_ajaran + ')' : 'Pilih rombel kelas' }} · Total {{ siswa.length }} Siswa Terdata
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <!-- Kelas Selector (if Admin / Multiple classes) -->
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

          <!-- Unduh Template Button -->
          <a
            href="/siswa/import/template"
            target="_blank"
            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5 border border-slate-300 shadow-xs cursor-pointer"
            title="Unduh format template Excel untuk data siswa"
          >
            <i class="fa-solid fa-file-arrow-down text-emerald-600 text-sm"></i>
            <span>Format Excel</span>
          </a>

          <!-- Impor dari Excel Button -->
          <button
            v-if="kelas"
            type="button"
            @click="importModalOpen = true"
            class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer"
          >
            <i class="fa-solid fa-file-excel text-sm"></i>
            <span>Impor Siswa</span>
          </button>

          <!-- Add Student Button -->
          <button
            v-if="kelas"
            type="button"
            @click="openAddModal"
            class="px-4 py-2 bg-teal-600 hover:bg-teal-700 active:bg-teal-800 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer"
          >
            <i class="fa-solid fa-user-plus text-xs"></i>
            <span>Tambah Siswa</span>
          </button>
        </div>
      </div>

      <!-- Quick Stats (Consistent with Admin Dashboard) -->
      <div v-if="kelas && siswa.length > 0" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4 hover:border-purple-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center text-xl font-bold shadow-xs shrink-0">
            <i class="fa-solid fa-users text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Total Siswa</p>
            <p class="text-2xl font-black text-slate-900 mt-0.5">{{ siswa.length }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Kelas {{ kelas.nama }}</p>
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4 hover:border-blue-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center text-xl font-bold shadow-xs shrink-0">
            <i class="fa-solid fa-child text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Laki-Laki</p>
            <p class="text-2xl font-black text-blue-900 mt-0.5">{{ totalLakiLaki }}</p>
            <p class="text-[11px] text-blue-600 mt-0.5">Siswa putra</p>
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4 hover:border-pink-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-pink-100 text-pink-700 flex items-center justify-center text-xl font-bold shadow-xs shrink-0">
            <i class="fa-solid fa-child-dress text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Perempuan</p>
            <p class="text-2xl font-black text-pink-900 mt-0.5">{{ totalPerempuan }}</p>
            <p class="text-[11px] text-pink-600 mt-0.5">Siswa putri</p>
          </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4 hover:border-emerald-300 transition">
          <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl font-bold shadow-xs shrink-0">
            <i class="fa-solid fa-mobile-screen text-xl"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Akun Ortu Siap</p>
            <p class="text-2xl font-black text-emerald-700 mt-0.5">{{ totalAkunOrtu }} / {{ siswa.length }}</p>
            <p class="text-[11px] text-emerald-600 mt-0.5">{{ siswa.length - totalAkunOrtu === 0 ? 'Semua akun aktif' : (siswa.length - totalAkunOrtu) + ' belum punya PIN' }}</p>
          </div>
        </div>
      </div>

      <!-- Students Table Card -->
      <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex items-center gap-2">
            <h2 class="font-bold text-slate-900 text-sm sm:text-base flex items-center gap-2">
              <i class="fa-solid fa-address-book text-teal-600"></i>
              <span>Daftar Siswa & Kontak Orang Tua</span>
            </h2>
            <span class="text-xs text-slate-500 font-semibold px-2.5 py-1 rounded-full bg-slate-100 hidden sm:inline-flex">
              {{ filteredSiswa.length }} Siswa Tampil
            </span>
          </div>

          <!-- Search Input -->
          <div class="relative sm:w-72">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input
              type="text"
              v-model="searchQuery"
              placeholder="Cari nama, NIS, atau wali..."
              class="w-full text-xs pl-9 pr-3.5 py-2 rounded-xl bg-slate-50 border border-slate-300 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-teal-500 transition"
            />
          </div>
        </div>

        <div v-if="filteredSiswa.length > 0" class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase font-semibold">
              <tr>
                <th class="py-3 px-4 w-12 text-center">No</th>
                <th class="py-3 px-4 w-24">NIS</th>
                <th class="py-3 px-4">Nama Siswa</th>
                <th class="py-3 px-3 w-12 text-center">L/P</th>
                <th class="py-3 px-4">Nama Orang Tua</th>
                <th class="py-3 px-4">WhatsApp Ortu</th>
                <th class="py-3 px-3 text-center">Akun Ortu</th>
                <th class="py-3 px-3 text-center">Status</th>
                <th class="py-3 px-4 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="(s, index) in filteredSiswa"
                :key="s.id"
                :class="['hover:bg-slate-50 transition', !s.aktif ? 'bg-slate-50/70 opacity-60' : '']"
              >
                <td class="py-3.5 px-4 text-center font-medium text-slate-400">{{ index + 1 }}</td>
                <td class="py-3.5 px-4 font-mono font-medium text-slate-700">
                  <span class="px-2 py-0.5 rounded-md bg-slate-100 text-[11px]">{{ s.nis }}</span>
                </td>
                <td class="py-3.5 px-4 font-bold text-slate-900 text-sm">{{ s.nama }}</td>
                <td class="py-3.5 px-3 text-center">
                  <span
                    :class="[
                      'px-2 py-0.5 rounded-md text-[10px] font-bold inline-flex items-center justify-center',
                      s.jenis_kelamin === 'L' ? 'bg-blue-100 text-blue-700' : 'bg-pink-100 text-pink-700'
                    ]"
                  >
                    {{ s.jenis_kelamin }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-slate-800 font-medium">{{ s.nama_ortu }}</td>
                <td class="py-3.5 px-4 font-mono text-slate-700">
                  <a
                    :href="`https://wa.me/${s.no_wa}`"
                    target="_blank"
                    class="text-teal-700 hover:text-teal-800 hover:underline flex items-center gap-1.5 font-medium"
                    title="Kirim pesan WhatsApp"
                  >
                    <i class="fa-brands fa-whatsapp text-emerald-600 text-sm"></i>
                    <span>{{ s.no_wa }}</span>
                  </a>
                </td>
                <td class="py-3.5 px-3 text-center">
                  <span
                    v-if="s.akun_ortu"
                    class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 inline-flex items-center gap-1"
                    :title="'Username: ' + s.akun_ortu.username"
                  >
                    <i class="fa-solid fa-key text-[9px]"></i>
                    @{{ s.akun_ortu.username }}
                  </span>
                  <span
                    v-else
                    class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-500 border border-slate-200"
                  >
                    Belum Dibuat
                  </span>
                </td>
                <td class="py-3.5 px-3 text-center">
                  <span
                    :class="[
                      'px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1',
                      s.aktif ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'
                    ]"
                  >
                    <i :class="s.aktif ? 'fa-solid fa-circle-check text-[9px]' : 'fa-solid fa-circle-xmark text-[9px]'"></i>
                    <span>{{ s.aktif ? 'Aktif' : 'Nonaktif' }}</span>
                  </span>
                </td>
                <td class="py-3.5 px-4 text-center">
                  <div class="flex items-center justify-center gap-1.5">
                    <!-- Edit Button -->
                    <button
                      type="button"
                      @click="openEditModal(s)"
                      class="px-2.5 py-1.5 text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 cursor-pointer"
                    >
                      <i class="fa-solid fa-pen-to-square text-[11px] text-slate-500"></i>
                      <span>Ubah</span>
                    </button>

                    <!-- Reset / Create Parent Account -->
                    <button
                      type="button"
                      @click="buatAtauResetAkunOrtu(s)"
                      class="px-2.5 py-1.5 text-amber-800 bg-amber-50 hover:bg-amber-100 rounded-lg text-xs font-semibold border border-amber-200 transition inline-flex items-center gap-1.5 cursor-pointer"
                      :title="s.akun_ortu ? 'Reset PIN Akun Ortu' : 'Buat Akun Ortu Baru'"
                    >
                      <i class="fa-solid fa-key text-[11px] text-amber-600"></i>
                      <span>{{ s.akun_ortu ? 'Reset PIN' : 'Buat Akun' }}</span>
                    </button>

                    <!-- Toggle Aktif/Nonaktif -->
                    <button
                      type="button"
                      @click="toggleAktif(s)"
                      :class="[
                        'px-2.5 py-1.5 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 cursor-pointer border',
                        s.aktif ? 'text-rose-700 bg-rose-50 hover:bg-rose-100 border-rose-200' : 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border-emerald-200'
                      ]"
                    >
                      <i :class="s.aktif ? 'fa-solid fa-ban text-[11px]' : 'fa-solid fa-check text-[11px]'"></i>
                      <span>{{ s.aktif ? 'Nonaktifkan' : 'Aktifkan' }}</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div v-else class="p-12 text-center text-slate-400 text-xs">
          <i class="fa-solid fa-user-slash text-4xl text-slate-300 mb-2 block"></i>
          <p class="text-slate-600 text-sm font-semibold">
            {{ searchQuery ? 'Tidak ada siswa yang sesuai pencarian "' + searchQuery + '".' : (error_message || 'Belum ada siswa di kelas ini.') }}
          </p>
          <p class="text-slate-400 text-xs mt-1">Gunakan tombol "Tambah Siswa" atau "Impor Siswa" untuk memasukkan data.</p>
        </div>
      </div>

      <!-- Modal Tambah / Edit Siswa -->
      <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full p-6 animate-in fade-in zoom-in-95 duration-150">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="font-bold text-base text-slate-900 flex items-center gap-2">
              <i class="fa-solid fa-user-graduate text-teal-600"></i>
              <span>{{ isEditing ? 'Ubah Data Siswa' : 'Tambah Siswa Baru' }}</span>
            </h3>
            <button @click="modalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer">
              <i class="fa-solid fa-xmark text-base"></i>
            </button>
          </div>

          <form @submit.prevent="submitForm" class="mt-4 space-y-4">
            <div>
              <label class="block text-xs font-semibold uppercase text-slate-700 flex items-center gap-1.5">
                <i class="fa-solid fa-id-badge text-slate-400"></i>
                <span>NIS (Nomor Induk Siswa)</span>
              </label>
              <input
                type="text"
                v-model="form.nis"
                required
                placeholder="Contoh: 1007"
                class="mt-1.5 w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition"
              />
              <p v-if="form.errors.nis" class="text-[11px] text-rose-600 mt-1 flex items-center gap-1">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>{{ form.errors.nis }}</span>
              </p>
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase text-slate-700 flex items-center gap-1.5">
                <i class="fa-solid fa-user text-slate-400"></i>
                <span>Nama Lengkap Siswa</span>
              </label>
              <input
                type="text"
                v-model="form.nama"
                required
                placeholder="Nama lengkap anak"
                class="mt-1.5 w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase text-slate-700 flex items-center gap-1.5">
                <i class="fa-solid fa-venus-mars text-slate-400"></i>
                <span>Jenis Kelamin</span>
              </label>
              <select
                v-model="form.jenis_kelamin"
                required
                class="mt-1.5 w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition font-medium"
              >
                <option value="L">Laki-laki (L)</option>
                <option value="P">Perempuan (P)</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase text-slate-700 flex items-center gap-1.5">
                <i class="fa-solid fa-hands-holding-child text-slate-400"></i>
                <span>Nama Orang Tua / Wali</span>
              </label>
              <input
                type="text"
                v-model="form.nama_ortu"
                required
                placeholder="Contoh: Bpk. Bambang"
                class="mt-1.5 w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition"
              />
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase text-slate-700 flex items-center gap-1.5">
                <i class="fa-brands fa-whatsapp text-slate-400"></i>
                <span>Nomor WhatsApp Orang Tua</span>
              </label>
              <input
                type="text"
                v-model="form.no_wa"
                required
                placeholder="Contoh: 08123456789 atau 628123456789"
                class="mt-1.5 w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs sm:text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition"
              />
              <p class="text-[10px] text-slate-500 mt-1">Sistem otomatis menstandarkan nomor ke format internasional (62...).</p>
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
                <span>{{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambah Siswa') }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal Impor Siswa Massal dari Excel -->
      <ImportSiswaModal
        v-if="kelas"
        :isOpen="importModalOpen"
        :kelasId="kelas.id"
        :kelasNama="kelas.nama"
        @close="importModalOpen = false"
        @imported="refreshData"
      />

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ImportSiswaModal from '@/Components/ImportSiswaModal.vue';

const props = defineProps({
  kelasList: {
    type: Array,
    default: () => [],
  },
  kelas: {
    type: Object,
    default: null,
  },
  siswa: {
    type: Array,
    default: () => [],
  },
  error_message: {
    type: String,
    default: null,
  },
});

const totalLakiLaki = computed(() => {
  return props.siswa.filter((s) => s.jenis_kelamin === 'L').length;
});

const totalPerempuan = computed(() => {
  return props.siswa.filter((s) => s.jenis_kelamin === 'P').length;
});

const totalAkunOrtu = computed(() => {
  return props.siswa.filter((s) => !!s.akun_ortu).length;
});

const selectedKelasId = ref(props.kelas?.id || (props.kelasList[0]?.id ?? null));
const searchQuery = ref('');

const filteredSiswa = computed(() => {
  if (!searchQuery.value) return props.siswa;
  const q = searchQuery.value.toLowerCase();
  return props.siswa.filter(s =>
    s.nama.toLowerCase().includes(q) ||
    s.nis.toLowerCase().includes(q) ||
    (s.nama_ortu && s.nama_ortu.toLowerCase().includes(q))
  );
});

const refreshData = () => {
  router.get('/siswa', {
    kelas_id: selectedKelasId.value,
  }, {
    preserveState: false,
    preserveScroll: true,
  });
};

const modalOpen = ref(false);
const importModalOpen = ref(false);
const isEditing = ref(false);
const currentSiswaId = ref(null);

const form = useForm({
  kelas_id: props.kelas?.id,
  nis: '',
  nama: '',
  jenis_kelamin: 'L',
  nama_ortu: '',
  no_wa: '',
});

const openAddModal = () => {
  isEditing.value = false;
  currentSiswaId.value = null;
  form.reset();
  form.kelas_id = props.kelas?.id;
  modalOpen.value = true;
};

const openEditModal = (s) => {
  isEditing.value = true;
  currentSiswaId.value = s.id;
  form.kelas_id = s.kelas_id;
  form.nis = s.nis;
  form.nama = s.nama;
  form.jenis_kelamin = s.jenis_kelamin;
  form.nama_ortu = s.nama_ortu;
  form.no_wa = s.no_wa;
  modalOpen.value = true;
};

const submitForm = () => {
  if (isEditing.value) {
    form.put(`/siswa/${currentSiswaId.value}`, {
      onSuccess: () => {
        modalOpen.value = false;
      },
    });
  } else {
    form.post('/siswa', {
      onSuccess: () => {
        modalOpen.value = false;
        form.reset();
      },
    });
  }
};

const toggleAktif = (s) => {
  if (confirm(`Apakah Anda yakin ingin ${s.aktif ? 'menonaktifkan' : 'mengaktifkan'} siswa ${s.nama}?`)) {
    router.patch(`/siswa/${s.id}/toggle-aktif`, {}, {
      preserveScroll: true,
    });
  }
};

const buatAtauResetAkunOrtu = (s) => {
  const aksi = s.akun_ortu ? 'mereset PIN' : 'membuat akun orang tua';
  if (confirm(`Apakah Anda yakin ingin ${aksi} untuk siswa ${s.nama}?`)) {
    router.post(`/siswa/${s.id}/generate-akun-ortu`, {}, {
      preserveScroll: true,
    });
  }
};
</script>
