<script setup lang="ts">
import { computed } from 'vue';
import PortalLayout from '../components/PortalLayout.vue';
import { useShared } from '../lib/page';

const props = defineProps<{ section: 'orders' | 'programs' }>();
const shared = useShared();
const copy = computed(() =>
  props.section === 'orders'
    ? { title: 'Pesanan', body: 'Daftar pesanan, status pembayaran, dan faktur akan tampil di sini setelah fitur checkout tersedia.' }
    : { title: 'Program saya', body: 'Program yang Anda ikuti, jadwal kelas, unggah dokumen persyaratan, dan sertifikat akan tampil di sini.' },
);
</script>

<template>
  <PortalLayout :title="copy.title" :active="section">
    <h1 class="page-title">{{ copy.title }}</h1>
    <div class="card mt-8 max-w-2xl">
      <p class="inline-flex rounded-full bg-accent-100 px-2.5 py-0.5 text-xs font-semibold text-accent-700">Segera hadir</p>
      <p class="mt-3 text-slate-700">{{ copy.body }}</p>
      <a :href="`${shared.app.landing_url}/program`" class="btn btn-primary mt-5">Lihat katalog program</a>
    </div>
  </PortalLayout>
</template>
