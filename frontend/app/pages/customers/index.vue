<script setup lang="ts">
import type { Column } from '~/types/table'

definePageMeta({ middleware: 'auth' })

const { can } = useAuth()
const api = useApi()
const toast = useToast()
const { lookups, load: loadLookups } = useLookups()
const { confirm } = useConfirm()

const customers = useResource('/customers', { city_id: '', is_active: '' })

onMounted(loadLookups)

const columns: Column[] = [
  { key: 'name', label: 'الزبون', sortable: true },
  { key: 'city', label: 'المدينة' },
  { key: 'address', label: 'العنوان' },
  { key: 'orders_count', label: 'الطلبات' },
  { key: 'is_active', label: 'الحالة' },
  { key: 'created_at', label: 'الانضمام', sortable: true },
  { key: 'actions', label: '', width: '120px' },
]

const showForm = ref(false)
const editing = ref<any>(null)
const form = reactive({ name: '', phone: '', email: '', city_id: '' as any, address: '', is_active: true, notes: '' })

function openForm(row: any = null) {
  editing.value = row
  Object.assign(form, {
    name: row?.name ?? '', phone: row?.phone ?? '', email: row?.email ?? '',
    city_id: row?.city_id ?? '', address: row?.address ?? '',
    is_active: row?.is_active ?? true, notes: row?.notes ?? '',
  })
  showForm.value = true
}

async function submit() {
  const res = editing.value
    ? await customers.update(editing.value.id, { ...form })
    : await customers.create({ ...form })
  if (res) showForm.value = false
}

async function toggleActive(row: any, value: boolean) {
  await customers.update(row.id, { is_active: value }, value ? 'تم تفعيل الحساب' : 'تم تعطيل الحساب')
}

async function remove(row: any) {
  if (!await confirm({ title: `حذف الزبون ${row.name}؟`, text: 'سيُنقل إلى سلّة المحذوفات.', danger: true, confirmText: 'حذف' })) return
  await customers.remove(row.id)
}

const showDetails = ref(false)
const details = ref<any>(null)
const detailsLoading = ref(false)

async function openDetails(row: any) {
  showDetails.value = true
  detailsLoading.value = true
  details.value = null
  try {
    details.value = await api.get(`/customers/${row.id}`)
  } catch (e) {
    toast.error(apiError(e))
  } finally {
    detailsLoading.value = false
  }
}
</script>

