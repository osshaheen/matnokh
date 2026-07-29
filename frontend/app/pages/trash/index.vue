<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const { can, sys } = useAuth()
const api = useApi()
const toast = useToast()
const { confirm } = useConfirm()

const summary = ref<any[]>([])
const totalDeleted = ref(0)
const type = ref('orders')
const items = ref<any[]>([])
const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0, label: '' })
const search = ref('')
const page = ref(1)
const loading = ref(false)
const busy = ref(false)

async function loadSummary() {
  try {
    const res = await api.get<{ data: any[]; total: number }>('/trash/summary')
    summary.value = res.data
    totalDeleted.value = res.total
    // land on a bucket that actually has something in it
    if (!items.value.length) {
      const first = res.data.find(t => t.count > 0)
      if (first && first.type !== type.value) type.value = first.type
    }
  } catch (e) {
    toast.error(apiError(e, 'تعذّر تحميل سلّة المحذوفات'))
  }
}

async function load() {
  loading.value = true
  try {
    const res = await api.get<any>(`/trash/${type.value}`, { search: search.value, page: page.value })
    items.value = res.data
    meta.value = { ...res.meta }
  } catch (e) {
    items.value = []
    toast.error(apiError(e))
  } finally {
    loading.value = false
  }
}

let timer: ReturnType<typeof setTimeout>
watch([type, search], () => {
  page.value = 1
  clearTimeout(timer)
  timer = setTimeout(load, 250)
})
watch(page, load)

onMounted(async () => {
  await loadSummary()
  await load()
})

async function restore(row: any) {
  if (!await confirm({ title: `استعادة «${row.title}»؟`, confirmText: 'استعادة' })) return
  busy.value = true
  try {
    await api.post(`/trash/${type.value}/${row.id}/restore`, {})
    toast.success('تمت الاستعادة')
    await Promise.all([load(), loadSummary()])
  } catch (e) {
    toast.error(apiError(e))
  } finally {
    busy.value = false
  }
}

async function forceDelete(row: any) {
  const ok = await confirm({
    title: `حذف «${row.title}» نهائياً؟`,
    text: 'لا يمكن التراجع عن هذا الإجراء.',
    danger: true,
    confirmText: 'حذف نهائي',
  })
  if (!ok) return

  busy.value = true
  try {
    await api.del(`/trash/${type.value}/${row.id}`)
    toast.success('تم الحذف نهائياً')
    await Promise.all([load(), loadSummary()])
  } catch (e) {
    toast.error(apiError(e))
  } finally {
    busy.value = false
  }
}

async function emptyBucket() {
  const ok = await confirm({
    title: `تفريغ سلّة «${meta.value.label}»؟`,
    text: `سيُحذف ${meta.value.total} عنصراً نهائياً بلا رجعة.`,
    danger: true,
    confirmText: 'تفريغ السلّة',
  })
  if (!ok) return

  busy.value = true
  try {
    const res = await api.post<{ message: string }>(`/trash/${type.value}/empty`, {})
    toast.success(res.message)
    await Promise.all([load(), loadSummary()])
  } catch (e) {
    toast.error(apiError(e))
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div>
    <PageHeader title="سلّة المحذوفات" :subtitle="`${num(totalDeleted)} عنصر محذوف في كل الوحدات`">
      <template #actions>
        <button
          v-if="can('trash.delete') && meta.total" class="btn btn-danger"
          :disabled="busy" @click="emptyBucket"
        ><Icon name="trash" /> تفريغ هذه السلّة</button>
      </template>
    </PageHeader>

    <div v-if="!sys.restore_enabled" class="card" style="padding:14px 18px;margin-bottom:16px;background:#f2ead9;border-color:#e6dbc2">
      <span style="font-weight:700;color:#9c7e4e"><Icon name="alert" /> الاستعادة معطّلة من إعدادات النظام — يمكنك التصفّح فقط.</span>
    </div>

    <div class="tabs" style="overflow-x:auto">
      <button
        v-for="t in summary" :key="t.type"
        class="tab" :class="{ active: type === t.type }"
        @click="type = t.type"
      >
        {{ t.label }}
        <span v-if="t.count" class="pill pill-terra" style="margin-right:6px;padding:1px 8px">{{ num(t.count) }}</span>
      </button>
    </div>

    <div class="toolbar">
      <input v-model="search" class="input input-sm grow" placeholder="بحث ضمن المحذوفات…">
    </div>

    <div class="card">
      <div v-if="loading" style="padding:20px;display:flex;flex-direction:column;gap:12px">
        <div v-for="i in 4" :key="i" class="skeleton" style="height:44px" />
      </div>

      <EmptyState v-else-if="!items.length" icon="sparkles" title="السلّة فارغة" text="لا توجد عناصر محذوفة في هذه الوحدة" />

      <div v-else>
        <div
          v-for="row in items" :key="row.id"
          style="display:flex;align-items:center;gap:12px;padding:14px 18px;border-bottom:1px solid var(--line)"
        >
          <div style="min-width:0;flex:1">
            <div style="font-weight:700;color:var(--head)">{{ row.title }}</div>
            <div class="muted" style="font-size:12px">{{ row.subtitle }}</div>
          </div>
          <div class="muted" style="font-size:12px;white-space:nowrap">حُذف {{ ago(row.deleted_at) }}</div>
          <div class="row-actions">
            <button
              v-if="can('trash.update') && sys.restore_enabled"
              class="btn btn-ghost btn-sm" :disabled="busy" @click="restore(row)"
            ><Icon name="undo" /> استعادة</button>
            <button
              v-if="can('trash.delete')" class="icon-btn danger" title="حذف نهائي"
              :disabled="busy" @click="forceDelete(row)"
            ><Icon name="trash" /></button>
          </div>
        </div>
      </div>
    </div>

    <AppPagination :meta="meta" @change="page = $event" />
  </div>
</template>
