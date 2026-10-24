<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PortalLayout from '../components/PortalLayout.vue';
import ProgramCardView from '../components/ProgramCardView.vue';
import { formatDate } from '../lib/format';
import { useShared } from '../lib/page';
import type { AssessmentResult, ProgramCard } from '../types';

const props = defineProps<{ latestAssessment: AssessmentResult | null; recommendations: ProgramCard[] }>();
const shared = useShared();

const firstName = computed(() => shared.value.auth.customer?.name.split(/\s+/)[0] ?? '');
const greeting = computed(() => {
  const hour = Number(new Intl.DateTimeFormat('id-ID', { hour: 'numeric', hourCycle: 'h23', timeZone: 'Asia/Jakarta' }).format(new Date()));
  return hour < 11 ? 'Selamat pagi' : hour < 15 ? 'Selamat siang' : hour < 18 ? 'Selamat sore' : 'Selamat malam';
});
const maxScore = computed(() => Math.max(1, ...(props.latestAssessment?.scores.map((s) => s.score) ?? [1])));
</script>

<template>
  <PortalLayout title="Dashboard" active="dashboard">
    <section class="flex flex-col gap-4 rounded-2xl bg-brand-900 px-6 py-8 text-white sm:flex-row sm:items-center sm:justify-between sm:px-8">
      <div>
        <p class="text-sm font-semibold tracking-wide text-accent-400 uppercase">Portal Peserta</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">{{ greeting }}, {{ firstName }}!</h1>
        <p class="mt-2 max-w-xl text-brand-100">Lanjutkan perjalanan sertifikasi Anda: lihat rekomendasi, pilih batch, dan pantau progres di sini.</p>
      </div>
      <a :href="`${shared.app.landing_url}/program`" class="btn btn-accent shrink-0">Jelajahi katalog program</a>
    </section>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
      <section class="card lg:col-span-2" aria-labelledby="judul-assessment">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="eyebrow">Assessment terbaru</p>
            <h2 id="judul-assessment" class="mt-1 text-lg font-semibold text-slate-900">
              {{ latestAssessment ? `Rekomendasi: ${latestAssessment.recommended_category.title}` : 'Belum ada hasil assessment' }}
            </h2>
          </div>
          <Link v-if="latestAssessment" :href="shared.routes.assessments" class="link shrink-0 text-sm">Riwayat</Link>
        </div>

        <template v-if="latestAssessment">
          <p class="mt-1 text-sm text-slate-500">Diambil {{ formatDate(latestAssessment.taken_at) }}</p>
          <ul class="mt-5 space-y-3" aria-label="Skor per bidang">
            <li v-for="s in latestAssessment.scores" :key="s.slug">
              <div class="flex justify-between text-sm">
                <span :class="s.slug === latestAssessment.recommended_category.slug ? 'font-semibold text-slate-900' : 'text-slate-600'">{{ s.title }}</span>
                <span class="text-slate-500">{{ s.score }} poin</span>
              </div>
              <div class="mt-1 h-2 rounded-full bg-slate-100" aria-hidden="true">
                <div class="h-2 rounded-full" :class="s.slug === latestAssessment.recommended_category.slug ? 'bg-accent-400' : 'bg-brand-300'" :style="{ width: `${(s.score / maxScore) * 100}%` }" />
              </div>
            </li>
          </ul>
        </template>
        <div v-else class="mt-3 text-sm text-slate-600">
          <p>Ikuti assessment singkat (± 3 menit) untuk mengetahui program sertifikasi yang paling cocok dengan Anda.</p>
          <a :href="`${shared.app.landing_url}/assessment`" class="btn btn-primary mt-4">Mulai assessment</a>
        </div>
      </section>

      <div class="grid gap-6">
        <section class="card" aria-labelledby="judul-pesanan">
          <h2 id="judul-pesanan" class="text-base font-semibold text-slate-900">Pesanan</h2>
          <p class="mt-2 text-sm text-slate-600">Belum ada pesanan. Riwayat pembayaran dan faktur akan tampil di sini.</p>
          <p class="mt-3 inline-flex rounded-full bg-accent-100 px-2.5 py-0.5 text-xs font-semibold text-accent-700">Segera hadir</p>
        </section>
        <section class="card" aria-labelledby="judul-program">
          <h2 id="judul-program" class="text-base font-semibold text-slate-900">Program saya</h2>
          <p class="mt-2 text-sm text-slate-600">Jadwal kelas, dokumen persyaratan, dan sertifikat Anda akan tampil di sini.</p>
          <p class="mt-3 inline-flex rounded-full bg-accent-100 px-2.5 py-0.5 text-xs font-semibold text-accent-700">Segera hadir</p>
        </section>
      </div>
    </div>

    <section v-if="recommendations.length" class="mt-10" aria-labelledby="judul-rekomendasi">
      <h2 id="judul-rekomendasi" class="text-xl font-bold tracking-tight text-slate-900">Program yang direkomendasikan untuk Anda</h2>
      <ul class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <li v-for="p in recommendations" :key="p.slug"><ProgramCardView :program="p" /></li>
      </ul>
    </section>
  </PortalLayout>
</template>
