<script setup lang="ts">
import type { Column } from '~/types/table'

definePageMeta({ middleware: 'auth' })

const { can } = useAuth()
const { lookups, load: loadLookups } = useLookups()
const { confirm } = useConfirm()

const orders = useResource('/orders', {
  status: '', city_id: '', driver_id: '', payment_method: '', from: '', to: '',
})

onMounted(loadLookups)

const columns: Column[] = [
  { key: 'order_no', label: 'رقم الطلب', sortable: true },
  { key: 'customer', label: 'الزبون' },
  { key: 'merchant', label: 'المتجر' },
  { key: 'driver', label: 'السائق' },
  { key: 'total', label: 'الإجمالي', sortable: true },
  { key: 'payment_method', label: 'الدفع' },
  { key: 'status', label: 'الحالة', sortable: true },
  { key: 'created_at', label: 'التاريخ', sortable: true },
  { key: 'actions', label: '', width: '90px' },
]

// ── create ──────────────────────────────────────────────────────────────
const showForm = ref(false)
const form = reactive({
  customer_id: null as number | null,
  merchant_id: null as number | null,
  city_id: '' as any,
  service_id: '' as any,
  driver_id: '' as any,
  pickup_address: '',
  drop_address: '',
  recipient_name: '',
  recipient_phone: '',
  items_total: 0,
  delivery_fee: 0,
  discount: 0,
  payment_method: 'cash',
  notes: '',
})

function openForm() {
  Object.assign(form, {
    customer_id: null, merchant_id: null, city_id: '', service_id: '', driver_id: '',
    pickup_address: '', drop_address: '', recipient_name: '', recipient_phone: '',
    items_total: 0, delivery_fee: 0, discount: 0, payment_method: 'cash', notes: '',
  })
  showForm.value = true
}

// picking a city pre-fills its standard delivery fee
watch(() => form.city_id, (id) => {
  const city = lookups.value.cities.find((c: any) => String(c.id) === String(id))
  if (city) form.delivery_fee = Number(city.delivery_fee)
})

const total = computed(() => Math.max(0, Number(form.items_total || 0) + Number(form.delivery_fee || 0) - Number(form.discount || 0)))

async function submit() {
  const res = await orders.create({ ...form })
  if (res) showForm.value = false
}

async function remove(row: any) {
  if (!await confirm({ title: `حذف الطلب ${row.order_no}؟`, text: 'سيُنقل إلى سلّة المحذوفات.', danger: true, confirmText: 'حذف' })) return
  await orders.remove(row.id)
}
</script>

