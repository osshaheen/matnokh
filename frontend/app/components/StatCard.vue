<script setup lang="ts">
withDefaults(defineProps<{
  label: string
  value: string | number
  icon?: string
  grad?: string
  hint?: string
  loading?: boolean
}>(), { icon: 'chart', grad: 'var(--grad-green)' })
</script>

<template>
  <div class="card" style="padding:20px">
    <div
      :style="{ width: '44px', height: '44px', borderRadius: '14px', background: grad, display: 'flex',
                alignItems: 'center', justifyContent: 'center', color: '#fff', marginBottom: '12px' }"
    ><Icon :name="icon" :size="22" /></div>

    <div v-if="loading" class="skeleton" style="height:28px;width:70%" />
    <div v-else style="font-size:26px;font-weight:800;color:var(--head)" class="num">{{ value }}</div>

    <div class="muted" style="font-size:13px;margin-top:2px">{{ label }}</div>
    <div v-if="hint" style="font-size:12px;font-weight:700;color:var(--green);margin-top:4px">{{ hint }}</div>
  </div>
</template>
