<script setup lang="ts">
/**
 * Type-ahead picker backed by a paginated list endpoint — used where a plain
 * <select> would mean loading thousands of rows (customers, merchants).
 */
const props = withDefaults(defineProps<{
  modelValue: number | string | null
  endpoint: string
  labelKey?: string
  subKey?: string
  placeholder?: string
  initialLabel?: string
  disabled?: boolean
}>(), { labelKey: 'name', subKey: 'phone', placeholder: 'ابحث…' })

const emit = defineEmits<{ (e: 'update:modelValue', v: number | null): void }>()

const api = useApi()
const open = ref(false)
const term = ref('')
const items = ref<any[]>([])
const loading = ref(false)
const selectedLabel = ref(props.initialLabel ?? '')
const root = ref<HTMLElement | null>(null)

async function load() {
  loading.value = true
  try {
    const res = await api.get<any>(props.endpoint, { search: term.value, per_page: 10 })
    items.value = res.data ?? []
  } catch {
    items.value = []
  } finally {
    loading.value = false
  }
}

let timer: ReturnType<typeof setTimeout>
watch(term, () => {
  clearTimeout(timer)
  timer = setTimeout(load, 260)
})

watch(() => props.initialLabel, v => { if (v) selectedLabel.value = v })
watch(() => props.modelValue, v => { if (v === null) selectedLabel.value = '' })

function choose(item: any) {
  selectedLabel.value = item[props.labelKey] ?? ''
  emit('update:modelValue', item.id)
  open.value = false
  term.value = ''
}

function clear() {
  selectedLabel.value = ''
  emit('update:modelValue', null)
}

function focus() {
  if (props.disabled) return
  open.value = true
  if (!items.value.length) load()
}

function onDocClick(e: MouseEvent) {
  if (root.value && !root.value.contains(e.target as Node)) open.value = false
}

onMounted(() => document.addEventListener('click', onDocClick))
onUnmounted(() => document.removeEventListener('click', onDocClick))
</script>

<template>
  <div ref="root" style="position:relative">
    <div v-if="modelValue && selectedLabel" class="input" style="display:flex;align-items:center;gap:8px">
      <span style="flex:1;font-weight:700;color:var(--head);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ selectedLabel }}</span>
      <button type="button" class="muted" style="font-size:16px" :disabled="disabled" @click="clear">✕</button>
    </div>

    <input
      v-else v-model="term" class="input" :placeholder="placeholder" :disabled="disabled"
      @focus="focus"
    >

    <div
      v-if="open && !(modelValue && selectedLabel)"
      class="card"
      style="position:absolute;top:calc(100% + 6px);right:0;left:0;z-index:20;max-height:230px;overflow-y:auto;padding:6px"
    >
      <div v-if="loading" class="muted" style="padding:12px;text-align:center;font-size:13px">جارٍ البحث…</div>
      <div v-else-if="!items.length" class="muted" style="padding:12px;text-align:center;font-size:13px">لا توجد نتائج</div>
      <button
        v-for="item in items" :key="item.id" type="button"
        style="display:block;width:100%;text-align:right;padding:9px 12px;border-radius:11px;font-size:14px"
        class="sidebar-link"
        @click="choose(item)"
      >
        <span style="font-weight:700">{{ item[labelKey] }}</span>
        <span v-if="item[subKey]" class="muted num" style="font-size:12px"> · {{ item[subKey] }}</span>
      </button>
    </div>
  </div>
</template>
