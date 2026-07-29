<script setup lang="ts">
import type { Column } from '~/types/table'

definePageMeta({ middleware: 'auth' })

const { can } = useAuth()
const api = useApi()
const toast = useToast()
const { lookups, load: loadLookups } = useLookups()
const { confirm } = useConfirm()

const merchants = useResource('/merchants', { status: '', city_id: '', store_category_id: '', is_active: '' })

onMounted(loadLookups)

const columns: Column[] = [
  { key: 'store_name', label: 'المتجر', sortable: true },
  { key: 'category', label: 'التصنيف' },
  { key: 'city', label: 'المدينة' },
  { key: 'orders_count', label: 'الطلبات' },
  { key: 'commission_rate', label: 'العمولة' },
  { key: 'balance', label: 'الرصيد', sortable: true },
  { key: 'subscription', label: 'الاشتراك' },
  { key: 'status', label: 'الحالة' },
  { key: 'actions', label: '', width: '120px' },
]

const showForm = ref(false)
const editing = ref<any>(null)
const form = reactive({
  store_name: '', owner_name: '', phone: '', email: '',
  city_id: '' as any, store_category_id: '' as any, address: '',
  commission_rate: 10, notes: '',
})

function openForm(row: any = null) {
  editing.value = row
  Object.assign(form, {
    store_name: row?.store_name ?? '', owner_name: row?.owner_name ?? '',
    phone: row?.phone ?? '', email: row?.email ?? '',
    city_id: row?.city_id ?? '', store_category_id: row?.store_category_id ?? '',
    address: row?.address ?? '', commission_rate: row?.commission_rate ?? 10,
    notes: row?.notes ?? '',
  })
  showForm.value = true
}

async function submit() {
  const res = editing.value
    ? await merchants.update(editing.value.id, { ...form })
    : await merchants.create({ ...form })
  if (res) showForm.value = false
}

async function setStatus(row: any, status: string) {
  await merchants.patch(`/merchants/${row.id}/status`, { status }, `تم تحديث حالة ${row.store_name}`)
}

async function remove(row: any) {
  if (!await confirm({ title: `حذف المتجر ${row.store_name}؟`, text: 'سيُنقل إلى سلّة المحذوفات.', danger: true, confirmText: 'حذف' })) return
  await merchants.remove(row.id)
}

const showDetails = ref(false)
const details = ref<any>(null)
const detailsLoading = ref(false)

async function openDetails(row: any) {
  showDetails.value = true
  detailsLoading.value = true
  details.value = null
  try {
    details.value = await api.get(`/merchants/${row.id}`)
  } catch (e) {
    toast.error(apiError(e))
  } finally {
    detailsLoading.value = false
  }
}
</script>

