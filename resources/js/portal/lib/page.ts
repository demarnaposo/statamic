import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { SharedProps } from '../types';

export const useShared = () => {
  const page = usePage<SharedProps>();
  return computed(() => page.props);
};
