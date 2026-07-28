<script setup lang="ts">
type Point = { day: string; orders: number; revenue: number }

const props = withDefaults(defineProps<{
  points: Point[]
  metric?: 'orders' | 'revenue'
  height?: number
}>(), { metric: 'orders', height: 190 })

const W = 600
const H = 200
const PAD = { top: 14, bottom: 26, side: 10 }

const values = computed(() => props.points.map(p => Number(p[props.metric] ?? 0)))
const max = computed(() => Math.max(1, ...values.value))

/** Oldest point sits on the right — reading direction matches the RTL layout. */
function x(i: number): number {
  const n = Math.max(1, props.points.length - 1)
  return W - PAD.side - (i / n) * (W - PAD.side * 2)
}

function y(v: number): number {
  const usable = H - PAD.top - PAD.bottom
  return PAD.top + usable - (v / max.value) * usable
}

const line = computed(() => values.value.map((v, i) => `${i ? 'L' : 'M'}${x(i).toFixed(1)},${y(v).toFixed(1)}`).join(' '))
const area = computed(() => {
  if (!values.value.length) return ''
  const base = H - PAD.bottom
  return `${line.value} L${x(values.value.length - 1).toFixed(1)},${base} L${x(0).toFixed(1)},${base} Z`
})

// only a few labels fit — show roughly every fourth day
const ticks = computed(() =>
  props.points
    .map((p, i) => ({ i, label: p.day.slice(5).replace('-', '/') }))
    .filter(t => t.i % Math.ceil(props.points.length / 5) === 0),
)

const hover = ref<number | null>(null)
const active = computed(() => (hover.value === null ? null : props.points[hover.value]))

function onMove(e: MouseEvent) {
  const box = (e.currentTarget as SVGElement).getBoundingClientRect()
  const ratio = (box.right - e.clientX) / box.width
  const i = Math.round(ratio * (props.points.length - 1))
  hover.value = Math.min(props.points.length - 1, Math.max(0, i))
}
</script>

<template>
  <div style="position:relative">
    <svg
      :viewBox="`0 0 ${W} ${H}`" preserveAspectRatio="none"
      :style="{ width: '100%', height: height + 'px', display: 'block', overflow: 'visible' }"
      @mousemove="onMove" @mouseleave="hover = null"
    >
      <defs>
        <linearGradient id="trendFill" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="#6a9d84" stop-opacity=".28" />
          <stop offset="100%" stop-color="#6a9d84" stop-opacity="0" />
        </linearGradient>
      </defs>

      <line
        v-for="g in 4" :key="g"
        :x1="PAD.side" :x2="W - PAD.side"
        :y1="PAD.top + (g - 1) * ((H - PAD.top - PAD.bottom) / 3)"
        :y2="PAD.top + (g - 1) * ((H - PAD.top - PAD.bottom) / 3)"
        stroke="var(--line)" stroke-width="1" vector-effect="non-scaling-stroke"
      />

      <path :d="area" fill="url(#trendFill)" />
      <path :d="line" fill="none" stroke="#4f7f68" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />

      <template v-if="hover !== null">
        <line
          :x1="x(hover)" :x2="x(hover)" :y1="PAD.top" :y2="H - PAD.bottom"
          stroke="#4f7f68" stroke-width="1" stroke-dasharray="4 4" vector-effect="non-scaling-stroke"
        />
        <circle :cx="x(hover)" :cy="y(values[hover] ?? 0)" r="4.5" fill="#4f7f68" stroke="#fff" stroke-width="2" vector-effect="non-scaling-stroke" />
      </template>

      <text
        v-for="t in ticks" :key="t.i"
        :x="x(t.i)" :y="H - 6" text-anchor="middle"
        font-size="11" fill="var(--muted)" font-family="Cairo, sans-serif"
      >{{ t.label }}</text>
    </svg>

    <div
      v-if="active"
      class="card"
      style="position:absolute;top:0;left:0;padding:8px 12px;font-size:12px;font-weight:700;pointer-events:none"
    >
      <div class="muted">{{ active.day }}</div>
      <div style="color:var(--head)">
        {{ metric === 'orders' ? `${num(active.orders)} طلب` : money(active.revenue) }}
      </div>
    </div>
  </div>
</template>