<template>
  <div>
    <PageHeader title="الزبائن" :subtitle="`${num(customers.meta.total)} زبون`">
      <template #actions>
        <button v-if="can('customer.create')" class="btn" @click="openForm()">＋ زبون جديد</button>
      </template>
    </PageHeader>

    <div class="toolbar">
      <input v-model="customers.query.search" class="input input-sm grow" placeholder="🔍 بحث بالاسم، الهاتف، العنوان…">

      <select v-model="customers.query.city_id" class="input input-sm">
        <option value="">كل المدن</option>
        <option v-for="c in lookups.cities" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>

      <select v-model="customers.query.is_active" class="input input-sm">
        <option value="">الحالة: الكل</option>
        <option value="1">مفعّل</option>
        <option value="0">معطّل</option>
      </select>

      <button class="btn btn-ghost btn-sm" @click="customers.reset()">مسح الفلاتر</button>
    </div>

    <DataTable
      :columns="columns" :rows="customers.items" :loading="customers.loading"
      :sort="customers.query.sort" :dir="customers.query.dir"
      empty="لا يوجد زبائن مطابقون" empty-icon="👥"
      @sort="customers.sortBy"
    >
      <template #cell-name="{ row }">
        <div style="display:flex;align-items:center;gap:10px">
          <div style="width:34px;height:34px;border-radius:11px;background:var(--sage);display:flex;
               align-items:center;justify-content:center;color:#fff;font-weight:800;flex-shrink:0">{{ row.name[0] }}</div>
          <div style="min-width:0">
            <div style="font-weight:700;color:var(--head)">{{ row.name }}</div>
            <div class="muted num" style="font-size:12px">{{ row.phone }}</div>
          </div>
        </div>
      </template>
      <template #cell-city="{ row }">{{ row.city?.name ?? '—' }}</template>
      <template #cell-address="{ row }"><span class="muted">{{ row.address ?? '—' }}</span></template>
      <template #cell-orders_count="{ row }"><span class="num">{{ num(row.orders_count ?? 0) }}</span></template>
      <template #cell-is_active="{ row }">
        <AppSwitch
          :model-value="row.is_active" :disabled="!can('customer.update')"
          @update:model-value="toggleActive(row, $event)"
        />
      </template>
      <template #cell-created_at="{ row }"><span class="muted" style="font-size:13px">{{ date(row.created_at) }}</span></template>
      <template #cell-actions="{ row }">
        <div class="row-actions">
          <button class="icon-btn" title="التفاصيل" @click="openDetails(row)">👁</button>
          <button v-if="can('customer.update')" class="icon-btn" title="تعديل" @click="openForm(row)">✏️</button>
          <button v-if="can('customer.delete')" class="icon-btn danger" title="حذف" @click="remove(row)">🗑</button>
        </div>
      </template>
    </DataTable>

    <AppPagination :meta="customers.meta" @change="customers.query.page = $event" />

    <AppModal v-model="showForm" :title="editing ? 'تعديل بيانات الزبون' : 'زبون جديد'">
      <form id="customer-form" class="form-grid" @submit.prevent="submit">
        <FormField label="الاسم *"><input v-model="form.name" class="input" required></FormField>
        <FormField label="رقم الهاتف *"><input v-model="form.phone" class="input" dir="ltr" required></FormField>
        <FormField label="البريد الإلكتروني"><input v-model="form.email" type="email" class="input" dir="ltr"></FormField>
        <FormField label="المدينة">
          <select v-model="form.city_id" class="input">
            <option value="">— اختر —</option>
            <option v-for="c in lookups.cities" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </FormField>
        <FormField label="العنوان" full><input v-model="form.address" class="input"></FormField>
        <FormField label="الحالة" full><AppSwitch v-model="form.is_active" label="الحساب مفعّل" /></FormField>
        <FormField label="ملاحظات" full><textarea v-model="form.notes" class="input" /></FormField>
      </form>

      <template #footer>
        <button type="submit" form="customer-form" class="btn" :disabled="customers.saving">
          {{ customers.saving ? 'جارٍ الحفظ…' : 'حفظ' }}
        </button>
        <button class="btn btn-ghost" @click="showForm = false">إلغاء</button>
      </template>
    </AppModal>

    <AppModal v-model="showDetails" :title="details?.data?.name ?? 'تفاصيل الزبون'" width="640px">
      <div v-if="detailsLoading" class="skeleton" style="height:160px" />

      <template v-else-if="details">
        <div class="grid grid-kpi" style="margin-bottom:18px">
          <div class="card" style="padding:14px">
            <div class="num" style="font-size:20px;font-weight:800;color:var(--head)">{{ num(details.stats.delivered) }}</div>
            <div class="muted" style="font-size:12px">طلب مُسلَّم</div>
          </div>
          <div class="card" style="padding:14px">
            <div class="num" style="font-size:20px;font-weight:800;color:var(--head)">{{ num(details.stats.canceled) }}</div>
            <div class="muted" style="font-size:12px">طلب ملغي</div>
          </div>
          <div class="card" style="padding:14px">
            <div class="num" style="font-size:20px;font-weight:800;color:var(--green-d)">{{ money(details.stats.spent) }}</div>
            <div class="muted" style="font-size:12px">إجمالي الإنفاق</div>
          </div>
        </div>

        <div style="font-weight:800;color:var(--head);margin-bottom:10px">آخر الطلبات</div>
        <div v-if="!details.recent_orders.length" class="muted" style="font-size:14px">لا توجد طلبات.</div>
        <NuxtLink
          v-for="o in details.recent_orders" :key="o.id" :to="`/orders/${o.id}`"
          style="display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--line)"
        >
          <span class="num" style="font-weight:700;color:var(--head)">{{ o.order_no }}</span>
          <span class="muted" style="flex:1;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ o.merchant?.name ?? '' }}</span>
          <span class="num" style="font-size:13px">{{ money(o.total) }}</span>
          <StatusPill :value="o.status" :map="ORDER_STATUS" />
        </NuxtLink>
      </template>
    </AppModal>
  </div>
</template>
