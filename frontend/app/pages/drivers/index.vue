<script setup lang="ts">
import type { Column } from '~/types/table'

definePageMeta({ middleware: 'auth' })

const { can } = useAuth()
const api = useApi()
const toast = useToast()
const { lookups, load: loadLookups } = useLookups()
const { confirm } = useConfirm()

const drivers = useResource('/drivers', { status: '', city_id: '', vehicle_type: '', is_available: '' })

onMounted(loadLookups)

const columns: Column[] = [
  { key: 'name', label: 'السائق', sortable: true },
  { key: 'city', label: 'المدينة' },
  { key: 'vehicle_type', label: 'المركبة' },
  { key: 'orders_count', label: 'الطلبات' },
  { key: 'balance', label: 'الرصيد', sortable: true },
  { key: 'rating', label: 'التقييم', sortable: true },
  { key: 'is_available', label: 'الإتاحة' },
  { key: 'status', label: 'الحالة' },
  { key: 'actions', label: '', width: '120px' },
]

// ── create / edit ───────────────────────────────────────────────────────
const showForm = ref(false)
const editing = ref<any>(null)
const form = reactive({
  name: '', phone: '', email: '', city_id: '' as any, vehicle_type: 'motorcycle',
  vehicle_plate: '', national_id: '', license_number: '', notes: '',
})

function openForm(row: any = null) {
  editing.value = row
  Object.assign(form, {
    name: row?.name ?? '', phone: row?.phone ?? '', email: row?.email ?? '',
    city_id: row?.city_id ?? '', vehicle_type: row?.vehicle_type ?? 'motorcycle',
    vehicle_plate: row?.vehicle_plate ?? '', national_id: row?.national_id ?? '',
    license_number: row?.license_number ?? '', notes: row?.notes ?? '',
  })
  showForm.value = true
}

async function submit() {
  const res = editing.value
    ? await drivers.update(editing.value.id, { ...form })
    : await drivers.create({ ...form })
  if (res) showForm.value = false
}

// ── status / availability ───────────────────────────────────────────────
async function setStatus(row: any, status: string) {
  await drivers.patch(`/drivers/${row.id}/status`, { status }, `تم تحديث حالة ${row.name}`)
}

async function toggleAvailability(row: any, value: boolean) {
  await drivers.patch(`/drivers/${row.id}/availability`, { is_available: value })
}

async function remove(row: any) {
  if (!await confirm({ title: `حذف السائق ${row.name}؟`, text: 'سيُنقل إلى سلّة المحذوفات.', danger: true, confirmText: 'حذف' })) return
  await drivers.remove(row.id)
}

// ── details ─────────────────────────────────────────────────────────────
const showDetails = ref(false)
const details = ref<any>(null)
const detailsLoading = ref(false)

async function openDetails(row: any) {
  showDetails.value = true
  detailsLoading.value = true
  details.value = null
  try {
    details.value = await api.get(`/drivers/${row.id}`)
  } catch (e) {
    toast.error(apiError(e))
  } finally {
    detailsLoading.value = false
  }
}
</script>

