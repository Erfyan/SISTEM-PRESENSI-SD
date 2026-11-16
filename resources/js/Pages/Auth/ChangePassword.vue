<template>
  <AppLayout>
    <div class="max-w-xl mx-auto py-4">
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <div class="mb-6 flex items-start gap-3">
          <div class="w-10 h-10 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-key text-lg"></i>
          </div>
          <div>
            <h2 class="text-xl font-bold text-slate-900">Ganti Kata Sandi / PIN</h2>
            <p class="text-xs text-slate-500 mt-1">
              Perbarui kata sandi atau PIN akun Anda secara berkala untuk menjaga keamanan data siswa.
            </p>
          </div>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <!-- Password Lama -->
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
              <i class="fa-solid fa-lock text-slate-400 text-xs"></i>
              <span>Kata Sandi / PIN Saat Ini</span>
            </label>
            <input
              type="password"
              v-model="form.current_password"
              required
              class="mt-1.5 w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
              :class="{ 'border-rose-400': form.errors.current_password }"
            />
            <p v-if="form.errors.current_password" class="mt-1 text-xs text-rose-600">
              {{ form.errors.current_password }}
            </p>
          </div>

          <!-- Password Baru -->
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
              <i class="fa-solid fa-key text-slate-400 text-xs"></i>
              <span>Kata Sandi / PIN Baru (Minimal 6 Karakter)</span>
            </label>
            <input
              type="password"
              v-model="form.password"
              required
              class="mt-1.5 w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
              :class="{ 'border-rose-400': form.errors.password }"
            />
            <p v-if="form.errors.password" class="mt-1 text-xs text-rose-600">
              {{ form.errors.password }}
            </p>
          </div>

          <!-- Konfirmasi Password Baru -->
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
              <i class="fa-solid fa-shield-halved text-slate-400 text-xs"></i>
              <span>Konfirmasi Kata Sandi Baru</span>
            </label>
            <input
              type="password"
              v-model="form.password_confirmation"
              required
              class="mt-1.5 w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
            />
          </div>

          <div class="pt-3">
            <button
              type="submit"
              :disabled="form.processing"
              class="w-full sm:w-auto px-6 py-2.5 bg-teal-600 hover:bg-teal-700 active:bg-teal-800 text-white font-bold rounded-xl text-sm transition shadow-sm cursor-pointer disabled:opacity-50 flex items-center justify-center gap-2"
            >
              <i class="fa-solid fa-floppy-disk text-xs"></i>
              <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const form = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.put('/profil/password', {
    onSuccess: () => form.reset(),
  });
};
</script>
