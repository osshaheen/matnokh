<script setup lang="ts">
import type { Column } from '~/types/table'

definePageMeta({ middleware: 'auth' })

const { user, can } = useAuth()
const api = useApi()
const toast = useToast()

const data = ref<any>(null)
const loading = ref(true)
const days = ref(14)
const metric = ref<'orders' | 'revenue'>('orders')

async function load() {
  loading.value = true
  try {
    const res = await api.get<{ data: any }>('/dashboard', { days: days.value })
    data.value = res.data
  } catch (e) {
    toast.error(apiError(e, 'تعذّر تحميل لوحة المعلومات'))
  } finally {
    loading.value = false
  }
}

watch(days, load)
onMounted(load)

const k = computed(() => data.value?.kpis ?? {})

const kpis = computed(() => [
  { label: 'طلبات اليوم', value: num(k.value.orders_today ?? 0), icon: 'box', grad: 'var(--grad-green)', hint: `${num(k.value.orders_active ?? 0)} طلب جارٍ` },
  { label: 'إيراد اليوم', value: money(k.value.revenue_today ?? 0), icon: 'wallet', grad: 'var(--grad-sand)', hint: `الشهر: ${money(k.value.revenue_month ?? 0)}` },
  { label: 'السائقون المتاحون', value: `${num(k.value.drivers_available ?? 0)} / ${num(k.value.drivers_total ?? 0)}`, icon: 'car', grad: 'var(--grad-blue)', hint: '' },
  { label: 'التجّار النشطون', value: num(k.value.merchants_active ?? 0), icon: 'store', grad: 'var(--grad-terra)', hint: k.value.merchants_pending ? `${num(k.value.merchants_pending)} بانتظار الاعتماد` : '' },
])

const secondary = computed(() => [
  { label: 'سحوبات معلّقة', value: num(k.value.withdraws_pending ?? 0), sub: money(k.value.withdraws_pending_amount ?? 0), to: '/withdraws', perm: 'withdraw.view', icon: 'cash' },
  { label: 'اشتراكات فعّالة', value: num(k.value.subscriptions_active ?? 0), sub: `${num(k.value.subscriptions_expiring ?? 0)} تنتهي خلال أسبوع`, to: '/subscriptions', perm: 'subscription.view', icon: 'star' },
  { label: 'عمولة الشهر', value: money(k.value.commission_month ?? 0), sub: 'من الطلبات المسلّمة', to: '/orders', perm: 'order.view', icon: 'trend' },
  { label: 'إجمالي الزبائن', value: num(k.value.customers_total ?? 0), sub: `${num(k.value.orders_total ?? 0)} طلب إجمالاً`, to: '/customers', perm: 'customer.view', icon: 'users' },
])

const recentColumns: Column[] = [
  { key: 'order_no', label: 'رقم الطلب' },
  { key: 'customer', label: 'الزبون' },
  { key: 'merchant', label: 'المتجر' },
  { key: 'total', label: 'الإجمالي' },
  { key: 'status', label: 'الحالة' },
  { key: 'created_at', label: 'التاريخ' },
]
</script>

