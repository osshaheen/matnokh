<script setup lang="ts">
defineProps<{ label: string; error?: string; hint?: string; full?: boolean }>()
</script>

<template>
  <div class="field" :class="{ full }">
    <label class="label">{{ label }}</label>
    <slot />
    <span v-if="error" class="error">{{ error }}</span>
    <span v-else-if="hint" class="muted" style="font-size:12px">{{ hint }}</span>
  </div>
</template>
