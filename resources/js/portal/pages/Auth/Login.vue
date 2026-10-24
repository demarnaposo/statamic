<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '../../components/AuthLayout.vue';
import TextField from '../../components/TextField.vue';
import { useShared } from '../../lib/page';

const shared = useShared();
const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post(shared.value.routes['login.store'], { onFinish: () => form.reset('password') });
</script>

<template>
  <AuthLayout title="Masuk ke portal" subtitle="Gunakan email dan kata sandi akun Anda.">
    <form class="card space-y-5" novalidate @submit.prevent="submit">
      <TextField v-model="form.email" label="Email" type="email" inputmode="email" autocomplete="username" required :error="form.errors.email" />
      <TextField v-model="form.password" label="Kata sandi" type="password" autocomplete="current-password" required :error="form.errors.password" />
      <div class="flex items-center justify-between gap-4 text-sm">
        <label class="inline-flex items-center gap-2 text-slate-700">
          <input v-model="form.remember" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-700 focus:ring-brand-500" />
          Ingat saya
        </label>
        <Link :href="shared.routes['password.request']" class="link">Lupa kata sandi?</Link>
      </div>
      <button type="submit" class="btn btn-primary w-full" :disabled="form.processing">
        {{ form.processing ? 'Memproses…' : 'Masuk' }}
      </button>
    </form>
    <p class="mt-6 text-center text-sm text-slate-600">
      Belum punya akun? <Link :href="shared.routes.register" class="link">Daftar sekarang</Link>
    </p>
  </AuthLayout>
</template>