<template>
  <div>
    <PageHeader :title="`مرحباً، ${user?.name || ''}`" subtitle="نظرة عامة على منصّة مطنوخ">
      <template #actions>
        <select v-model.number="days" class="input input-sm" style="width:auto">
          <option :value="7">آخر 7 أيام</option>
          <option :value="14">آخر 14 يوم</option>
          <option :value="30">آخر 30 يوم</option>
        </select>
        <button class="btn btn-ghost btn-sm" :disabled="loading" @click="load"><Icon name="refresh" /> تحديث</button>
      </template>
    </PageHeader>

    <div class="grid grid-kpi" style="margin-bottom:16px">
      <StatCard
        v-for="(s, i) in kpis" :key="i"
        :label="s.label" :value="s.value" :icon="s.icon" :grad="s.grad" :hint="s.hint" :loading="loading"
      />
    </div>

    <div class="grid grid-kpi" style="margin-bottom:22px">
      <NuxtLink
        v-for="(s, i) in secondary.filter(x => can(x.perm))" :key="i" :to="s.to"
        class="card" style="padding:16px 18px;display:flex;align-items:center;gap:13px"
      >
        <div style="color:var(--green)"><Icon :name="s.icon" :size="22" /></div>
        <div style="min-width:0;flex:1">
          <div class="num" style="font-size:19px;font-weight:800;color:var(--head)">{{ s.value }}</div>
          <div class="muted" style="font-size:12px">{{ s.label }} · {{ s.sub }}</div>
        </div>
        <div class="muted">›</div>
      </NuxtLink>
    </div>

    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(300px,1fr));margin-bottom:22px">
      <div class="card" style="padding:20px;grid-column:span 2;min-width:0">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:6px">
          <div style="font-weight:800;color:var(--head)">حركة الطلبات</div>
          <div class="tabs" style="margin:0;padding:4px">
            <button class="tab" :class="{ active: metric === 'orders' }" @click="metric = 'orders'">عدد الطلبات</button>
            <button class="tab" :class="{ active: metric === 'revenue' }" @click="metric = 'revenue'">الإيراد</button>
          </div>
        </div>
        <div v-if="loading" class="skeleton" style="height:190px" />
        <TrendChart v-else-if="data?.orders_chart?.length" :points="data.orders_chart" :metric="metric" />
        <EmptyState v-else text="لا توجد بيانات لعرضها بعد" />
      </div>

      <div class="card" style="padding:20px;min-width:0">
        <div style="font-weight:800;color:var(--head);margin-bottom:14px">الطلبات حسب الحالة</div>
        <div v-if="loading" class="skeleton" style="height:190px" />
        <StatusBreakdown v-else-if="data?.orders_by_status" :counts="data.orders_by_status" />
      </div>
    </div>

    <div class="grid grid-2" style="margin-bottom:22px">
      <div class="card" style="padding:20px">
        <div style="font-weight:800;color:var(--head);margin-bottom:12px">أفضل المتاجر</div>
        <div v-if="loading" class="skeleton" style="height:120px" />
        <template v-else>
          <div
            v-for="(m, i) in data?.top_merchants ?? []" :key="m.id"
            style="display:flex;align-items:center;gap:11px;padding:9px 0;border-bottom:1px solid var(--line)"
          >
            <div style="width:27px;height:27px;border-radius:9px;background:var(--card2);display:flex;
                 align-items:center;justify-content:center;font-weight:800;font-size:12px">{{ i + 1 }}</div>
            <div style="flex:1;font-weight:700;font-size:14px;color:var(--head)">{{ m.name }}</div>
            <div class="num muted" style="font-size:13px">{{ num(m.orders) }} طلب</div>
          </div>
          <EmptyState v-if="!(data?.top_merchants ?? []).length" text="لا توجد بيانات" />
        </template>
      </div>

      <div class="card" style="padding:20px">
        <div style="font-weight:800;color:var(--head);margin-bottom:12px">أفضل السائقين</div>
        <div v-if="loading" class="skeleton" style="height:120px" />
        <template v-else>
          <div
            v-for="(d, i) in data?.top_drivers ?? []" :key="d.id"
            style="display:flex;align-items:center;gap:11px;padding:9px 0;border-bottom:1px solid var(--line)"
          >
            <div style="width:27px;height:27px;border-radius:9px;background:var(--card2);display:flex;
                 align-items:center;justify-content:center;font-weight:800;font-size:12px">{{ i + 1 }}</div>
            <div style="flex:1;font-weight:700;font-size:14px;color:var(--head)">{{ d.name }}</div>
            <div class="muted" style="font-size:13px"><Icon name="star" /> <span class="num">{{ d.rating }}</span></div>
            <div class="num muted" style="font-size:13px">{{ num(d.orders) }} طلب</div>
          </div>
          <EmptyState v-if="!(data?.top_drivers ?? []).length" text="لا توجد بيانات" />
        </template>
      </div>
    </div>

    <div v-if="can('order.view')">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
        <div style="font-weight:800;color:var(--head);font-size:17px">أحدث الطلبات</div>
        <NuxtLink to="/orders" class="muted" style="font-size:13px;font-weight:700">عرض الكل ›</NuxtLink>
      </div>

      <DataTable
        :columns="recentColumns" :rows="data?.recent_orders ?? []" :loading="loading"
        clickable @row="navigateTo(`/orders/${$event.id}`)"
      >
        <template #cell-order_no="{ row }">
          <span class="num" style="font-weight:800;color:var(--head)">{{ row.order_no }}</span>
        </template>
        <template #cell-customer="{ row }">{{ row.customer?.name ?? '—' }}</template>
        <template #cell-merchant="{ row }">{{ row.merchant?.name ?? '—' }}</template>
        <template #cell-total="{ row }"><span class="num">{{ money(row.total) }}</span></template>
        <template #cell-status="{ row }"><StatusPill :value="row.status" :map="ORDER_STATUS" /></template>
        <template #cell-created_at="{ row }"><span class="muted">{{ ago(row.created_at) }}</span></template>
      </DataTable>
    </div>
  </div>
</template>
