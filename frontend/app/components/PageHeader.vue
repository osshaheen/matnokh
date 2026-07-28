<script setup lang="ts">
defineProps<{ title: string; subtitle?: string; back?: string }>()
</script>

<template>
  <div class="page-head">
    <div style="min-width:0">
      <NuxtLink v-if="back" :to="back" class="muted" style="font-size:13px;font-weight:700">→ رجوع</NuxtLink>
      <h1 class="page-title">{{ title }}</h1>
      <p v-if="subtitle" class="muted" style="font-size:14px">{{ subtitle }}</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <slot name="actions" />
    </div>
  </div>
</template>
