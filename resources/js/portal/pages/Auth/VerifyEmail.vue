<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import AuthLayout from '../../components/AuthLayout.vue';
import { useShared } from '../../lib/page';

defineProps<{ email: string }>();
const shared = useShared();
const form = useForm({});
const resend = () => form.post(shared.value.routes['verification.send']);
const logout = () => router.post(shared.value.routes.logout);
</script>

<template>
  <AuthLayout title="Verifikasi email Anda">
    <div class="card space-y-4 text-sm text-slate-700">
      <p>
        Kami telah mengirim tautan verifikasi ke <strong class="text-slate-900">{{ email }}</strong>.
        Buka email tersebut dan klik tautannya untuk mengaktifkan akun.
      </p>
      <p class="text-slate-500">Tidak menerima email? Periksa folder spam, atau kirim ulang.</p>
      <div class="flex flex-col gap-3 sm:flex-row">
        <button type="button" class="btn btn-primary" :disabled="form.processing" @click="resend">Kirim ulang email verifikasi</button>
        <button type="button" class="btn btn-outline" @click="logout">Keluar</button>
      </div>
    </div>
  </AuthLayout>
</template>