<template>
  <div>
    <PageHeader title="الطلبات" :subtitle="`${num(orders.meta.total)} طلب`">
      <template #actions>
        <button v-if="can('order.create')" class="btn" @click="openForm"><Icon name="plus" :size="15" /> طلب جديد</button>
      </template>
    </PageHeader>

    <div class="toolbar">
      <input v-model="orders.query.search" class="input input-sm grow" placeholder="بحث برقم الطلب، الزبون، العنوان…">

      <select v-model="orders.query.status" class="input input-sm">
        <option value="">كل الحالات</option>
        <option v-for="o in options(ORDER_STATUS)" :key="o.value" :value="o.value">{{ o.label }}</option>
      </select>

      <select v-model="orders.query.city_id" class="input input-sm">
        <option value="">كل المدن</option>
        <option v-for="c in lookups.cities" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>

      <select v-model="orders.query.driver_id" class="input input-sm">
        <option value="">كل السائقين</option>
        <option v-for="d in lookups.drivers" :key="d.id" :value="d.id">{{ d.name }}</option>
      </select>

      <select v-model="orders.query.payment_method" class="input input-sm">
        <option value="">كل طرق الدفع</option>
        <option v-for="o in options(PAYMENT)" :key="o.value" :value="o.value">{{ o.label }}</option>
      </select>

      <input v-model="orders.query.from" type="date" class="input input-sm" title="من تاريخ">
      <input v-model="orders.query.to" type="date" class="input input-sm" title="إلى تاريخ">

      <button class="btn btn-ghost btn-sm" @click="orders.reset()">مسح الفلاتر</button>
    </div>

    <DataTable
      :columns="columns" :rows="orders.items" :loading="orders.loading"
      :sort="orders.query.sort" :dir="orders.query.dir"
      clickable empty="لا توجد طلبات مطابقة للفلاتر" empty-icon="box"
      @sort="orders.sortBy" @row="navigateTo(`/orders/${$event.id}`)"
    >
      <template #cell-order_no="{ row }">
        <span class="num" style="font-weight:800;color:var(--head)">{{ row.order_no }}</span>
      </template>
      <template #cell-customer="{ row }">
        <div>{{ row.customer?.name ?? '—' }}</div>
        <div class="muted num" style="font-size:12px">{{ row.customer?.phone ?? '' }}</div>
      </template>
      <template #cell-merchant="{ row }">{{ row.merchant?.name ?? '—' }}</template>
      <template #cell-driver="{ row }">
        <span v-if="row.driver">{{ row.driver.name }}</span>
        <span v-else class="pill pill-sand">غير معيّن</span>
      </template>
      <template #cell-total="{ row }"><span class="num" style="font-weight:700">{{ money(row.total) }}</span></template>
      <template #cell-payment_method="{ row }">
        <span class="muted">{{ PAYMENT[row.payment_method] ?? row.payment_method }}</span>
      </template>
      <template #cell-status="{ row }"><StatusPill :value="row.status" :map="ORDER_STATUS" /></template>
      <template #cell-created_at="{ row }"><span class="muted" style="font-size:13px">{{ dateTime(row.created_at) }}</span></template>
      <template #cell-actions="{ row }">
        <div class="row-actions" @click.stop>
          <NuxtLink :to="`/orders/${row.id}`" class="icon-btn" title="عرض"><Icon name="eye" /></NuxtLink>
          <button v-if="can('order.delete')" class="icon-btn danger" title="حذف" @click="remove(row)"><Icon name="trash" /></button>
        </div>
      </template>
    </DataTable>

    <AppPagination :meta="orders.meta" @change="orders.query.page = $event" />

    <!-- ── create ────────────────────────────────────────────────────── -->
    <AppModal v-model="showForm" title="طلب جديد" subtitle="أدخل بيانات الطلب" width="680px">
      <form id="order-form" class="form-grid" @submit.prevent="submit">
        <FormField label="الزبون">
          <RemoteSelect v-model="form.customer_id" endpoint="/customers" placeholder="ابحث عن زبون…" />
        </FormField>

        <FormField label="المتجر">
          <RemoteSelect v-model="form.merchant_id" endpoint="/merchants" label-key="store_name" placeholder="ابحث عن متجر…" />
        </FormField>

        <FormField label="المدينة">
          <select v-model="form.city_id" class="input">
            <option value="">— اختر —</option>
            <option v-for="c in lookups.cities" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </FormField>

        <FormField label="الخدمة">
          <select v-model="form.service_id" class="input">
            <option value="">— اختر —</option>
            <option v-for="s in lookups.services" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </FormField>

        <FormField label="السائق" hint="يمكن تعيينه لاحقاً">
          <select v-model="form.driver_id" class="input">
            <option value="">بدون تعيين</option>
            <option v-for="d in lookups.drivers" :key="d.id" :value="d.id">{{ d.name }}</option>
          </select>
        </FormField>

        <FormField label="طريقة الدفع">
          <select v-model="form.payment_method" class="input">
            <option v-for="o in options(PAYMENT)" :key="o.value" :value="o.value">{{ o.label }}</option>
          </select>
        </FormField>

        <FormField label="عنوان الاستلام" full>
          <input v-model="form.pickup_address" class="input" placeholder="من أين يُستلم الطلب">
        </FormField>

        <FormField label="عنوان التسليم *" full>
          <input v-model="form.drop_address" class="input" required placeholder="إلى أين يُسلَّم الطلب">
        </FormField>

        <FormField label="اسم المستلم">
          <input v-model="form.recipient_name" class="input">
        </FormField>

        <FormField label="هاتف المستلم">
          <input v-model="form.recipient_phone" class="input" dir="ltr">
        </FormField>

        <FormField label="قيمة الطلب">
          <input v-model.number="form.items_total" type="number" min="0" step="0.01" class="input" dir="ltr">
        </FormField>

        <FormField label="أجرة التوصيل">
          <input v-model.number="form.delivery_fee" type="number" min="0" step="0.01" class="input" dir="ltr">
        </FormField>

        <FormField label="الخصم">
          <input v-model.number="form.discount" type="number" min="0" step="0.01" class="input" dir="ltr">
        </FormField>

        <FormField label="ملاحظات" full>
          <textarea v-model="form.notes" class="input" placeholder="ملاحظات إضافية للسائق" />
        </FormField>

        <div class="field full" style="background:var(--card2);border-radius:14px;padding:14px 16px;
             display:flex;align-items:center;justify-content:space-between">
          <span style="font-weight:700;color:var(--head)">الإجمالي المتوقّع</span>
          <span class="num" style="font-weight:800;font-size:19px;color:var(--green-d)">{{ money(total) }}</span>
        </div>
      </form>

      <template #footer>
        <button type="submit" form="order-form" class="btn" :disabled="orders.saving">
          {{ orders.saving ? 'جارٍ الحفظ…' : 'إنشاء الطلب' }}
        </button>
        <button class="btn btn-ghost" @click="showForm = false">إلغاء</button>
      </template>
    </AppModal>
  </div>
</template>
