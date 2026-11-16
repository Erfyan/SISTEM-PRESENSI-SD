<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    <div class="bg-white rounded-2xl max-w-3xl w-full shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in duration-150">
      
      <!-- Modal Header -->
      <div class="p-5 bg-teal-50 border-b border-teal-200 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-teal-600 text-white flex items-center justify-center font-bold">
            <i class="fa-solid fa-file-excel text-lg"></i>
          </div>
          <div>
            <h3 class="text-base font-bold text-teal-950 flex items-center gap-2">
              <span>Impor Data Siswa Massal (Excel)</span>
            </h3>
            <p class="text-xs text-teal-800">
              Target Kelas: <strong class="text-teal-950">{{ kelasNama }}</strong>
            </p>
          </div>
        </div>

        <button
          type="button"
          @click="closeModal"
          class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-white cursor-pointer"
        >
          <i class="fa-solid fa-xmark text-lg"></i>
        </button>
      </div>

      <!-- Step 1: Upload File -->
      <div v-if="step === 'upload'" class="p-6 space-y-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 p-4 rounded-xl bg-slate-50 border border-slate-200">
          <div>
            <h4 class="font-bold text-sm text-slate-800">Format Template Excel</h4>
            <p class="text-xs text-slate-500 mt-0.5">
              Kolom wajib: NIS, Nama, Jenis Kelamin (L/P), Nama Orang Tua, No WhatsApp.
            </p>
          </div>
          <a
            href="/siswa/import/template"
            target="_blank"
            class="px-4 py-2 rounded-xl bg-white border border-slate-300 text-slate-700 hover:bg-slate-100 text-xs font-bold flex items-center gap-2 shadow-xs shrink-0 cursor-pointer"
          >
            <i class="fa-solid fa-file-arrow-down text-emerald-600"></i>
            <span>Unduh Template</span>
          </a>
        </div>

        <!-- Dropzone -->
        <div
          class="border-2 border-dashed border-slate-300 hover:border-teal-500 rounded-2xl p-8 text-center cursor-pointer transition bg-slate-50 hover:bg-teal-50/30 flex flex-col items-center justify-center gap-3"
          @click="$refs.fileInput.click()"
        >
          <input
            type="file"
            ref="fileInput"
            accept=".xlsx,.xls,.csv"
            class="hidden"
            @change="handleFileSelected"
          />
          <div class="w-14 h-14 rounded-2xl bg-teal-100 text-teal-700 flex items-center justify-center">
            <i class="fa-solid fa-cloud-arrow-up text-2xl"></i>
          </div>
          <div>
            <p class="font-bold text-slate-800 text-sm">
              {{ selectedFile ? selectedFile.name : 'Klik untuk memilih file Excel (.xlsx, .xls, .csv)' }}
            </p>
            <p class="text-xs text-slate-500 mt-1">Ukuran maksimal file 5 MB</p>
          </div>
        </div>

        <div v-if="uploadError" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium">
          {{ uploadError }}
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button
            type="button"
            @click="closeModal"
            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 cursor-pointer"
          >
            Batal
          </button>
          <button
            type="button"
            @click="processPreview"
            :disabled="!selectedFile || loading"
            class="px-5 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs shadow-md shadow-teal-600/30 flex items-center gap-2 cursor-pointer disabled:opacity-50"
          >
            <svg v-if="loading" class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ loading ? 'Menganalisis File...' : 'Lanjut ke Pratinjau' }}</span>
          </button>
        </div>
      </div>

      <!-- Step 2: Preview & Validation Results -->
      <div v-else-if="step === 'preview'" class="flex-1 flex flex-col overflow-hidden">
        
        <!-- Summary Stats Badges -->
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs">
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-1 rounded-lg bg-slate-200 text-slate-700 font-bold">
              Total: {{ totalRows }} Baris
            </span>
            <span class="px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-800 font-bold border border-emerald-300">
              Valid: {{ validRows.length }} Baris
            </span>
            <span
              v-if="invalidRows.length > 0"
              class="px-2.5 py-1 rounded-lg bg-rose-100 text-rose-800 font-bold border border-rose-300"
            >
              Bermasalah: {{ invalidRows.length }} Baris
            </span>
          </div>

          <div class="flex items-center gap-2">
            <label class="flex items-center gap-2 cursor-pointer select-none">
              <input
                type="checkbox"
                v-model="buatAkunOrtu"
                class="rounded border-slate-300 text-teal-600 focus:ring-teal-500"
              />
              <span class="text-xs font-medium text-slate-700">Buat Akun Ortu Otomatis (jika ada No WA)</span>
            </label>
          </div>
        </div>

        <!-- Tables Scroll Area -->
        <div class="flex-1 overflow-y-auto p-4 space-y-5">
          
          <!-- Invalid Rows Alert (Baris salah tidak membatalkan baris benar) -->
          <div v-if="invalidRows.length > 0" class="border border-rose-200 bg-rose-50/60 rounded-xl p-4">
            <div class="flex items-center gap-2 mb-2">
              <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              <h5 class="text-xs font-bold text-rose-900">
                {{ invalidRows.length }} Baris Bermasalah (Akan Dilewati, Baris Benar Tetap Disimpan)
              </h5>
            </div>
            <div class="divide-y divide-rose-200/60 max-h-40 overflow-y-auto text-[11px]">
              <div v-for="inv in invalidRows" :key="inv.row_number" class="py-1.5 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                <div>
                  <span class="font-mono font-bold text-rose-700 mr-2">Baris {{ inv.row_number }}:</span>
                  <span class="font-semibold text-slate-800">{{ inv.nama || '(Tanpa Nama)' }}</span>
                  <span class="text-slate-500 ml-1 font-mono">(NIS: {{ inv.nis || '-' }})</span>
                </div>
                <div class="text-rose-700 font-medium">
                  {{ inv.errors.join('; ') }}
                </div>
              </div>
            </div>
          </div>

          <!-- Valid Rows Table -->
          <div>
            <h5 class="text-xs font-bold text-slate-700 mb-2">
              Daftar Siswa Siap Diimpor ({{ validRows.length }} Siswa):
            </h5>
            <div class="border border-slate-200 rounded-xl overflow-hidden overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                  <tr>
                    <th class="p-2.5">No</th>
                    <th class="p-2.5">NIS</th>
                    <th class="p-2.5">Nama</th>
                    <th class="p-2.5">L/P</th>
                    <th class="p-2.5">Orang Tua</th>
                    <th class="p-2.5">No WhatsApp (Normal)</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="(v, idx) in validRows" :key="v.nis" class="hover:bg-slate-50">
                    <td class="p-2.5 text-slate-400 font-mono">{{ idx + 1 }}</td>
                    <td class="p-2.5 font-mono font-semibold text-slate-900">{{ v.nis }}</td>
                    <td class="p-2.5 font-bold text-slate-800">{{ v.nama }}</td>
                    <td class="p-2.5">
                      <span
                        :class="[
                          'px-1.5 py-0.5 rounded text-[10px] font-bold',
                          v.jenis_kelamin === 'L' ? 'bg-blue-100 text-blue-700' : 'bg-pink-100 text-pink-700'
                        ]"
                      >
                        {{ v.jenis_kelamin }}
                      </span>
                    </td>
                    <td class="p-2.5 text-slate-600">{{ v.nama_ortu }}</td>
                    <td class="p-2.5 font-mono text-slate-600">{{ v.no_wa || '-' }}</td>
                  </tr>
                  <tr v-if="validRows.length === 0">
                    <td colspan="6" class="p-6 text-center text-slate-400">
                      Tidak ada baris data valid yang ditemukan pada file.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

        </div>

        <!-- Footer Actions -->
        <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between gap-3">
          <button
            type="button"
            @click="step = 'upload'"
            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 cursor-pointer"
          >
            Kembali
          </button>
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 cursor-pointer"
            >
              Batal
            </button>
            <button
              type="button"
              @click="executeImport"
              :disabled="validRows.length === 0 || loading"
              class="px-5 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs shadow-md shadow-teal-600/30 flex items-center gap-2 cursor-pointer disabled:opacity-50"
            >
              <svg v-if="loading" class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>Konfirmasi & Simpan {{ validRows.length }} Siswa</span>
            </button>
          </div>
        </div>

      </div>

      <!-- Step 3: Success & Download Initial PINs -->
      <div v-else-if="step === 'success'" class="p-6 space-y-5 text-center">
        <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 mx-auto flex items-center justify-center">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <div>
          <h4 class="text-lg font-bold text-slate-900">Impor Siswa Berhasil!</h4>
          <p class="text-xs text-slate-600 mt-1 max-w-md mx-auto">
            {{ successMessage }}
          </p>
        </div>

        <!-- Initial PIN Download Box (G7 & Opsi Buat Akun) -->
        <div v-if="createdAkunOrtu.length > 0" class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-left">
          <div class="flex items-center gap-2 mb-2">
            <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <h5 class="text-xs font-bold text-amber-950">
              {{ createdAkunOrtu.length }} Akun Orang Tua & PIN Awal Berhasil Dibuat
            </h5>
          </div>
          <p class="text-[11px] text-amber-800 mb-3">
            PIN awal hanya ditampilkan sekali ini. Silakan unduh daftar akun dan PIN ini untuk dibagikan ke orang tua siswa.
          </p>
          <div class="flex items-center gap-2 flex-wrap">
            <a
              :href="`/siswa/kelas/${kelasId}/download-pin?format=excel`"
              target="_blank"
              class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              Unduh PIN (Excel)
            </a>
            <a
              :href="`/siswa/kelas/${kelasId}/download-pin?format=pdf`"
              target="_blank"
              class="px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
              </svg>
              Unduh PIN (PDF)
            </a>
          </div>
        </div>

        <div class="pt-3">
          <button
            type="button"
            @click="finishAndRefresh"
            class="px-6 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs shadow-md shadow-teal-600/30 cursor-pointer"
          >
            Selesai & Muat Ulang Daftar Siswa
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
  isOpen: Boolean,
  kelasId: {
    type: [Number, String],
    required: true,
  },
  kelasNama: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['close', 'imported']);

