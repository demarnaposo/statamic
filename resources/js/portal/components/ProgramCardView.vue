<script setup lang="ts">
import { formatDate, formatRupiah, issuerLabel } from '../lib/format';
import type { ProgramCard } from '../types';

defineProps<{ program: ProgramCard }>();
</script>

<template>
  <article class="flex h-full flex-col rounded-xl border border-slate-200 bg-white p-5 transition-shadow hover:shadow-md">
    <p class="text-xs font-semibold tracking-wide uppercase" :class="program.issuer === 'bnsp' ? 'text-accent-700' : 'text-brand-600'">
      {{ issuerLabel(program.issuer) }}
    </p>
    <h3 class="mt-2 text-base font-semibold text-slate-900">
      <a :href="program.url" class="hover:text-brand-700 focus-visible:outline-offset-4">{{ program.title }}</a>
    </h3>
    <p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ program.summary }}</p>
    <dl class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
      <div v-if="program.level"><dt class="sr-only">Level</dt><dd>{{ program.level }}</dd></div>
      <div v-if="program.duration"><dt class="sr-only">Durasi</dt><dd>{{ program.duration }}</dd></div>
      <div v-if="program.next_batch_date"><dt class="inline">Batch terdekat: </dt><dd class="inline">{{ formatDate(program.next_batch_date) }}</dd></div>
    </dl>
    <div class="mt-auto flex items-end justify-between gap-3 pt-5">
      <p>
        <span v-if="program.promo_active" class="block text-xs text-slate-400 line-through">{{ formatRupiah(program.price) }}</span>
        <span class="text-base font-bold text-brand-900">{{ formatRupiah(program.effective_price) }}</span>
      </p>
      <a :href="program.url" class="text-sm font-semibold text-brand-700 hover:underline" :aria-label="`Lihat detail ${program.title}`">Lihat detail →</a>
    </div>
  </article>
</template>