<template>
  <div>
    <PageHeader title="السائقون" :subtitle="`${num(drivers.meta.total)} سائق`">
      <template #actions>
        <button v-if="can('driver.create')" class="btn" @click="openForm()"><Icon name="plus" :size="15" /> سائق جديد</button>
      </template>
    </PageHeader>

    <div class="toolbar">
      <input v-model="drivers.query.search" class="input input-sm grow" placeholder="بحث بالاسم، الهاتف، رقم المركبة…">

      <select v-model="drivers.query.status" class="input input-sm">
        <option value="">كل الحالات</option>
        <option v-for="o in options(PARTNER_STATUS)" :key="o.value" :value="o.value">{{ o.label }}</option>
      </select>

      <select v-model="drivers.query.city_id" class="input input-sm">
        <option value="">كل المدن</option>
        <option v-for="c in lookups.cities" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>

      <select v-model="drivers.query.vehicle_type" class="input input-sm">
        <option value="">كل المركبات</option>
        <option v-for="o in options(VEHICLE)" :key="o.value" :value="o.value">{{ o.label }}</option>
      </select>

      <select v-model="drivers.query.is_available" class="input input-sm">
        <option value="">الإتاحة: الكل</option>
        <option value="1">متاح</option>
        <option value="0">غير متاح</option>
      </select>

      <button class="btn btn-ghost btn-sm" @click="drivers.reset()">مسح الفلاتر</button>
    </div>

    <DataTable
      :columns="columns" :rows="drivers.items" :loading="drivers.loading"
      :sort="drivers.query.sort" :dir="drivers.query.dir"
      empty="لا يوجد سائقون مطابقون" empty-icon="car"
      @sort="drivers.sortBy"
    >
      <template #cell-name="{ row }">
        <div style="display:flex;align-items:center;gap:10px">
          <div style="width:34px;height:34px;border-radius:11px;background:var(--grad-blue);display:flex;
               align-items:center;justify-content:center;color:#fff;font-weight:800;flex-shrink:0">{{ row.name[0] }}</div>
          <div style="min-width:0">
            <div style="font-weight:700;color:var(--head)">{{ row.name }}</div>
            <div class="muted num" style="font-size:12px">{{ row.phone }}</div>
          </div>
        </div>
      </template>
      <template #cell-city="{ row }">{{ row.city?.name ?? '—' }}</template>
      <template #cell-vehicle_type="{ row }">
        <div>{{ VEHICLE[row.vehicle_type] ?? row.vehicle_type }}</div>
        <div class="muted num" style="font-size:12px">{{ row.vehicle_plate ?? '' }}</div>
      </template>
      <template #cell-orders_count="{ row }"><span class="num">{{ num(row.orders_count ?? 0) }}</span></template>
      <template #cell-balance="{ row }"><span class="num" style="font-weight:700">{{ money(row.balance) }}</span></template>
      <template #cell-rating="{ row }"><span class="num"><Icon name="star" /> {{ row.rating }}</span></template>
      <template #cell-is_available="{ row }">
        <AppSwitch
          :model-value="row.is_available"
          :disabled="!can('driver.update') || row.status !== 'approved'"
          @update:model-value="toggleAvailability(row, $event)"
        />
      </template>
      <template #cell-status="{ row }">
        <select
          v-if="can('driver.update')" class="input input-sm" style="width:auto;min-width:120px"
          :value="row.status" @change="setStatus(row, ($event.target as HTMLSelectElement).value)"
        >
          <option v-for="o in options(PARTNER_STATUS)" :key="o.value" :value="o.value">{{ o.label }}</option>
        </select>
        <StatusPill v-else :value="row.status" :map="PARTNER_STATUS" />
      </template>
      <template #cell-actions="{ row }">
        <div class="row-actions">
          <button class="icon-btn" title="التفاصيل" @click="openDetails(row)"><Icon name="eye" /></button>
          <button v-if="can('driver.update')" class="icon-btn" title="تعديل" @click="openForm(row)"><Icon name="edit" /></button>
          <button v-if="can('driver.delete')" class="icon-btn danger" title="حذف" @click="remove(row)"><Icon name="trash" /></button>
        </div>
      </template>
    </DataTable>

    <AppPagination :meta="drivers.meta" @change="drivers.query.page = $event" />

    <!-- ── form ──────────────────────────────────────────────────────── -->
    <AppModal v-model="showForm" :title="editing ? 'تعديل بيانات السائق' : 'سائق جديد'" width="620px">
      <form id="driver-form" class="form-grid" @submit.prevent="submit">
        <FormField label="الاسم *"><input v-model="form.name" class="input" required></FormField>
        <FormField label="رقم الهاتف *"><input v-model="form.phone" class="input" dir="ltr" required></FormField>
        <FormField label="البريد الإلكتروني"><input v-model="form.email" type="email" class="input" dir="ltr"></FormField>
        <FormField label="المدينة">
          <select v-model="form.city_id" class="input">
            <option value="">— اختر —</option>
            <option v-for="c in lookups.cities" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </FormField>
        <FormField label="نوع المركبة">
          <select v-model="form.vehicle_type" class="input">
            <option v-for="o in options(VEHICLE)" :key="o.value" :value="o.value">{{ o.label }}</option>
          </select>
        </FormField>
        <FormField label="رقم اللوحة"><input v-model="form.vehicle_plate" class="input" dir="ltr"></FormField>
        <FormField label="رقم الهوية"><input v-model="form.national_id" class="input" dir="ltr"></FormField>
        <FormField label="رقم الرخصة"><input v-model="form.license_number" class="input" dir="ltr"></FormField>
        <FormField label="ملاحظات" full><textarea v-model="form.notes" class="input" /></FormField>
      </form>

      <template #footer>
        <button type="submit" form="driver-form" class="btn" :disabled="drivers.saving">
          {{ drivers.saving ? 'جارٍ الحفظ…' : 'حفظ' }}
        </button>
        <button class="btn btn-ghost" @click="showForm = false">إلغاء</button>
      </template>
    </AppModal>

    <!-- ── details ───────────────────────────────────────────────────── -->
    <AppModal v-model="showDetails" :title="details?.data?.name ?? 'تفاصيل السائق'" width="700px">
      <div v-if="detailsLoading" class="skeleton" style="height:180px" />

      <template v-else-if="details">
        <div class="grid grid-kpi" style="margin-bottom:18px">
          <div class="card" style="padding:14px">
            <div class="num" style="font-size:20px;font-weight:800;color:var(--head)">{{ num(details.stats.delivered) }}</div>
            <div class="muted" style="font-size:12px">طلب مُسلَّم</div>
          </div>
          <div class="card" style="padding:14px">
            <div class="num" style="font-size:20px;font-weight:800;color:var(--head)">{{ num(details.stats.active) }}</div>
            <div class="muted" style="font-size:12px">طلب جارٍ</div>
          </div>
          <div class="card" style="padding:14px">
            <div class="num" style="font-size:20px;font-weight:800;color:var(--head)">{{ money(details.stats.earnings) }}</div>
            <div class="muted" style="font-size:12px">أجور التوصيل</div>
          </div>
          <div class="card" style="padding:14px">
            <div class="num" style="font-size:20px;font-weight:800;color:var(--green-d)">{{ money(details.data.balance) }}</div>
            <div class="muted" style="font-size:12px">الرصيد الحالي</div>
          </div>
        </div>

        <div style="font-weight:800;color:var(--head);margin-bottom:10px">آخر الطلبات</div>
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
