<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useShared } from '../lib/page';
import FlashMessage from './FlashMessage.vue';
import Logo from './Logo.vue';

defineProps<{ title: string; subtitle?: string }>();
const shared = useShared();
</script>

<template>
  <Head :title="title" />
  <div class="flex min-h-screen flex-col bg-brand-950 lg:flex-row">
    <aside class="relative overflow-hidden px-6 py-8 text-white lg:flex lg:w-5/12 lg:flex-col lg:justify-between lg:px-12 lg:py-12">
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(47,107,184,0.45),transparent_60%)]" aria-hidden="true" />
      <a :href="shared.app.landing_url" class="relative inline-flex">
        <Logo :name="shared.app.name" light />
        <span class="sr-only"> — kembali ke beranda</span>
      </a>
      <div class="relative mt-8 hidden max-w-md lg:block">
        <p class="text-sm font-semibold tracking-wide text-accent-400 uppercase">Portal Peserta</p>
        <p class="mt-3 text-3xl leading-tight font-bold">Kelola pelatihan dan sertifikasi Anda di satu tempat.</p>
        <ul class="mt-8 space-y-3 text-brand-100">
          <li class="flex gap-3"><span class="text-accent-400" aria-hidden="true">✓</span> Simpan hasil assessment &amp; rekomendasi program</li>
          <li class="flex gap-3"><span class="text-accent-400" aria-hidden="true">✓</span> Pantau pesanan dan jadwal batch</li>
          <li class="flex gap-3"><span class="text-accent-400" aria-hidden="true">✓</span> Unduh dokumen &amp; sertifikat</li>
        </ul>
      </div>
      <p class="relative mt-8 hidden text-xs text-brand-300 lg:block">© {{ new Date().getFullYear() }} {{ shared.app.name }}</p>
    </aside>

    <main id="konten" class="flex flex-1 items-start justify-center bg-slate-50 px-4 py-10 sm:px-6 lg:items-center lg:rounded-l-3xl">
      <div class="w-full max-w-md">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ title }}</h1>
        <p v-if="subtitle" class="mt-2 text-sm text-slate-600">{{ subtitle }}</p>
        <div class="mt-6 space-y-4">
          <FlashMessage :message="shared.flash.status" />
          <FlashMessage v-if="shared.assessment?.claimed" message="Hasil assessment Anda telah disimpan ke akun." />
          <FlashMessage
            v-if="shared.assessment?.pending"
            tone="info"
            message="Hasil assessment Anda akan otomatis tersimpan ke akun setelah Anda masuk atau mendaftar."
          />
        </div>
        <div class="mt-6">
          <slot />
        </div>
      </div>
    </main>
  </div>
</template>
