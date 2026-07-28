<script setup lang="ts">
const props = defineProps<{
  value: string | null | undefined
  map: Record<string, [string, string]>
}>()

const entry = computed(() => props.map[props.value ?? ''] ?? [props.value ?? '—', 'pill-gray'])
</script>

<template>
  <span class="pill" :class="entry[1]">{{ entry[0] }}</span>
</template>
