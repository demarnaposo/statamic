<script setup lang="ts">
defineProps<{ name: string; light?: boolean }>();
</script>

<template>
  <span class="inline-flex items-center gap-2">
    <svg aria-hidden="true" viewBox="0 0 32 32" class="h-8 w-8 shrink-0">
      <rect width="32" height="32" rx="8" :class="light ? 'fill-white/10' : 'fill-brand-700'" />
      <path d="M16 6l8 3.5v6c0 5-3.4 8.9-8 10.5-4.6-1.6-8-5.5-8-10.5v-6L16 6z" class="fill-accent-400" />
      <path d="M12.2 16.2l2.6 2.6 5-5.2" fill="none" stroke="#0d2647" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
    <span class="text-lg font-bold tracking-tight" :class="light ? 'text-white' : 'text-brand-900'">{{ name }}</span>
  </span>
</template>
