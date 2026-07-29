<script setup lang="ts">
import type { Column } from '~/types/table'

definePageMeta({ middleware: 'auth' })

const { can } = useAuth()
const { lookups, load: loadLookups, refresh: refreshLookups } = useLookups()
const { confirm } = useConfirm()

const tab = ref<'subscriptions' | 'plans'>('subscriptions')

const subs = useResource('/subscriptions', { status: '', subscription_plan_id: '', expiring: '' })
const plans = useResource('/subscription-plans', { is_active: '' })

onMounted(loadLookups)

const subColumns: Column[] = [
  { key: 'merchant', label: 'المتجر' },
  { key: 'plan', label: 'الباقة' },
  { key: 'price', label: 'القيمة', sortable: true },
  { key: 'period', label: 'الفترة' },
  { key: 'days_left', label: 'المتبقي' },
  { key: 'status', label: 'الحالة', sortable: true },
  { key: 'actions', label: '', width: '120px' },
]

const planColumns: Column[] = [
  { key: 'name', label: 'الباقة', sortable: true },
  { key: 'price', label: 'السعر', sortable: true },
  { key: 'duration_days', label: 'المدة' },
  { key: 'commission_rate', label: 'العمولة' },
  { key: 'features', label: 'المزايا' },
  { key: 'subscriptions_count', label: 'المشتركون' },
  { key: 'is_active', label: 'مفعّلة' },
  { key: 'actions', label: '', width: '90px' },
]

// ── subscribe a merchant ────────────────────────────────────────────────
const showSub = ref(false)
const subForm = reactive({ merchant_id: null as number | null, subscription_plan_id: '' as any, starts_at: todayISO(), price: null as number | null, note: '' })

function openSub() {
  Object.assign(subForm, { merchant_id: null, subscription_plan_id: '', starts_at: todayISO(), price: null, note: '' })
  showSub.value = true
}

watch(() => subForm.subscription_plan_id, (id) => {
  const plan = lookups.value.subscription_plans.find((p: any) => String(p.id) === String(id))
  if (plan) subForm.price = Number(plan.price)
})

async function submitSub() {
  const res = await subs.create({ ...subForm }, 'تم تسجيل الاشتراك')
  if (res) showSub.value = false
}

async function renew(row: any) {
  if (!await confirm({ title: 'تجديد الاشتراك؟', text: `${row.merchant?.name ?? ''} — ${row.plan?.name ?? ''}`, confirmText: 'تجديد' })) return
  await subs.submit(() => useApi().post(`/subscriptions/${row.id}/renew`, {}), 'تم تجديد الاشتراك')
}

async function cancelSub(row: any) {
  if (!await confirm({ title: 'إلغاء الاشتراك؟', danger: true, confirmText: 'إلغاء' })) return
  await subs.update(row.id, { status: 'canceled' }, 'تم إلغاء الاشتراك')
}

// ── plans ───────────────────────────────────────────────────────────────
const showPlan = ref(false)
const editingPlan = ref<any>(null)
const planForm = reactive({ name: '', description: '', price: 0, duration_days: 30, commission_rate: null as number | null, orders_limit: null as number | null, features: '', is_active: true, sort: 0 })

function openPlan(row: any = null) {
  editingPlan.value = row
  Object.assign(planForm, {
    name: row?.name ?? '', description: row?.description ?? '', price: row?.price ?? 0,
    duration_days: row?.duration_days ?? 30, commission_rate: row?.commission_rate ?? null,
    orders_limit: row?.orders_limit ?? null, features: (row?.features ?? []).join('\n'),
    is_active: row?.is_active ?? true, sort: row?.sort ?? 0,
  })
  showPlan.value = true
}

async function submitPlan() {
  const payload = {
    ...planForm,
    features: planForm.features.split('\n').map(f => f.trim()).filter(Boolean),
  }
  const res = editingPlan.value
    ? await plans.update(editingPlan.value.id, payload)
    : await plans.create(payload)
  if (res) {
    showPlan.value = false
    refreshLookups()
  }
}

async function removePlan(row: any) {
  if (!await confirm({ title: `حذف الباقة ${row.name}؟`, danger: true, confirmText: 'حذف' })) return
  await plans.remove(row.id)
  refreshLookups()
}
</script>

