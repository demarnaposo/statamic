<script setup lang="ts">
import PortalLayout from '../../components/PortalLayout.vue';
import { formatDateTime } from '../../lib/format';
import type { AssessmentResult } from '../../types';

defineProps<{ results: AssessmentResult[]; assessmentUrl: string }>();
</script>

<template>
  <PortalLayout title="Assessment saya" active="assessments">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="page-title">Assessment saya</h1>
        <p class="mt-2 text-slate-600">Riwayat hasil assessment yang tersimpan di akun Anda.</p>
      </div>
      <a :href="assessmentUrl" class="btn btn-outline shrink-0">Ikuti assessment lagi</a>
    </div>

    <div v-if="!results.length" class="card mt-8 text-center">
      <p class="font-medium text-slate-900">Belum ada hasil assessment.</p>
      <p class="mt-1 text-sm text-slate-600">Hasil assessment yang Anda ikuti di situs kami bisa disimpan ke akun lewat tombol "Simpan hasil".</p>
      <a :href="assessmentUrl" class="btn btn-primary mt-5">Mulai assessment</a>
    </div>

    <ol v-else class="mt-8 space-y-4">
      <li v-for="r in results" :key="r.id" class="card">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between">
          <h2 class="text-base font-semibold text-slate-900">Rekomendasi: {{ r.recommended_category.title }}</h2>
          <p class="text-sm text-slate-500"><time :datetime="r.taken_at">{{ formatDateTime(r.taken_at) }}</time></p>
        </div>
        <ul class="mt-3 flex flex-wrap gap-2" aria-label="Skor per bidang">
          <li
            v-for="s in r.scores"
            :key="s.slug"
            class="rounded-full px-3 py-1 text-xs font-medium"
            :class="s.slug === r.recommended_category.slug ? 'bg-accent-100 text-accent-700' : 'bg-slate-100 text-slate-600'"
          >
            {{ s.title }}: {{ s.score }}
          </li>
        </ul>
      </li>
    </ol>
  </PortalLayout>
</template>
