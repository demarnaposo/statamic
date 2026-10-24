<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '../../components/AuthLayout.vue';
import TextField from '../../components/TextField.vue';
import { useShared } from '../../lib/page';

const shared = useShared();
const form = useForm({ email: '' });
const submit = () => form.post(shared.value.routes['password.email']);
</script>

<template>
  <AuthLayout title="Lupa kata sandi" subtitle="Masukkan email akun Anda. Kami akan mengirim tautan untuk mengatur ulang kata sandi.">
    <form class="card space-y-5" novalidate @submit.prevent="submit">
      <TextField v-model="form.email" label="Email" type="email" inputmode="email" autocomplete="email" required :error="form.errors.email" />
      <button type="submit" class="btn btn-primary w-full" :disabled="form.processing">Kirim tautan</button>
    </form>
    <p class="mt-6 text-center text-sm text-slate-600">
      <Link :href="shared.routes.login" class="link">Kembali ke halaman masuk</Link>
    </p>
  </AuthLayout>
</template>
