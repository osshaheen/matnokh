<script setup lang="ts">
definePageMeta({ layout: 'merchant', middleware: 'merchant-auth' })
const m = useMerchant()
const data = ref<any>(null)
const loading = ref(true)
onMounted(async () => {
  try { const r = await m.http.get('/dashboard'); data.value = r.data } finally { loading.value = false }
})
const k = computed(() => data.value?.kpis ?? {})
const chartMax = computed(() => Math.max(1, ...(data.value?.sales_chart ?? []).map((d: any) => d.sales)))
const STATUS: Record<string, string> = { pending: 'جديد', preparing: 'قيد التجهيز', ready: 'جاهز', accepted: 'مقبول', delivered: 'مكتمل', on_the_way: 'في الطريق', picked_up: 'استلمه المندوب', rejected: 'مرفوض', canceled: 'ملغي' }
</script>

<template>
  <div>
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:18px">
      <div>
        <div class="m-h1">صباح الخير <Icon name="wave" :size="18" /></div>
        <p class="muted" style="font-size:13px">{{ data?.store_name }} — {{ data?.city }}</p>
      </div>
      <span class="pill" :class="data?.is_open ? 'pill-green' : 'pill-sand'">{{ data?.is_open ? 'متاح' : 'مغلق' }}</span>
    </div>

    <div v-if="loading" class="m-card" style="height:120px" />
    <template v-else>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
        <div class="m-card" style="padding:16px">
          <div class="muted" style="font-size:12px">مبيعات هذا الشهر</div>
          <div style="font-size:22px;font-weight:800;color:var(--head)" class="num">{{ money(k.sales_month) }}</div>
        </div>
        <div class="m-card" style="padding:16px">
          <div class="muted" style="font-size:12px">تقييم المتجر</div>
          <div style="font-size:22px;font-weight:800;color:var(--head)"><Icon name="star" :size="16" /> {{ k.rating }}</div>
        </div>
        <div class="m-card" style="padding:16px">
          <div class="muted" style="font-size:12px">منتجات</div>
          <div style="font-size:22px;font-weight:800;color:var(--head)" class="num">{{ k.products }}</div>
        </div>
        <div class="m-card" style="padding:16px">
          <div class="muted" style="font-size:12px">فروع</div>
          <div style="font-size:22px;font-weight:800;color:var(--head)" class="num">{{ k.branches }}</div>
        </div>
      </div>

      <div class="m-card" style="padding:16px;margin-bottom:16px">
        <div class="muted" style="font-size:12px;margin-bottom:10px">مبيعات آخر 7 أيام</div>
        <div style="display:flex;align-items:flex-end;gap:6px;height:70px">
          <div v-for="(d, i) in data.sales_chart" :key="i" style="flex:1;display:flex;flex-direction:column;justify-content:flex-end;height:100%">
            <div :style="{ height: (d.sales / chartMax * 100) + '%', minHeight: '3px', background: 'var(--grad-green)', borderRadius: '5px 5px 0 0' }" />
          </div>
        </div>
      </div>

      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
        <div style="font-weight:800;color:var(--head)">آخر الطلبات</div>
        <NuxtLink to="/merchant/orders" class="muted" style="font-size:13px;font-weight:700">عرض الكل</NuxtLink>
      </div>
      <div class="m-card" style="padding:6px 14px">
        <div v-for="o in data.latest_orders" :key="o.id" style="display:flex;justify-content:space-between;align-items:center;padding:11px 0;border-bottom:1px solid var(--line)">
          <div>
            <div style="font-weight:700;font-size:14px" class="num" dir="ltr">{{ o.order_no }}</div>
            <div class="muted" style="font-size:12px">{{ o.recipient_name || o.customer }}</div>
          </div>
          <div style="text-align:left">
            <div class="num" style="font-weight:800">{{ money(o.items_total) }}</div>
            <span class="pill" style="font-size:10px" :class="['pending','ready'].includes(o.status) ? 'pill-sand' : 'pill-green'">{{ STATUS[o.status] || o.status }}</span>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
