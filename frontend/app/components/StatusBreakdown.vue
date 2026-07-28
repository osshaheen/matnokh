<script setup lang="ts">
const props = defineProps<{ counts: Record<string, number> }>()

const total = computed(() => Object.values(props.counts).reduce((a, b) => a + b, 0))

const COLORS: Record<string, string> = {
  pending: '#d9c8a9',
  accepted: '#9fbcd0',
  picked_up: '#8fb3c9',
  on_the_way: '#7da2b8',
  delivered: '#5c8d76',
  canceled: '#c98d6b',
  returned: '#b98a76',
}

const rows = computed(() =>
  Object.entries(props.counts)
    .map(([key, count]) => ({
      key,
      count,
      label: ORDER_STATUS[key]?.[0] ?? key,
      color: COLORS[key] ?? 'var(--sage)',
      percent: total.value ? Math.round((count / total.value) * 100) : 0,
    }))
    .sort((a, b) => b.count - a.count),
)
</script>

<template>
  <div>
    <div style="display:flex;height:10px;border-radius:20px;overflow:hidden;background:var(--card2);margin-bottom:16px">
      <div
        v-for="r in rows" :key="r.key"
        :style="{ width: r.percent + '%', background: r.color }"
        :title="`${r.label}: ${r.count}`"
      />
    </div>

    <div style="display:flex;flex-direction:column;gap:9px">
      <div v-for="r in rows" :key="r.key" style="display:flex;align-items:center;gap:9px;font-size:13px">
        <span :style="{ width: '9px', height: '9px', borderRadius: '3px', background: r.color, flexShrink: 0 }" />
        <span style="flex:1;font-weight:700;color:var(--head)">{{ r.label }}</span>
        <span class="num muted">{{ r.percent }}%</span>
        <span class="num" style="font-weight:800;min-width:34px;text-align:left">{{ num(r.count) }}</span>
      </div>
    </div>
  </div>
</template>
