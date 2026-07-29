<script setup lang="ts">
import type { Column } from '~/types/table'

definePageMeta({ middleware: 'auth' })

const { can } = useAuth()
const { confirm } = useConfirm()

const withdraws = useResource('/withdraws', { status: '', requester_type: '', method: '' })

const columns: Column[] = [
  { key: 'requester', label: 'مقدّم الطلب' },
  { key: 'amount', label: 'المبلغ', sortable: true },
  { key: 'method', label: 'طريقة الصرف' },
  { key: 'account', label: 'الحساب' },
  { key: 'status', label: 'الحالة', sortable: true },
  { key: 'created_at', label: 'تاريخ الطلب', sortable: true },
  { key: 'actions', label: '', width: '150px' },
]

/** Mirrors the transitions the API allows, so no button 422s. */
const NEXT: Record<string, string[]> = {
  pending: ['approved', 'rejected'],
  approved: ['paid', 'rejected'],
  rejected: [],
  paid: [],
}

async function setStatus(row: any, status: string) {
  const label = WITHDRAW_STATUS[status][0]
  const danger = status === 'rejected'
  const ok = await confirm({
    title: `${danger ? 'رفض' : 'تأكيد'} طلب السحب؟`,
    text: `${row.requester?.name ?? ''} — ${money(row.amount)} <Icon name="arrow" /> ${label}`
      + (status === 'paid' ? '\nسيُخصم المبلغ من رصيد الحساب.' : ''),
    danger,
    confirmText: label,
  })
  if (!ok) return

  await withdraws.patch(`/withdraws/${row.id}/status`, { status }, `تم تحديث الطلب إلى: ${label}`)
}

async function remove(row: any) {
  if (!await confirm({ title: 'حذف طلب السحب؟', danger: true, confirmText: 'حذف' })) return
  await withdraws.remove(row.id)
}

// ── create on behalf ────────────────────────────────────────────────────
const showForm = ref(false)
const form = reactive({
  requester_type: 'driver',
  requester_id: null as number | null,
  amount: 0,
  method: 'bank',
  account_name: '',
  account_number: '',
  bank_name: '',
  note: '',
})

function openForm() {
  Object.assign(form, {
    requester_type: 'driver', requester_id: null, amount: 0, method: 'bank',
    account_name: '', account_number: '', bank_name: '', note: '',
  })
  showForm.value = true
}

watch(() => form.requester_type, () => { form.requester_id = null })

async function submit() {
  const res = await withdraws.create({ ...form }, 'تم إنشاء طلب السحب')
  if (res) showForm.value = false
}

const totals = computed(() => ({
  pending: withdraws.items.filter((w: any) => w.status === 'pending').reduce((s: number, w: any) => s + Number(w.amount), 0),
}))
</script>

