<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import PortalLayout from '../../components/PortalLayout.vue';
import TextField from '../../components/TextField.vue';
import { formatDate } from '../../lib/format';
import { useShared } from '../../lib/page';

const props = defineProps<{
  profile: {
    name: string;
    email: string;
    phone: string;
    company_name: string | null;
    npwp: string | null;
    billing_address: string | null;
    marketing_opt_in: boolean;
    privacy_accepted_at: string;
  };
}>();
const shared = useShared();

const profile = useForm({
  name: props.profile.name,
  email: props.profile.email,
  phone: props.profile.phone,
  company_name: props.profile.company_name ?? '',
  npwp: props.profile.npwp ?? '',
  billing_address: props.profile.billing_address ?? '',
  marketing_opt_in: props.profile.marketing_opt_in,
});
const password = useForm({ current_password: '', password: '', password_confirmation: '' });

const saveProfile = () => profile.put(shared.value.routes['profile.update'], { preserveScroll: true });
const savePassword = () =>
  password.put(shared.value.routes['password.update'], { preserveScroll: true, onFinish: () => password.reset() });
</script>

<template>
  <PortalLayout title="Profil" active="profile">
    <h1 class="page-title">Profil</h1>
    <p class="mt-2 text-slate-600">Perbarui data diri dan data penagihan untuk faktur.</p>

    <form class="mt-8 grid gap-6 lg:grid-cols-2" novalidate @submit.prevent="saveProfile">
      <section class="card space-y-5" aria-labelledby="judul-pribadi">
        <h2 id="judul-pribadi" class="text-base font-semibold text-slate-900">Data pribadi</h2>
        <TextField v-model="profile.name" label="Nama lengkap" autocomplete="name" required :error="profile.errors.name" hint="Sesuai identitas, akan tercetak di sertifikat." />
        <TextField v-model="profile.email" label="Email" type="email" inputmode="email" autocomplete="email" required :error="profile.errors.email" hint="Mengubah email memerlukan verifikasi ulang." />
        <TextField v-model="profile.phone" label="Nomor telepon/WhatsApp" type="tel" inputmode="tel" autocomplete="tel" required :error="profile.errors.phone" />
        <label class="flex items-start gap-3 text-sm text-slate-700">
          <input v-model="profile.marketing_opt_in" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-700 focus:ring-brand-500" />
          <span>Kirimi saya info jadwal & promo program.</span>
        </label>
        <p class="text-xs text-slate-500">Persetujuan Kebijakan Privasi diberikan pada {{ formatDate(props.profile.privacy_accepted_at) }}.</p>
      </section>

      <section class="card space-y-5" aria-labelledby="judul-perusahaan">
        <div>
          <h2 id="judul-perusahaan" class="text-base font-semibold text-slate-900">Data perusahaan & faktur</h2>
          <p class="mt-1 text-sm text-slate-500">Opsional. Diisi bila faktur/faktur pajak atas nama perusahaan.</p>
        </div>
        <TextField v-model="profile.company_name" label="Nama perusahaan/instansi" autocomplete="organization" :error="profile.errors.company_name" />
        <TextField v-model="profile.npwp" label="NPWP" inputmode="numeric" autocomplete="off" :error="profile.errors.npwp" hint="15 atau 16 digit; titik/strip boleh disertakan." />
        <TextField v-model="profile.billing_address" label="Alamat penagihan" autocomplete="street-address" multiline :error="profile.errors.billing_address" />
      </section>

      <div class="lg:col-span-2">
        <button type="submit" class="btn btn-primary" :disabled="profile.processing">{{ profile.processing ? 'Menyimpan…' : 'Simpan profil' }}</button>
      </div>
    </form>

    <form class="card mt-10 max-w-xl space-y-5" novalidate aria-labelledby="judul-sandi" @submit.prevent="savePassword">
      <h2 id="judul-sandi" class="text-base font-semibold text-slate-900">Ganti kata sandi</h2>
      <TextField v-model="password.current_password" label="Kata sandi saat ini" type="password" autocomplete="current-password" required :error="password.errors.current_password" />
      <TextField v-model="password.password" label="Kata sandi baru" type="password" autocomplete="new-password" required :error="password.errors.password" hint="Minimal 8 karakter." />
      <TextField v-model="password.password_confirmation" label="Ulangi kata sandi baru" type="password" autocomplete="new-password" required />
      <button type="submit" class="btn btn-primary" :disabled="password.processing">Ganti kata sandi</button>
    </form>
  </PortalLayout>
</template>