const step = ref('upload'); // 'upload' | 'preview' | 'success'
const selectedFile = ref(null);
const loading = ref(false);
const uploadError = ref('');

const totalRows = ref(0);
const validRows = ref([]);
const invalidRows = ref([]);
const buatAkunOrtu = ref(true);

const successMessage = ref('');
const createdAkunOrtu = ref([]);

const handleFileSelected = (e) => {
  const file = e.target.files[0];
  if (file) {
    selectedFile.value = file;
    uploadError.value = '';
  }
};

const processPreview = async () => {
  if (!selectedFile.value) return;

  loading.value = true;
  uploadError.value = '';

  const formData = new FormData();
  formData.append('kelas_id', props.kelasId);
  formData.append('file', selectedFile.value);

  try {
    const res = await axios.post('/siswa/import/preview', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    if (res.data.success) {
      validRows.value = res.data.valid_rows;
      invalidRows.value = res.data.invalid_rows;
      totalRows.value = res.data.total_rows;
      step.value = 'preview';
    }
  } catch (err) {
    uploadError.value = err.response?.data?.message || 'Gagal membaca file Excel. Pastikan format file sesuai.';
  } finally {
    loading.value = false;
  }
};

const executeImport = async () => {
  if (validRows.value.length === 0) return;

  loading.value = true;
  try {
    const res = await axios.post('/siswa/import/store', {
      kelas_id: props.kelasId,
      rows: validRows.value,
      buat_akun_ortu: buatAkunOrtu.value,
    });

    if (res.data.success) {
      successMessage.value = res.data.message;
      createdAkunOrtu.value = res.data.akun_ortu || [];
      step.value = 'success';
    }
  } catch (err) {
    alert('Gagal menyimpan data siswa: ' + (err.response?.data?.message || err.message));
  } finally {
    loading.value = false;
  }
};

const finishAndRefresh = () => {
  emit('imported');
  closeModal();
};

const closeModal = () => {
  step.value = 'upload';
  selectedFile.value = null;
  validRows.value = [];
  invalidRows.value = [];
  uploadError.value = '';
  emit('close');
};
</script>
