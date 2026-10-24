<script setup lang="ts">
import PortalLayout from '../../components/PortalLayout.vue';
import { formatDate, formatRupiah, modeLabel } from '../../lib/format';
import { useShared } from '../../lib/page';
import type { ProgramCard } from '../../types';

defineProps<{
  qty: number;
  batch: {
    id: string;
    code: string;
    title: string;
    start_date: string;
    end_date: string;
    mode: string;
    location: string | null;
    status: 'open' | 'closed' | 'full';
    seats_left: number;
    price: number;
    total: number;
    program: ProgramCard;
  } | null;
}>();
const shared = useShared();
</script>

<template>
  <PortalLayout title="Checkout" active="orders">
    <h1 class="page-title">Checkout segera hadir</h1>
    <p class="mt-2 max-w-2xl text-slate-600">
      Pembayaran online sedang kami siapkan. Ringkasan pilihan Anda tersimpan di bawah ini; hubungi kami bila ingin mendaftar sekarang.
    </p>

    <section v-if="batch" class="card mt-8 max-w-2xl" aria-labelledby="judul-ringkasan">
      <p class="eyebrow">Ringkasan pendaftaran</p>
      <h2 id="judul-ringkasan" class="mt-1 text-lg font-semibold text-slate-900">{{ batch.program.title }}</h2>
      <dl class="mt-5 grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
        <div><dt class="text-slate-500">Batch</dt><dd class="font-medium text-slate-900">{{ batch.code }}</dd></div>
        <div><dt class="text-slate-500">Jadwal</dt><dd class="font-medium text-slate-900">{{ formatDate(batch.start_date) }}<template v-if="batch.end_date !== batch.start_date"> – {{ formatDate(batch.end_date) }}</template></dd></div>
        <div><dt class="text-slate-500">Mode</dt><dd class="font-medium text-slate-900">{{ modeLabel(batch.mode) }}<template v-if="batch.location"> · {{ batch.location }}</template></dd></div>
        <div><dt class="text-slate-500">Jumlah peserta</dt><dd class="font-medium text-slate-900">{{ qty }} orang</dd></div>
        <div><dt class="text-slate-500">Harga per peserta</dt><dd class="font-medium text-slate-900">{{ formatRupiah(batch.price) }}</dd></div>
        <div><dt class="text-slate-500">Estimasi total</dt><dd class="text-lg font-bold text-brand-900">{{ formatRupiah(batch.total) }}</dd></div>
      </dl>
      <p v-if="batch.status !== 'open'" class="mt-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900">
        Batch ini {{ batch.status === 'full' ? 'sudah penuh' : 'sudah ditutup' }}. Silakan pilih batch lain.
      </p>
      <p class="mt-4 text-xs text-slate-500">Harga sudah termasuk PPN. Total final dihitung ulang saat checkout.</p>
      <a :href="batch.program.url" class="btn btn-outline mt-5">Kembali ke halaman program</a>
    </section>

    <div v-else class="card mt-8 max-w-2xl">
      <p class="font-medium text-slate-900">Batch tidak ditemukan.</p>
      <p class="mt-1 text-sm text-slate-600">Tautan mungkin sudah tidak berlaku. Pilih program dan batch dari katalog.</p>
      <a :href="`${shared.app.landing_url}/program`" class="btn btn-primary mt-5">Lihat katalog program</a>
    </div>
  </PortalLayout>
</template>