<template>
  <div>
    <PageHeader title="الاشتراكات" subtitle="اشتراكات التجّار وباقات المنصّة">
      <template #actions>
        <button v-if="tab === 'subscriptions' && can('subscription.create')" class="btn" @click="openSub"><Icon name="plus" :size="15" /> اشتراك جديد</button>
        <button v-if="tab === 'plans' && can('subscription.create')" class="btn" @click="openPlan()"><Icon name="plus" :size="15" /> باقة جديدة</button>
      </template>
    </PageHeader>

    <div class="tabs">
      <button class="tab" :class="{ active: tab === 'subscriptions' }" @click="tab = 'subscriptions'">الاشتراكات</button>
      <button class="tab" :class="{ active: tab === 'plans' }" @click="tab = 'plans'">الباقات</button>
    </div>

    <!-- ── subscriptions ─────────────────────────────────────────────── -->
    <template v-if="tab === 'subscriptions'">
      <div class="toolbar">
        <input v-model="subs.query.search" class="input input-sm grow" placeholder="بحث باسم المتجر أو هاتفه…">

        <select v-model="subs.query.status" class="input input-sm">
          <option value="">كل الحالات</option>
          <option v-for="o in options(SUBSCRIPTION_STATUS)" :key="o.value" :value="o.value">{{ o.label }}</option>
        </select>

        <select v-model="subs.query.subscription_plan_id" class="input input-sm">
          <option value="">كل الباقات</option>
          <option v-for="p in lookups.subscription_plans" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>

        <select v-model="subs.query.expiring" class="input input-sm">
          <option value="">الكل</option>
          <option value="7">تنتهي خلال أسبوع</option>
          <option value="30">تنتهي خلال شهر</option>
        </select>

        <button class="btn btn-ghost btn-sm" @click="subs.reset()">مسح الفلاتر</button>
      </div>

      <DataTable
        :columns="subColumns" :rows="subs.items" :loading="subs.loading"
        :sort="subs.query.sort" :dir="subs.query.dir"
        empty="لا توجد اشتراكات" empty-icon="star"
        @sort="subs.sortBy"
      >
        <template #cell-merchant="{ row }">
          <div style="font-weight:700;color:var(--head)">{{ row.merchant?.name ?? '—' }}</div>
          <div class="muted num" style="font-size:12px">{{ row.merchant?.phone ?? '' }}</div>
        </template>
        <template #cell-plan="{ row }">{{ row.plan?.name ?? '—' }}</template>
        <template #cell-price="{ row }"><span class="num" style="font-weight:700">{{ money(row.price) }}</span></template>
        <template #cell-period="{ row }">
          <span class="muted num" style="font-size:13px">{{ date(row.starts_at) }} <Icon name="arrow" /> {{ date(row.ends_at) }}</span>
        </template>
        <template #cell-days_left="{ row }">
          <span
            class="pill num"
            :class="row.days_left < 0 ? 'pill-terra' : (row.days_left <= 7 ? 'pill-sand' : 'pill-green')"
          >{{ row.days_left < 0 ? 'منتهٍ' : `${row.days_left} يوم` }}</span>
        </template>
        <template #cell-status="{ row }"><StatusPill :value="row.status" :map="SUBSCRIPTION_STATUS" /></template>
        <template #cell-actions="{ row }">
          <div class="row-actions">
            <button v-if="can('subscription.create')" class="icon-btn" title="تجديد" @click="renew(row)"><Icon name="refresh" /></button>
            <button v-if="can('subscription.update') && row.status === 'active'" class="icon-btn danger" title="إلغاء" @click="cancelSub(row)"><Icon name="x" /></button>
          </div>
        </template>
      </DataTable>

      <AppPagination :meta="subs.meta" @change="subs.query.page = $event" />
    </template>

    <!-- ── plans ─────────────────────────────────────────────────────── -->
    <template v-else>
      <div class="toolbar">
        <input v-model="plans.query.search" class="input input-sm grow" placeholder="بحث باسم الباقة…">
        <select v-model="plans.query.is_active" class="input input-sm">
          <option value="">الكل</option>
          <option value="1">مفعّلة</option>
          <option value="0">معطّلة</option>
        </select>
      </div>

      <DataTable
        :columns="planColumns" :rows="plans.items" :loading="plans.loading"
        :sort="plans.query.sort" :dir="plans.query.dir"
        empty="لا توجد باقات" empty-icon="star"
        @sort="plans.sortBy"
      >
        <template #cell-name="{ row }">
          <div style="font-weight:700;color:var(--head)">{{ row.name }}</div>
          <div class="muted" style="font-size:12px">{{ row.description ?? '' }}</div>
        </template>
        <template #cell-price="{ row }"><span class="num" style="font-weight:700">{{ money(row.price) }}</span></template>
        <template #cell-duration_days="{ row }"><span class="num">{{ row.duration_days }} يوم</span></template>
        <template #cell-commission_rate="{ row }">
          <span class="num">{{ row.commission_rate !== null ? `${row.commission_rate}%` : '—' }}</span>
        </template>
        <template #cell-features="{ row }">
          <div style="display:flex;gap:5px;flex-wrap:wrap;max-width:280px">
            <span v-for="(f, i) in row.features.slice(0, 3)" :key="i" class="pill pill-gray">{{ f }}</span>
            <span v-if="row.features.length > 3" class="muted" style="font-size:12px">+{{ row.features.length - 3 }}</span>
          </div>
        </template>
        <template #cell-subscriptions_count="{ row }"><span class="num">{{ num(row.subscriptions_count ?? 0) }}</span></template>
        <template #cell-is_active="{ row }">
          <span class="pill" :class="row.is_active ? 'pill-green' : 'pill-gray'">{{ row.is_active ? 'مفعّلة' : 'معطّلة' }}</span>
        </template>
        <template #cell-actions="{ row }">
          <div class="row-actions">
            <button v-if="can('subscription.update')" class="icon-btn" title="تعديل" @click="openPlan(row)"><Icon name="edit" /></button>
            <button v-if="can('subscription.delete')" class="icon-btn danger" title="حذف" @click="removePlan(row)"><Icon name="trash" /></button>
          </div>
        </template>
      </DataTable>

      <AppPagination :meta="plans.meta" @change="plans.query.page = $event" />
    </template>

    <!-- ── modals ────────────────────────────────────────────────────── -->
    <AppModal v-model="showSub" title="اشتراك جديد" subtitle="يُلغى أي اشتراك فعّال سابق للمتجر">
      <form id="sub-form" class="form-grid" @submit.prevent="submitSub">
        <FormField label="المتجر *" full>
          <RemoteSelect v-model="subForm.merchant_id" endpoint="/merchants" label-key="store_name" placeholder="ابحث عن متجر…" />
        </FormField>
        <FormField label="الباقة *">
          <select v-model="subForm.subscription_plan_id" class="input" required>
            <option value="">— اختر —</option>
            <option v-for="p in lookups.subscription_plans" :key="p.id" :value="p.id">
              {{ p.name }} — {{ money(p.price) }} / {{ p.duration_days }} يوم
            </option>
          </select>
        </FormField>
        <FormField label="تاريخ البدء"><input v-model="subForm.starts_at" type="date" class="input"></FormField>
        <FormField label="القيمة المدفوعة" hint="اتركها كما هي لاعتماد سعر الباقة">
          <input v-model.number="subForm.price" type="number" min="0" step="0.01" class="input" dir="ltr">
        </FormField>
        <FormField label="ملاحظات" full><textarea v-model="subForm.note" class="input" /></FormField>
      </form>

      <template #footer>
        <button type="submit" form="sub-form" class="btn" :disabled="subs.saving || !subForm.merchant_id">تسجيل الاشتراك</button>
        <button class="btn btn-ghost" @click="showSub = false">إلغاء</button>
      </template>
    </AppModal>

    <AppModal v-model="showPlan" :title="editingPlan ? 'تعديل الباقة' : 'باقة جديدة'">
      <form id="plan-form" class="form-grid" @submit.prevent="submitPlan">
        <FormField label="اسم الباقة *"><input v-model="planForm.name" class="input" required></FormField>
        <FormField label="السعر *"><input v-model.number="planForm.price" type="number" min="0" step="0.01" class="input" dir="ltr" required></FormField>
        <FormField label="المدة (أيام)"><input v-model.number="planForm.duration_days" type="number" min="1" class="input" dir="ltr"></FormField>
        <FormField label="نسبة العمولة %" hint="اتركها فارغة لاعتماد عمولة المتجر">
          <input v-model.number="planForm.commission_rate" type="number" min="0" max="100" step="0.5" class="input" dir="ltr">
        </FormField>
        <FormField label="حد الطلبات" hint="فارغ = غير محدود">
          <input v-model.number="planForm.orders_limit" type="number" min="1" class="input" dir="ltr">
        </FormField>
        <FormField label="ترتيب العرض"><input v-model.number="planForm.sort" type="number" min="0" class="input" dir="ltr"></FormField>
        <FormField label="الوصف" full><input v-model="planForm.description" class="input"></FormField>
        <FormField label="المزايا" hint="ميزة واحدة في كل سطر" full>
          <textarea v-model="planForm.features" class="input" />
        </FormField>
        <FormField label="الحالة" full><AppSwitch v-model="planForm.is_active" label="الباقة مفعّلة" /></FormField>
      </form>

      <template #footer>
        <button type="submit" form="plan-form" class="btn" :disabled="plans.saving">حفظ</button>
        <button class="btn btn-ghost" @click="showPlan = false">إلغاء</button>
      </template>
    </AppModal>
  </div>
</template>
