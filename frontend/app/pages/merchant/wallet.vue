<script setup lang="ts">
definePageMeta({ layout: 'merchant', middleware: 'merchant-auth' })
const m = useMerchant()
const toast = useToast()
const data = ref<any>(null)
const loading = ref(true)
const show = ref(false)
const amount = ref(0)
const saving = ref(false)

async function load() {
  loading.value = true
  try { data.value = await m.http.get('/wallet') } finally { loading.value = false }
}
onMounted(load)

async function submit() {
  saving.value = true
  try { await m.http.post('/withdraws', { amount: amount.value }); toast.success('تم إرسال طلب السحب'); show.value = false; await load() }
  catch (e) { toast.error(apiError(e)) } finally { saving.value = false }
}
</script>

<template>
  <div>
    <div class="m-h1" style="margin-bottom:14px">المحفظة والسحب</div>

    <div class="m-card" style="padding:22px;text-align:center;margin-bottom:16px;background:var(--grad-green);color:#fff">
      <div style="font-size:13px;opacity:.9">الرصيد القابل للسحب</div>
      <div style="font-size:32px;font-weight:900" class="num">{{ money(data?.balance ?? 0) }}</div>
    </div>

    <button class="btn" style="width:100%;margin-bottom:8px" :disabled="data?.has_pending_withdraw" @click="show = true; amount = data?.balance ?? 0">
      {{ data?.has_pending_withdraw ? 'لديك طلب سحب معلّق' : 'طلب سحب رصيد' }}
    </button>
    <p class="muted" style="font-size:12px;text-align:center;margin-bottom:16px">الحد الأدنى للسحب: <span class="num">{{ money(data?.min_withdraw ?? 0) }}</span></p>

    <div style="font-weight:800;color:var(--head);margin-bottom:10px">سجل المعاملات</div>
    <div v-if="loading" class="m-card" style="height:120px" />
    <div v-else class="m-card" style="padding:6px 14px">
      <div v-for="t in data.transactions" :key="t.id" style="display:flex;justify-content:space-between;align-items:center;padding:11px 0;border-bottom:1px solid var(--line)">
        <div>
          <div style="font-weight:700;font-size:14px">{{ t.type === 'funding' ? ('تمويل' + (t.order_no ? ' — ' + t.order_no : '')) : 'سحب رصيد' }}</div>
          <div class="muted" style="font-size:12px">{{ dateTime(t.created_at) }} · {{ t.status === 'pending' ? 'معلّق' : (t.method || 'منفّذ') }}</div>
        </div>
        <div class="num" style="font-weight:800" :style="{ color: t.type === 'funding' ? 'var(--green-d)' : 'var(--terra)' }">
          {{ t.type === 'funding' ? '+' : '−' }}{{ money(t.amount) }}
        </div>
      </div>
    </div>

    <AppModal v-model="show" title="طلب سحب رصيد">
      <FormField label="المبلغ"><input v-model.number="amount" type="number" min="1" class="input" dir="ltr"></FormField>
      <template #footer>
        <button class="btn" :disabled="saving" @click="submit">إرسال الطلب</button>
        <button class="btn btn-ghost" @click="show = false">إلغاء</button>
      </template>
    </AppModal>
  </div>
</template>
