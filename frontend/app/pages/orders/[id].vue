<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const route = useRoute()
const api = useApi()
const toast = useToast()
const { can } = useAuth()
const { lookups, load: loadLookups } = useLookups()
const { confirm } = useConfirm()

const id = route.params.id as string
const order = ref<any>(null)
const loading = ref(true)
const busy = ref(false)

async function load() {
  loading.value = true
  try {
    const res = await api.get<{ data: any }>(`/orders/${id}`)
    order.value = res.data
  } catch (e) {
    toast.error(apiError(e, 'تعذّر تحميل الطلب'))
  } finally {
    loading.value = false
  }
}

onMounted(() => { load(); loadLookups() })

/** Only forward moves are offered, and only while the order is still open. */
const nextStatuses = computed(() => {
  const flow: Record<string, string[]> = {
    pending: ['accepted', 'canceled'],
    accepted: ['picked_up', 'canceled'],
    picked_up: ['on_the_way', 'canceled'],
    on_the_way: ['delivered', 'returned'],
  }
  return flow[order.value?.status] ?? []
})

async function setStatus(status: string) {
  const label = ORDER_STATUS[status]?.[0] ?? status
  let cancel_reason: string | undefined

  if (status === 'canceled') {
    if (!await confirm({ title: 'إلغاء الطلب؟', text: 'لا يمكن التراجع بعد الإلغاء.', danger: true, confirmText: 'إلغاء الطلب' })) return
    cancel_reason = 'أُلغي من لوحة التحكم'
  }

  busy.value = true
  try {
    const res = await api.patch<{ data: any }>(`/orders/${id}/status`, { status, cancel_reason })
    order.value = res.data
    toast.success(`تم تحديث الحالة إلى: ${label}`)
  } catch (e) {
    toast.error(apiError(e))
  } finally {
    busy.value = false
  }
}

// ── assign driver ───────────────────────────────────────────────────────
const showAssign = ref(false)
const driverId = ref<any>('')

async function assign() {
  if (!driverId.value) return
  busy.value = true
  try {
    const res = await api.patch<{ data: any }>(`/orders/${id}/assign`, { driver_id: driverId.value })
    order.value = res.data
    showAssign.value = false
    toast.success('تم تعيين السائق')
  } catch (e) {
    toast.error(apiError(e))
  } finally {
    busy.value = false
  }
}

// ── edit ────────────────────────────────────────────────────────────────
const showEdit = ref(false)
const form = reactive<any>({})

function openEdit() {
  Object.assign(form, {
    drop_address: order.value.drop_address,
    pickup_address: order.value.pickup_address,
    recipient_name: order.value.recipient_name,
    recipient_phone: order.value.recipient_phone,
    items_total: order.value.items_total,
    delivery_fee: order.value.delivery_fee,
    discount: order.value.discount,
    payment_method: order.value.payment_method,
    is_paid: order.value.is_paid,
    notes: order.value.notes,
  })
  showEdit.value = true
}

async function saveEdit() {
  busy.value = true
  try {
    const res = await api.put<{ data: any }>(`/orders/${id}`, form)
    order.value = { ...order.value, ...res.data }
    showEdit.value = false
    toast.success('تم حفظ التعديلات')
    load()
  } catch (e) {
    toast.error(apiError(e))
  } finally {
    busy.value = false
  }
}

const money_ = money
const rows = computed(() => order.value ? [
  ['قيمة الطلب', money_(order.value.items_total)],
  ['أجرة التوصيل', money_(order.value.delivery_fee)],
  ['الخصم', `− ${money_(order.value.discount)}`],
  ['العمولة', money_(order.value.commission)],
] : [])
</script>

