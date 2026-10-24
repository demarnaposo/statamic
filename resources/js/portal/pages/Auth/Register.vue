<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '../../components/AuthLayout.vue';
import TextField from '../../components/TextField.vue';
import { useShared } from '../../lib/page';

const shared = useShared();
const form = useForm({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  privacy: false,
  marketing_opt_in: false,
});
const submit = () =>
  form.post(shared.value.routes['register.store'], { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
  <AuthLayout title="Buat akun peserta" subtitle="Daftar untuk menyimpan hasil assessment dan mendaftar program.">
    <form class="card space-y-5" novalidate @submit.prevent="submit">
      <TextField v-model="form.name" label="Nama lengkap" autocomplete="name" required :error="form.errors.name" hint="Sesuai identitas, akan tercetak di sertifikat." />
      <TextField v-model="form.email" label="Email" type="email" inputmode="email" autocomplete="email" required :error="form.errors.email" />
      <TextField v-model="form.phone" label="Nomor telepon/WhatsApp" type="tel" inputmode="tel" autocomplete="tel" required :error="form.errors.phone" hint="Contoh: 081234567890" />
      <TextField v-model="form.password" label="Kata sandi" type="password" autocomplete="new-password" required :error="form.errors.password" hint="Minimal 8 karakter." />
      <TextField v-model="form.password_confirmation" label="Ulangi kata sandi" type="password" autocomplete="new-password" required />

      <div class="space-y-3 text-sm">
        <div>
          <label class="flex items-start gap-3 text-slate-700">
            <input
              v-model="form.privacy"
              type="checkbox"
              required
              class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-700 focus:ring-brand-500"
              :aria-invalid="form.errors.privacy ? 'true' : undefined"
              aria-describedby="privacy-error"
            />
            <span>
              Saya menyetujui pemrosesan data pribadi sesuai
              <a :href="`${shared.app.landing_url}/kebijakan-privasi`" class="link" target="_blank" rel="noopener">Kebijakan Privasi</a>
              dan UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi. <span class="text-red-600" aria-hidden="true">*</span>
            </span>
          </label>
          <p v-if="form.errors.privacy" id="privacy-error" class="mt-1.5 text-red-700">{{ form.errors.privacy }}</p>
        </div>
        <label class="flex items-start gap-3 text-slate-700">
          <input v-model="form.marketing_opt_in" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-700 focus:ring-brand-500" />
          <span>Kirimi saya info jadwal & promo program (opsional, bisa dihentikan kapan saja).</span>
        </label>
      </div>

      <button type="submit" class="btn btn-primary w-full" :disabled="form.processing">
        {{ form.processing ? 'Memproses…' : 'Daftar' }}
      </button>
    </form>
    <p class="mt-6 text-center text-sm text-slate-600">
      Sudah punya akun? <Link :href="shared.routes.login" class="link">Masuk</Link>
    </p>
  </AuthLayout>
</template>
