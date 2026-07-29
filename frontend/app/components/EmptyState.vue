<script setup lang="ts">
withDefaults(defineProps<{ icon?: string; title?: string; text?: string }>(), {
  icon: 'folder',
  title: 'لا توجد بيانات',
  text: '',
})
</script>

<template>
  <div style="padding:44px 20px;text-align:center">
    <div style="margin-bottom:10px;color:var(--sage)"><Icon :name="icon" :size="42" /></div>
    <div style="font-weight:800;color:var(--head);font-size:16px">{{ title }}</div>
    <p v-if="text" class="muted" style="font-size:14px;max-width:420px;margin:4px auto 0">{{ text }}</p>
    <div style="margin-top:14px"><slot /></div>
  </div>
</template>
