<script setup lang="ts">
const props = defineProps<{
  meta: { current_page: number; last_page: number; per_page?: number; total: number }
}>()
const emit = defineEmits<{ (e: 'change', page: number): void }>()

/** Windowed page numbers: 1 … 4 5 [6] 7 8 … 20 */
const pages = computed(() => {
  const { current_page: cur, last_page: last } = props.meta
  const out: (number | '…')[] = []
  for (let p = 1; p <= last; p++) {
    if (p === 1 || p === last || Math.abs(p - cur) <= 1) out.push(p)
    else if (out[out.length - 1] !== '…') out.push('…')
  }
  return out
})

const from = computed(() => (props.meta.total ? (props.meta.current_page - 1) * (props.meta.per_page ?? 15) + 1 : 0))
const to = computed(() => Math.min(props.meta.current_page * (props.meta.per_page ?? 15), props.meta.total))
</script>

<template>
  <div v-if="meta.total" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:14px">
    <div class="muted" style="font-size:13px">
      عرض <span class="num">{{ from }}</span>–<span class="num">{{ to }}</span>
      من <span class="num">{{ num(meta.total) }}</span>
    </div>

    <div v-if="meta.last_page > 1" style="display:flex;gap:5px;align-items:center">
      <button class="icon-btn" :disabled="meta.current_page <= 1" @click="emit('change', meta.current_page - 1)">‹</button>
      <template v-for="(p, i) in pages" :key="i">
        <span v-if="p === '…'" class="muted" style="padding:0 4px">…</span>
        <button
          v-else class="icon-btn" style="width:auto;min-width:32px;padding:0 8px;font-size:13px;font-weight:700"
          :style="p === meta.current_page ? 'background:var(--grad-green);color:#fff;border-color:transparent' : ''"
          @click="emit('change', p as number)"
        >{{ p }}</button>
      </template>
      <button class="icon-btn" :disabled="meta.current_page >= meta.last_page" @click="emit('change', meta.current_page + 1)">›</button>
    </div>
  </div>
</template>