<template>
  <div>
    <div v-if="loading" class="card" style="padding:30px">
      <div class="skeleton" style="height:22px;width:220px;margin-bottom:16px" />
      <div class="skeleton" style="height:150px" />
    </div>

    <template v-else-if="order">
      <PageHeader :title="`الطلب ${order.order_no}`" :subtitle="dateTime(order.created_at)" back="/orders">
        <template #actions>
          <button v-if="can('order.update')" class="btn btn-ghost" @click="openEdit"><Icon name="edit" /> تعديل</button>
          <button
            v-if="can('order.update') && !['delivered', 'canceled', 'returned'].includes(order.status)"
            class="btn btn-ghost" @click="showAssign = true"
          >
            <Icon name="car" /> {{ order.driver ? 'تغيير السائق' : 'تعيين سائق' }}
          </button>
        </template>
      </PageHeader>

      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:20px">
        <StatusPill :value="order.status" :map="ORDER_STATUS" />
        <span class="pill pill-gray">{{ PAYMENT[order.payment_method] }}</span>
        <span class="pill" :class="order.is_paid ? 'pill-green' : 'pill-sand'">
          {{ order.is_paid ? 'مدفوع' : 'غير مدفوع' }}
        </span>

        <div v-if="can('order.update') && nextStatuses.length" style="display:flex;gap:8px;margin-right:auto;flex-wrap:wrap">
          <button
            v-for="s in nextStatuses" :key="s"
            class="btn btn-sm" :class="['canceled', 'returned'].includes(s) ? 'btn-danger' : ''"
            :disabled="busy" @click="setStatus(s)"
          >
            {{ ORDER_STATUS[s][0] }}
          </button>
        </div>
      </div>

      <div class="grid grid-2" style="margin-bottom:18px">
        <!-- parties -->
        <div class="card" style="padding:20px">
          <div style="font-weight:800;color:var(--head);margin-bottom:14px">الأطراف</div>

          <div style="display:flex;flex-direction:column;gap:14px">
            <div style="display:flex;align-items:center;gap:11px">
              <div style="width:38px;height:38px;border-radius:12px;background:var(--grad-blue);display:flex;
                   align-items:center;justify-content:center;font-size:17px"><Icon name="user" /></div>
              <div style="min-width:0;flex:1">
                <div class="muted" style="font-size:12px">الزبون</div>
                <div style="font-weight:700;color:var(--head)">{{ order.customer?.name ?? '—' }}</div>
              </div>
              <a v-if="order.customer?.phone" :href="`tel:${order.customer.phone}`" class="num muted" style="font-size:13px">{{ order.customer.phone }}</a>
            </div>

            <div style="display:flex;align-items:center;gap:11px">
              <div style="width:38px;height:38px;border-radius:12px;background:var(--grad-terra);display:flex;
                   align-items:center;justify-content:center;font-size:17px"><Icon name="store" /></div>
              <div style="min-width:0;flex:1">
                <div class="muted" style="font-size:12px">المتجر</div>
                <div style="font-weight:700;color:var(--head)">{{ order.merchant?.name ?? '—' }}</div>
              </div>
              <a v-if="order.merchant?.phone" :href="`tel:${order.merchant.phone}`" class="num muted" style="font-size:13px">{{ order.merchant.phone }}</a>
            </div>

            <div style="display:flex;align-items:center;gap:11px">
              <div style="width:38px;height:38px;border-radius:12px;background:var(--grad-green);display:flex;
                   align-items:center;justify-content:center;font-size:17px"><Icon name="car" /></div>
              <div style="min-width:0;flex:1">
                <div class="muted" style="font-size:12px">السائق</div>
                <div style="font-weight:700;color:var(--head)">{{ order.driver?.name ?? 'غير معيّن' }}</div>
              </div>
              <a v-if="order.driver?.phone" :href="`tel:${order.driver.phone}`" class="num muted" style="font-size:13px">{{ order.driver.phone }}</a>
            </div>
          </div>

          <div style="border-top:1px solid var(--line);margin-top:16px;padding-top:14px;display:flex;flex-direction:column;gap:10px">
            <div>
              <div class="muted" style="font-size:12px">عنوان الاستلام</div>
              <div style="font-weight:700">{{ order.pickup_address || '—' }}</div>
            </div>
            <div>
              <div class="muted" style="font-size:12px">عنوان التسليم</div>
              <div style="font-weight:700">{{ order.drop_address || '—' }}</div>
            </div>
            <div v-if="order.recipient_name || order.recipient_phone">
              <div class="muted" style="font-size:12px">المستلم</div>
              <div style="font-weight:700">
                {{ order.recipient_name || '—' }}
                <span class="num muted">{{ order.recipient_phone ? ` · ${order.recipient_phone}` : '' }}</span>
              </div>
            </div>
            <div v-if="order.notes">
              <div class="muted" style="font-size:12px">ملاحظات</div>
              <div>{{ order.notes }}</div>
            </div>
            <div v-if="order.cancel_reason">
              <div class="muted" style="font-size:12px">سبب الإلغاء</div>
              <div style="color:#a5623f;font-weight:700">{{ order.cancel_reason }}</div>
            </div>
          </div>
        </div>

        <!-- money -->
        <div class="card" style="padding:20px;align-self:start">
          <div style="font-weight:800;color:var(--head);margin-bottom:14px">الحساب</div>

          <div v-for="(r, i) in rows" :key="i" style="display:flex;justify-content:space-between;padding:8px 0;font-size:14px">
            <span class="muted">{{ r[0] }}</span>
            <span class="num" style="font-weight:700">{{ r[1] }}</span>
          </div>

          <div style="display:flex;justify-content:space-between;border-top:1px solid var(--line);margin-top:8px;padding-top:12px">
            <span style="font-weight:800;color:var(--head)">الإجمالي</span>
            <span class="num" style="font-weight:800;font-size:19px;color:var(--green-d)">{{ money(order.total) }}</span>
          </div>

          <div style="border-top:1px solid var(--line);margin-top:14px;padding-top:12px;display:flex;flex-direction:column;gap:7px;font-size:13px">
            <div style="display:flex;justify-content:space-between"><span class="muted">المدينة</span><span>{{ order.city ?? '—' }}</span></div>
            <div style="display:flex;justify-content:space-between"><span class="muted">الخدمة</span><span>{{ order.service ?? '—' }}</span></div>
          </div>
        </div>
      </div>

      <!-- timeline -->
      <div class="card" style="padding:20px">
        <div style="font-weight:800;color:var(--head);margin-bottom:16px">مسار الطلب</div>

        <div v-if="!(order.timeline ?? []).length" class="muted" style="font-size:14px">لا توجد حركات مسجّلة.</div>

        <div v-else style="display:flex;flex-direction:column;gap:0">
          <div v-for="(t, i) in order.timeline" :key="t.id" style="display:flex;gap:13px">
            <div style="display:flex;flex-direction:column;align-items:center">
              <div
                :style="{ width: '13px', height: '13px', borderRadius: '50%', marginTop: '6px',
                          background: i === 0 ? 'var(--green)' : 'var(--sage)' }"
              />
              <div v-if="i < order.timeline.length - 1" style="width:2px;flex:1;background:var(--line);margin:4px 0" />
            </div>
            <div style="padding-bottom:18px;flex:1">
              <div style="display:flex;align-items:center;gap:9px;flex-wrap:wrap">
                <StatusPill :value="t.status" :map="ORDER_STATUS" />
                <span class="muted" style="font-size:12px">{{ dateTime(t.at) }}</span>
              </div>
              <div v-if="t.note" style="font-size:14px;margin-top:3px">{{ t.note }}</div>
              <div v-if="t.by" class="muted" style="font-size:12px">بواسطة: {{ t.by }}</div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <EmptyState v-else icon="box" title="الطلب غير موجود" text="ربما حُذف أو أن الرابط غير صحيح" />

    <!-- ── assign driver ─────────────────────────────────────────────── -->
    <AppModal v-model="showAssign" title="تعيين سائق" subtitle="سيتم إشعار السائق بالطلب">
      <FormField label="السائق">
        <select v-model="driverId" class="input">
          <option value="">— اختر سائقاً —</option>
          <option v-for="d in lookups.drivers" :key="d.id" :value="d.id">
            {{ d.name }} — {{ d.is_available ? 'متاح' : 'غير متاح' }}
          </option>
        </select>
      </FormField>

      <template #footer>
        <button class="btn" :disabled="busy || !driverId" @click="assign">تعيين</button>
        <button class="btn btn-ghost" @click="showAssign = false">إلغاء</button>
      </template>
    </AppModal>

    <!-- ── edit ──────────────────────────────────────────────────────── -->
    <AppModal v-model="showEdit" title="تعديل الطلب" width="640px">
      <form id="edit-order" class="form-grid" @submit.prevent="saveEdit">
        <FormField label="عنوان الاستلام" full><input v-model="form.pickup_address" class="input"></FormField>
        <FormField label="عنوان التسليم" full><input v-model="form.drop_address" class="input" required></FormField>
        <FormField label="اسم المستلم"><input v-model="form.recipient_name" class="input"></FormField>
        <FormField label="هاتف المستلم"><input v-model="form.recipient_phone" class="input" dir="ltr"></FormField>
        <FormField label="قيمة الطلب"><input v-model.number="form.items_total" type="number" min="0" step="0.01" class="input" dir="ltr"></FormField>
        <FormField label="أجرة التوصيل"><input v-model.number="form.delivery_fee" type="number" min="0" step="0.01" class="input" dir="ltr"></FormField>
        <FormField label="الخصم"><input v-model.number="form.discount" type="number" min="0" step="0.01" class="input" dir="ltr"></FormField>
        <FormField label="طريقة الدفع">
          <select v-model="form.payment_method" class="input">
            <option v-for="o in options(PAYMENT)" :key="o.value" :value="o.value">{{ o.label }}</option>
          </select>
        </FormField>
        <FormField label="حالة الدفع" full>
          <AppSwitch v-model="form.is_paid" label="تم استلام المبلغ" />
        </FormField>
        <FormField label="ملاحظات" full><textarea v-model="form.notes" class="input" /></FormField>
      </form>

      <template #footer>
        <button type="submit" form="edit-order" class="btn" :disabled="busy">حفظ</button>
        <button class="btn btn-ghost" @click="showEdit = false">إلغاء</button>
      </template>
    </AppModal>
  </div>
</template>
