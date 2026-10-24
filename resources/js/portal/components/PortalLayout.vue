<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onUnmounted, ref } from 'vue';
import { useShared } from '../lib/page';
import FlashMessage from './FlashMessage.vue';
import Logo from './Logo.vue';

defineProps<{ title: string; active: 'dashboard' | 'assessments' | 'orders' | 'programs' | 'profile' }>();
const shared = useShared();
const menuOpen = ref(false);

const nav = computed(() => [
  { key: 'dashboard', label: 'Dashboard', href: shared.value.routes.dashboard },
  { key: 'assessments', label: 'Assessment saya', href: shared.value.routes.assessments },
  { key: 'orders', label: 'Pesanan', href: shared.value.routes.orders },
  { key: 'programs', label: 'Program saya', href: shared.value.routes.programs },
  { key: 'profile', label: 'Profil', href: shared.value.routes['profile.edit'] },
]);

const initials = computed(() =>
  (shared.value.auth.customer?.name ?? '?')
    .split(/\s+/)
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase())
    .join(''),
);

const logout = () => router.post(shared.value.routes.logout);
onUnmounted(router.on('navigate', () => (menuOpen.value = false)));
</script>

<template>
  <Head :title="title" />
  <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded focus:bg-white focus:px-3 focus:py-2">
    Lewati ke konten
  </a>
  <header class="bg-brand-900 text-white">
    <div class="container-page flex h-16 items-center justify-between gap-4">
      <Link :href="shared.routes.dashboard" class="shrink-0">
        <Logo :name="shared.app.name" light />
      </Link>

      <nav aria-label="Navigasi portal" class="hidden lg:block">
        <ul class="flex items-center gap-1">
          <li v-for="item in nav" :key="item.key">
            <Link
              :href="item.href"
              class="rounded-md px-3 py-2 text-sm font-medium transition-colors"
              :class="item.key === active ? 'bg-white/10 text-white' : 'text-brand-100 hover:bg-white/5 hover:text-white'"
              :aria-current="item.key === active ? 'page' : undefined"
            >
              {{ item.label }}
            </Link>
          </li>
        </ul>
      </nav>

      <div class="flex items-center gap-3">
        <div class="hidden items-center gap-2 sm:flex">
          <span class="flex h-8 w-8 items-center justify-center rounded-full bg-accent-400 text-xs font-bold text-brand-950" aria-hidden="true">{{ initials }}</span>
          <span class="max-w-40 truncate text-sm font-medium">{{ shared.auth.customer?.name }}</span>
        </div>
        <button type="button" class="hidden rounded-md border border-white/30 px-3 py-1.5 text-sm hover:bg-white/10 lg:inline-flex" @click="logout">
          Keluar
        </button>
        <button
          type="button"
          class="inline-flex h-10 w-10 items-center justify-center rounded-md hover:bg-white/10 lg:hidden"
          :aria-expanded="menuOpen"
          aria-controls="menu-portal"
          @click="menuOpen = !menuOpen"
        >
          <span class="sr-only">{{ menuOpen ? 'Tutup menu' : 'Buka menu' }}</span>
          <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path v-if="menuOpen" d="M6 6l12 12M18 6L6 18" stroke-linecap="round" />
            <path v-else d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round" />
          </svg>
        </button>
      </div>
    </div>

    <nav v-show="menuOpen" id="menu-portal" aria-label="Navigasi portal (seluler)" class="border-t border-white/10 lg:hidden">
      <ul class="container-page space-y-1 py-3">
        <li v-for="item in nav" :key="item.key">
          <Link
            :href="item.href"
            class="block rounded-md px-3 py-2 text-sm font-medium"
            :class="item.key === active ? 'bg-white/10 text-white' : 'text-brand-100 hover:bg-white/5'"
            :aria-current="item.key === active ? 'page' : undefined"
          >
            {{ item.label }}
          </Link>
        </li>
        <li class="border-t border-white/10 pt-2">
          <p class="px-3 py-1 text-xs text-brand-200">Masuk sebagai {{ shared.auth.customer?.email }}</p>
          <button type="button" class="block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-brand-100 hover:bg-white/5" @click="logout">Keluar</button>
        </li>
      </ul>
    </nav>
  </header>

  <main id="konten" class="container-page py-8 sm:py-10">
    <div class="mb-6 space-y-3 empty:hidden">
      <FlashMessage :message="shared.flash.status" />
      <FlashMessage v-if="shared.assessment?.claimed" message="Hasil assessment Anda telah disimpan ke akun." />
    </div>
    <slot />
  </main>

  <footer class="border-t border-slate-200 bg-white">
    <div class="container-page flex flex-col gap-2 py-6 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
      <p>© {{ new Date().getFullYear() }} {{ shared.app.name }}</p>
      <a :href="shared.app.landing_url" class="link">Lihat katalog program</a>
    </div>
  </footer>
</template>