<template>
  <div>
    <PageHeader title="السحوبات" :subtitle="`${num(withdraws.meta.total)} طلب سحب`">
      <template #actions>
        <button v-if="can('withdraw.create')" class="btn" @click="openForm"><Icon name="plus" :size="15" /> طلب سحب</button>
      </template>
    </PageHeader>

    <div class="toolbar">
      <input v-model="withdraws.query.search" class="input input-sm grow" placeholder="بحث باسم الحساب أو رقمه…">

      <select v-model="withdraws.query.status" class="input input-sm">
        <option value="">كل الحالات</option>
        <option v-for="o in options(WITHDRAW_STATUS)" :key="o.value" :value="o.value">{{ o.label }}</option>
      </select>

      <select v-model="withdraws.query.requester_type" class="input input-sm">
        <option value="">الجميع</option>
        <option value="driver">السائقون</option>
        <option value="merchant">التجّار</option>
      </select>

      <select v-model="withdraws.query.method" class="input input-sm">
        <option value="">كل الطرق</option>
        <option v-for="o in options(WITHDRAW_METHOD)" :key="o.value" :value="o.value">{{ o.label }}</option>
      </select>

      <button class="btn btn-ghost btn-sm" @click="withdraws.reset()">مسح الفلاتر</button>

      <div v-if="totals.pending" class="pill pill-sand" style="margin-right:auto;padding:8px 14px">
        معلّق في هذه الصفحة: <span class="num">{{ money(totals.pending) }}</span>
      </div>
    </div>

    <DataTable
      :columns="columns" :rows="withdraws.items" :loading="withdraws.loading"
      :sort="withdraws.query.sort" :dir="withdraws.query.dir"
      empty="لا توجد طلبات سحب" empty-icon="cash"
      @sort="withdraws.sortBy"
    >
      <template #cell-requester="{ row }">
        <div style="font-weight:700;color:var(--head)">{{ row.requester?.name ?? '—' }}</div>
        <div class="muted" style="font-size:12px">
          {{ REQUESTER[row.requester_type] ?? '' }}
          <span v-if="row.requester" class="num"> · الرصيد {{ money(row.requester.balance) }}</span>
        </div>
      </template>
      <template #cell-amount="{ row }">
        <span class="num" style="font-weight:800;font-size:15px">{{ money(row.amount) }}</span>
      </template>
      <template #cell-method="{ row }">{{ WITHDRAW_METHOD[row.method] ?? row.method }}</template>
      <template #cell-account="{ row }">
        <div style="font-size:13px">{{ row.account_name ?? '—' }}</div>
        <div class="muted num" style="font-size:12px">{{ row.account_number ?? '' }} {{ row.bank_name ? `· ${row.bank_name}` : '' }}</div>
      </template>
      <template #cell-status="{ row }">
        <StatusPill :value="row.status" :map="WITHDRAW_STATUS" />
        <div v-if="row.processed_by" class="muted" style="font-size:11px;margin-top:2px">بواسطة {{ row.processed_by }}</div>
      </template>
      <template #cell-created_at="{ row }"><span class="muted" style="font-size:13px">{{ dateTime(row.created_at) }}</span></template>
      <template #cell-actions="{ row }">
        <div class="row-actions">
          <button
            v-for="s in (can('withdraw.update') ? NEXT[row.status] ?? [] : [])" :key="s"
            class="btn btn-sm" :class="s === 'rejected' ? 'btn-danger' : (s === 'paid' ? '' : 'btn-ghost')"
            :disabled="withdraws.saving" @click="setStatus(row, s)"
          >
            {{ WITHDRAW_STATUS[s][0] }}
          </button>
          <button v-if="can('withdraw.delete') && row.status !== 'paid'" class="icon-btn danger" title="حذف" @click="remove(row)"><Icon name="trash" /></button>
        </div>
      </template>
    </DataTable>

    <AppPagination :meta="withdraws.meta" @change="withdraws.query.page = $event" />

    <AppModal v-model="showForm" title="طلب سحب جديد" subtitle="يُنشأ نيابةً عن السائق أو التاجر">
      <form id="withdraw-form" class="form-grid" @submit.prevent="submit">
        <FormField label="نوع الحساب">
          <select v-model="form.requester_type" class="input">
            <option value="driver">سائق</option>
            <option value="merchant">تاجر</option>
          </select>
        </FormField>

        <FormField label="الحساب *">
          <RemoteSelect
            v-model="form.requester_id"
            :endpoint="form.requester_type === 'driver' ? '/drivers' : '/merchants'"
            :label-key="form.requester_type === 'driver' ? 'name' : 'store_name'"
            placeholder="ابحث…"
          />
        </FormField>

        <FormField label="المبلغ *">
          <input v-model.number="form.amount" type="number" min="1" step="0.01" class="input" dir="ltr" required>
        </FormField>

        <FormField label="طريقة الصرف">
          <select v-model="form.method" class="input">
            <option v-for="o in options(WITHDRAW_METHOD)" :key="o.value" :value="o.value">{{ o.label }}</option>
          </select>
        </FormField>

        <FormField label="اسم صاحب الحساب"><input v-model="form.account_name" class="input"></FormField>
        <FormField label="رقم الحساب"><input v-model="form.account_number" class="input" dir="ltr"></FormField>
        <FormField label="اسم البنك" full><input v-model="form.bank_name" class="input"></FormField>
        <FormField label="ملاحظات" full><textarea v-model="form.note" class="input" /></FormField>
      </form>

      <template #footer>
        <button type="submit" form="withdraw-form" class="btn" :disabled="withdraws.saving || !form.requester_id">
          {{ withdraws.saving ? 'جارٍ الحفظ…' : 'إنشاء الطلب' }}
        </button>
        <button class="btn btn-ghost" @click="showForm = false">إلغاء</button>
      </template>
    </AppModal>
  </div>
</template>
