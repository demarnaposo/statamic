<script setup lang="ts">
defineProps<{ message?: string | null; tone?: 'success' | 'info' }>();
</script>

<template>
  <div
    v-if="message"
    role="status"
    class="rounded-lg border px-4 py-3 text-sm"
    :class="tone === 'info' ? 'border-brand-200 bg-brand-50 text-brand-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900'"
  >
    {{ message }}
  </div>
</template>
