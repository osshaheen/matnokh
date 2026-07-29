<script setup lang="ts">
definePageMeta({ layout: 'merchant', middleware: 'merchant-auth' })
const m = useMerchant()
const toast = useToast()
const tab = ref<'current' | 'completed'>('current')
const rows = ref<any[]>([])
const loading = ref(true)
const busy = ref<number | null>(null)

const STATUS: Record<string, string> = { pending: 'جديد — بانتظار القبول', preparing: 'قيد التجهيز', ready: 'جاهز — بُثّ للمناديب', accepted: 'مقبول', delivered: 'مكتمل', on_the_way: 'في الطريق', picked_up: 'استلمه المندوب', rejected: 'مرفوض', canceled: 'ملغي', returned: 'مُرتجع' }

async function load() {
  loading.value = true
  try { const r = await m.http.get('/orders', { tab: tab.value }); rows.value = r.data } finally { loading.value = false }
}
watch(tab, load)
onMounted(load)

async function act(o: any, action: 'accept' | 'reject' | 'ready') {
  busy.value = o.id
  try {
    const r = await m.http.post(`/orders/${o.id}/${action}`, action === 'reject' ? { reason: 'غير متوفر حالياً' } : {})
    toast.success(r.message)
    await load()
  } catch (e) { toast.error(apiError(e)) } finally { busy.value = null }
}
</script>

<template>
  <div>
    <div class="m-h1" style="margin-bottom:14px">الطلبات</div>
    <div style="display:flex;gap:8px;margin-bottom:14px">
      <button v-for="t in (['current','completed'] as const)" :key="t" class="btn btn-sm"
        :class="tab === t ? '' : 'btn-ghost'" style="flex:1" @click="tab = t">
        {{ t === 'current' ? 'الجارية' : 'المكتملة' }}
      </button>
    </div>

    <p class="muted" style="font-size:12px;text-align:center;margin-bottom:12px">التفاوض على السعر لا يشملك — أجرة التوصيل فقط</p>

    <div v-if="loading" class="m-card" style="height:120px" />
    <div v-else-if="!rows.length" class="m-card" style="padding:40px;text-align:center">
      <Icon name="clipboard" :size="40" style="color:var(--sage)" />
      <div class="muted" style="margin-top:8px">لا توجد طلبات في هذا التبويب</div>
    </div>

    <div v-for="o in rows" :key="o.id" class="m-card" style="padding:14px;margin-bottom:12px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <div>
          <div style="font-weight:800" class="num" dir="ltr">{{ o.order_no }}</div>
          <div class="muted" style="font-size:12px">{{ o.recipient_name || o.customer }}</div>
        </div>
        <span class="pill" :class="['pending','ready'].includes(o.status) ? 'pill-sand' : 'pill-green'" style="font-size:11px">{{ STATUS[o.status] || o.status }}</span>
      </div>

      <div v-if="o.items?.length" style="margin:10px 0;padding:10px 0;border-top:1px solid var(--line);border-bottom:1px solid var(--line)">
        <div v-for="(it, i) in o.items" :key="i" style="display:flex;justify-content:space-between;font-size:13px;padding:2px 0">
          <span>{{ it.qty }}× {{ it.name }}</span>
          <span class="num muted">{{ money(it.line_total) }}</span>
        </div>
      </div>

      <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px">
        <span class="muted">أجرة التوصيل: <span class="num">{{ money(o.delivery_fee) }}</span></span>
        <span style="font-weight:800" class="num">{{ money(o.items_total) }}</span>
      </div>

      <div v-if="o.status === 'pending'" style="display:flex;gap:8px;margin-top:12px">
        <button class="btn btn-sm" style="flex:1" :disabled="busy === o.id" @click="act(o, 'accept')">قبول</button>
        <button class="btn btn-sm btn-danger" :disabled="busy === o.id" @click="act(o, 'reject')">رفض</button>
      </div>
      <div v-else-if="['accepted','preparing'].includes(o.status)" style="margin-top:12px">
        <button class="btn btn-sm" style="width:100%" :disabled="busy === o.id" @click="act(o, 'ready')">الطلب جاهز — بثّ للمناديب</button>
      </div>
    </div>
  </div>
</template>
