<script setup lang="ts">
defineProps<{ modelValue: boolean; label?: string; disabled?: boolean }>()
const emit = defineEmits<{ (e: 'update:modelValue', v: boolean): void }>()
</script>

<template>
  <label class="switch" :style="disabled ? 'opacity:.55;cursor:not-allowed' : ''">
    <input
      type="checkbox" :checked="modelValue" :disabled="disabled"
      @change="emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
    >
    <span class="track" />
    <span v-if="label" style="font-size:14px;font-weight:700;color:var(--head)">{{ label }}</span>
  </label>
</template>
