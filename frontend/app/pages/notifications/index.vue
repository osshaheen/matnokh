<script setup lang="ts">
import type { Column } from '~/types/table'

definePageMeta({ middleware: 'auth' })

const { can } = useAuth()
const api = useApi()
const { confirm } = useConfirm()

const notifications = useResource('/notifications', { status: '', audience: '' })

const columns: Column[] = [
  { key: 'title', label: 'الإشعار', sortable: true },
  { key: 'audience', label: 'الجمهور' },
  { key: 'status', label: 'الحالة' },
  { key: 'sent_count', label: 'الوصول' },
  { key: 'sent_at', label: 'وقت الإرسال', sortable: true },
  { key: 'actions', label: '', width: '140px' },
]

const show = ref(false)
const editing = ref<any>(null)
const form = reactive({ title: '', body: '', audience: 'all', send_now: false })
const audienceSize = ref<number | null>(null)

function open(row: any = null) {
  editing.value = row
  Object.assign(form, {
    title: row?.title ?? '', body: row?.body ?? '',
    audience: row?.audience ?? 'all', send_now: false,
  })
  show.value = true
}

// live estimate of how many accounts the chosen audience covers
watch([() => form.audience, show], async () => {
  if (!show.value) return
  audienceSize.value = null
  try {
    const res = await api.get<{ data: { size: number } }>('/notifications/audience-size', { audience: form.audience })
    audienceSize.value = res.data.size
  } catch {
    audienceSize.value = null
  }
}, { immediate: true })

async function submit() {
  const res = editing.value
    ? await notifications.update(editing.value.id, { title: form.title, body: form.body, audience: form.audience })
    : await notifications.create({ ...form }, form.send_now ? 'تم إرسال الإشعار' : 'تم حفظ المسوّدة')
  if (res) show.value = false
}

async function send(row: any) {
  const ok = await confirm({
    title: 'إرسال الإشعار؟',
    text: `«${row.title}» إلى: ${AUDIENCE[row.audience]}`,
    confirmText: 'إرسال',
  })
  if (!ok) return
  await notifications.submit(() => api.post(`/notifications/${row.id}/send`, {}), 'تم إرسال الإشعار')
}

async function remove(row: any) {
  if (!await confirm({ title: `حذف الإشعار «${row.title}»؟`, danger: true, confirmText: 'حذف' })) return
  await notifications.remove(row.id)
}
</script>

<template>
  <div>
    <PageHeader title="الإشعارات" :subtitle="`${num(notifications.meta.total)} إشعار`">
      <template #actions>
        <button v-if="can('notification.create')" class="btn" @click="open()"><Icon name="plus" :size="15" /> إشعار جديد</button>
      </template>
    </PageHeader>

    <div class="toolbar">
      <input v-model="notifications.query.search" class="input input-sm grow" placeholder="بحث بالعنوان أو النص…">

      <select v-model="notifications.query.status" class="input input-sm">
        <option value="">كل الحالات</option>
        <option v-for="o in options(NOTIFICATION_STATUS)" :key="o.value" :value="o.value">{{ o.label }}</option>
      </select>

      <select v-model="notifications.query.audience" class="input input-sm">
        <option value="">كل الجماهير</option>
        <option v-for="o in options(AUDIENCE)" :key="o.value" :value="o.value">{{ o.label }}</option>
      </select>

      <button class="btn btn-ghost btn-sm" @click="notifications.reset()">مسح الفلاتر</button>
    </div>

    <DataTable
      :columns="columns" :rows="notifications.items" :loading="notifications.loading"
      :sort="notifications.query.sort" :dir="notifications.query.dir"
      empty="لا توجد إشعارات" empty-icon="bell"
      @sort="notifications.sortBy"
    >
      <template #cell-title="{ row }">
        <div style="font-weight:700;color:var(--head)">{{ row.title }}</div>
        <div class="muted" style="font-size:12px;max-width:420px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ row.body }}</div>
      </template>
      <template #cell-audience="{ row }"><span class="pill pill-blue">{{ AUDIENCE[row.audience] ?? row.audience }}</span></template>
      <template #cell-status="{ row }"><StatusPill :value="row.status" :map="NOTIFICATION_STATUS" /></template>
      <template #cell-sent_count="{ row }">
        <span class="num">{{ row.status === 'sent' ? num(row.sent_count) : '—' }}</span>
      </template>
      <template #cell-sent_at="{ row }"><span class="muted" style="font-size:13px">{{ dateTime(row.sent_at) }}</span></template>
      <template #cell-actions="{ row }">
        <div class="row-actions">
          <button
            v-if="can('notification.create') && row.status === 'draft'"
            class="btn btn-sm" :disabled="notifications.saving" @click="send(row)"
          >إرسال</button>
          <button v-if="can('notification.update') && row.status === 'draft'" class="icon-btn" title="تعديل" @click="open(row)"><Icon name="edit" /></button>
          <button v-if="can('notification.delete')" class="icon-btn danger" title="حذف" @click="remove(row)"><Icon name="trash" /></button>
        </div>
      </template>
    </DataTable>

    <AppPagination :meta="notifications.meta" @change="notifications.query.page = $event" />

    <AppModal v-model="show" :title="editing ? 'تعديل الإشعار' : 'إشعار جديد'" width="600px">
      <form id="notification-form" class="form-grid" @submit.prevent="submit">
        <FormField label="العنوان *" full><input v-model="form.title" class="input" required maxlength="255"></FormField>
        <FormField label="نص الإشعار *" full>
          <textarea v-model="form.body" class="input" required maxlength="2000" />
        </FormField>
        <FormField label="الجمهور" full :hint="audienceSize !== null ? `سيصل إلى ${num(audienceSize)} حساباً` : ''">
          <select v-model="form.audience" class="input">
            <option v-for="o in options(AUDIENCE)" :key="o.value" :value="o.value">{{ o.label }}</option>
          </select>
        </FormField>
        <FormField v-if="!editing" label="الإرسال" full>
          <AppSwitch v-model="form.send_now" label="إرسال فوري بعد الحفظ" />
        </FormField>
      </form>

      <template #footer>
        <button type="submit" form="notification-form" class="btn" :disabled="notifications.saving">
          {{ form.send_now && !editing ? 'حفظ وإرسال' : 'حفظ' }}
        </button>
        <button class="btn btn-ghost" @click="show = false">إلغاء</button>
      </template>
    </AppModal>
  </div>
</template>
