<script setup lang="ts">
import { computed, useId } from 'vue';

const model = defineModel<string | null>({ required: true });
const props = defineProps<{
  label: string;
  error?: string;
  type?: string;
  autocomplete?: string;
  hint?: string;
  required?: boolean;
  inputmode?: 'text' | 'email' | 'tel' | 'numeric';
  multiline?: boolean;
}>();

const id = useId();
const describedBy = computed(
  () => [props.hint && `${id}-hint`, props.error && `${id}-error`].filter(Boolean).join(' ') || undefined,
);
const attrs = computed(() => ({
  id,
  class: 'input',
  autocomplete: props.autocomplete,
  required: props.required,
  'aria-invalid': props.error ? ('true' as const) : undefined,
  'aria-describedby': describedBy.value,
}));
</script>

<template>
  <div>
    <label :for="id" class="mb-1.5 block text-sm font-medium text-slate-800">
      {{ label }}<span v-if="required" class="text-red-600" aria-hidden="true"> *</span>
    </label>
    <textarea v-if="multiline" v-model="model" rows="3" v-bind="attrs" />
    <input v-else v-model="model" :type="type ?? 'text'" :inputmode="inputmode" v-bind="attrs" />
    <p v-if="hint" :id="`${id}-hint`" class="mt-1.5 text-xs text-slate-500">{{ hint }}</p>
    <p v-if="error" :id="`${id}-error`" class="mt-1.5 text-sm text-red-700">{{ error }}</p>
  </div>
</template>