<template>
  <div>
    <PageHeader title="التجّار" :subtitle="`${num(merchants.meta.total)} متجر`">
      <template #actions>
        <button v-if="can('merchant.create')" class="btn" @click="openForm()"><Icon name="plus" :size="15" /> متجر جديد</button>
      </template>
    </PageHeader>

    <div class="toolbar">
      <input v-model="merchants.query.search" class="input input-sm grow" placeholder="بحث باسم المتجر، المالك، الهاتف…">

      <select v-model="merchants.query.status" class="input input-sm">
        <option value="">كل الحالات</option>
        <option v-for="o in options(PARTNER_STATUS)" :key="o.value" :value="o.value">{{ o.label }}</option>
      </select>

      <select v-model="merchants.query.city_id" class="input input-sm">
        <option value="">كل المدن</option>
        <option v-for="c in lookups.cities" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>

      <select v-model="merchants.query.store_category_id" class="input input-sm">
        <option value="">كل التصنيفات</option>
        <option v-for="c in lookups.store_categories" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>

      <button class="btn btn-ghost btn-sm" @click="merchants.reset()">مسح الفلاتر</button>
    </div>

    <DataTable
      :columns="columns" :rows="merchants.items" :loading="merchants.loading"
      :sort="merchants.query.sort" :dir="merchants.query.dir"
      empty="لا يوجد تجّار مطابقون" empty-icon="store"
      @sort="merchants.sortBy"
    >
      <template #cell-store_name="{ row }">
        <div style="display:flex;align-items:center;gap:10px">
          <div style="width:34px;height:34px;border-radius:11px;background:var(--grad-terra);display:flex;
               align-items:center;justify-content:center;color:#fff;font-weight:800;flex-shrink:0">{{ row.store_name[0] }}</div>
          <div style="min-width:0">
            <div style="font-weight:700;color:var(--head)">{{ row.store_name }}</div>
            <div class="muted num" style="font-size:12px">{{ row.phone }}</div>
          </div>
        </div>
      </template>
      <template #cell-category="{ row }">{{ row.category?.name ?? '—' }}</template>
      <template #cell-city="{ row }">{{ row.city?.name ?? '—' }}</template>
      <template #cell-orders_count="{ row }"><span class="num">{{ num(row.orders_count ?? 0) }}</span></template>
      <template #cell-commission_rate="{ row }"><span class="num">{{ row.commission_rate }}%</span></template>
      <template #cell-balance="{ row }"><span class="num" style="font-weight:700">{{ money(row.balance) }}</span></template>
      <template #cell-subscription="{ row }">
        <template v-if="row.subscription">
          <div style="font-weight:700;font-size:13px">{{ row.subscription.plan }}</div>
          <div class="muted" style="font-size:12px">
            {{ row.subscription.days_left >= 0 ? `${row.subscription.days_left} يوم متبقٍ` : 'منتهٍ' }}
          </div>
        </template>
        <span v-else class="muted">—</span>
      </template>
      <template #cell-status="{ row }">
        <select
          v-if="can('merchant.update')" class="input input-sm" style="width:auto;min-width:120px"
          :value="row.status" @change="setStatus(row, ($event.target as HTMLSelectElement).value)"
        >
          <option v-for="o in options(PARTNER_STATUS)" :key="o.value" :value="o.value">{{ o.label }}</option>
        </select>
        <StatusPill v-else :value="row.status" :map="PARTNER_STATUS" />
      </template>
      <template #cell-actions="{ row }">
        <div class="row-actions">
          <button class="icon-btn" title="التفاصيل" @click="openDetails(row)"><Icon name="eye" /></button>
          <button v-if="can('merchant.update')" class="icon-btn" title="تعديل" @click="openForm(row)"><Icon name="edit" /></button>
          <button v-if="can('merchant.delete')" class="icon-btn danger" title="حذف" @click="remove(row)"><Icon name="trash" /></button>
        </div>
      </template>
    </DataTable>

    <AppPagination :meta="merchants.meta" @change="merchants.query.page = $event" />

    <AppModal v-model="showForm" :title="editing ? 'تعديل بيانات المتجر' : 'متجر جديد'" width="620px">
      <form id="merchant-form" class="form-grid" @submit.prevent="submit">
        <FormField label="اسم المتجر *"><input v-model="form.store_name" class="input" required></FormField>
        <FormField label="اسم المالك"><input v-model="form.owner_name" class="input"></FormField>
        <FormField label="رقم الهاتف *"><input v-model="form.phone" class="input" dir="ltr" required></FormField>
        <FormField label="البريد الإلكتروني"><input v-model="form.email" type="email" class="input" dir="ltr"></FormField>
        <FormField label="المدينة">
          <select v-model="form.city_id" class="input">
            <option value="">— اختر —</option>
            <option v-for="c in lookups.cities" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </FormField>
        <FormField label="التصنيف">
          <select v-model="form.store_category_id" class="input">
            <option value="">— اختر —</option>
            <option v-for="c in lookups.store_categories" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </FormField>
        <FormField label="نسبة العمولة %" hint="تُطبَّق على قيمة الطلبات">
          <input v-model.number="form.commission_rate" type="number" min="0" max="100" step="0.5" class="input" dir="ltr">
        </FormField>
        <FormField label="العنوان" full><input v-model="form.address" class="input"></FormField>
        <FormField label="ملاحظات" full><textarea v-model="form.notes" class="input" /></FormField>
      </form>

      <template #footer>
        <button type="submit" form="merchant-form" class="btn" :disabled="merchants.saving">
          {{ merchants.saving ? 'جارٍ الحفظ…' : 'حفظ' }}
        </button>
        <button class="btn btn-ghost" @click="showForm = false">إلغاء</button>
      </template>
    </AppModal>

    <AppModal v-model="showDetails" :title="details?.data?.store_name ?? 'تفاصيل المتجر'" width="700px">
      <div v-if="detailsLoading" class="skeleton" style="height:180px" />

      <template v-else-if="details">
        <div class="grid grid-kpi" style="margin-bottom:18px">
          <div class="card" style="padding:14px">
            <div class="num" style="font-size:20px;font-weight:800;color:var(--head)">{{ num(details.stats.delivered) }}</div>
            <div class="muted" style="font-size:12px">طلب مُسلَّم</div>
          </div>
          <div class="card" style="padding:14px">
            <div class="num" style="font-size:20px;font-weight:800;color:var(--head)">{{ money(details.stats.revenue) }}</div>
            <div class="muted" style="font-size:12px">إجمالي المبيعات</div>
          </div>
          <div class="card" style="padding:14px">
            <div class="num" style="font-size:20px;font-weight:800;color:var(--head)">{{ money(details.stats.commission) }}</div>
            <div class="muted" style="font-size:12px">عمولة المنصّة</div>
          </div>
          <div class="card" style="padding:14px">
            <div class="num" style="font-size:20px;font-weight:800;color:var(--green-d)">{{ money(details.data.balance) }}</div>
            <div class="muted" style="font-size:12px">الرصيد الحالي</div>
          </div>
        </div>

        <div style="font-weight:800;color:var(--head);margin-bottom:10px">الاشتراكات</div>
        <div v-if="!details.subscriptions.length" class="muted" style="font-size:14px;margin-bottom:16px">لا توجد اشتراكات.</div>
        <div
          v-for="s in details.subscriptions" :key="s.id"
          style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--line)"
        >
          <span style="font-weight:700;flex:1">{{ s.plan?.name ?? '—' }}</span>
          <span class="muted num" style="font-size:13px">{{ date(s.starts_at) }} <Icon name="arrow" /> {{ date(s.ends_at) }}</span>
          <StatusPill :value="s.status" :map="SUBSCRIPTION_STATUS" />
        </div>

        <div style="font-weight:800;color:var(--head);margin:18px 0 10px">آخر الطلبات</div>
        <div v-if="!details.recent_orders.length" class="muted" style="font-size:14px">لا توجد طلبات.</div>
        <NuxtLink
          v-for="o in details.recent_orders" :key="o.id" :to="`/orders/${o.id}`"
          style="display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--line)"
        >
          <span class="num" style="font-weight:700;color:var(--head)">{{ o.order_no }}</span>
          <span class="muted" style="flex:1;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ o.drop_address }}</span>
          <span class="num" style="font-size:13px">{{ money(o.total) }}</span>
          <StatusPill :value="o.status" :map="ORDER_STATUS" />
        </NuxtLink>
      </template>
    </AppModal>
  </div>
</template>
