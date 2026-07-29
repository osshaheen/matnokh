<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const { can, loadSettings } = useAuth()
const api = useApi()
const toast = useToast()

const tab = ref<'general' | 'operations' | 'safety' | 'activity'>('general')

const form = reactive<Record<string, any>>({
  app_name: '', support_phone: '', support_email: '', currency: 'ILS',
  default_delivery_fee: 0, min_withdraw_amount: 0,
  auto_assign_driver: false, orders_enabled: true,
  deletion_enabled: true, trash_enabled: true, restore_enabled: true, maintenance_mode: false,
})

const loading = ref(true)
const saving = ref(false)

async function load() {
  loading.value = true
  try {
    const res = await api.get<{ data: any }>('/settings')
    Object.keys(form).forEach((k) => { if (k in res.data) form[k] = res.data[k] })
  } catch (e) {
    toast.error(apiError(e, 'تعذّر تحميل الإعدادات'))
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  try {
    await api.put('/settings', { ...form })
    toast.success('تم حفظ الإعدادات')
    // the sidebar and delete buttons read these toggles, so refresh them now
    await loadSettings()
  } catch (e) {
    toast.error(apiError(e))
  } finally {
    saving.value = false
  }
}

// ── activity log ────────────────────────────────────────────────────────
const activity = ref<any[]>([])
const activityLoading = ref(false)

async function loadActivity() {
  activityLoading.value = true
  try {
    const res = await api.get<any>('/activity', { per_page: 30 })
    activity.value = res.data
  } catch (e) {
    toast.error(apiError(e))
  } finally {
    activityLoading.value = false
  }
}

watch(tab, (t) => { if (t === 'activity' && !activity.value.length) loadActivity() })

onMounted(load)

const EVENTS: Record<string, string> = { created: 'إنشاء', updated: 'تعديل', deleted: 'حذف', restored: 'استعادة' }
const SUBJECTS: Record<string, string> = {
  Order: 'طلب', Merchant: 'تاجر', Driver: 'سائق', Withdraw: 'طلب سحب', Subscription: 'اشتراك',
}
</script>

<template>
  <div>
    <PageHeader title="الإعدادات" subtitle="إعدادات المنصّة العامة ومفاتيح التحكّم">
      <template #actions>
        <button v-if="can('settings.update') && tab !== 'activity'" class="btn" :disabled="saving || loading" @click="save">
          {{ saving ? 'جارٍ الحفظ…' : 'حفظ التغييرات' }}
        </button>
      </template>
    </PageHeader>

    <div class="tabs">
      <button class="tab" :class="{ active: tab === 'general' }" @click="tab = 'general'">عام</button>
      <button class="tab" :class="{ active: tab === 'operations' }" @click="tab = 'operations'">التشغيل</button>
      <button class="tab" :class="{ active: tab === 'safety' }" @click="tab = 'safety'">مفاتيح الأمان</button>
      <button class="tab" :class="{ active: tab === 'activity' }" @click="tab = 'activity'">سجل النشاطات</button>
    </div>

    <div v-if="loading && tab !== 'activity'" class="card" style="padding:24px">
      <div class="skeleton" style="height:180px" />
    </div>

    <!-- ── general ───────────────────────────────────────────────────── -->
    <div v-else-if="tab === 'general'" class="card" style="padding:24px">
      <div class="form-grid">
        <FormField label="اسم المنصّة"><input v-model="form.app_name" class="input" :disabled="!can('settings.update')"></FormField>
        <FormField label="العملة" hint="الرمز الظاهر بجانب المبالغ">
          <input v-model="form.currency" class="input" dir="ltr" :disabled="!can('settings.update')">
        </FormField>
        <FormField label="هاتف الدعم"><input v-model="form.support_phone" class="input" dir="ltr" :disabled="!can('settings.update')"></FormField>
        <FormField label="بريد الدعم"><input v-model="form.support_email" type="email" class="input" dir="ltr" :disabled="!can('settings.update')"></FormField>
      </div>
    </div>

    <!-- ── operations ────────────────────────────────────────────────── -->
    <div v-else-if="tab === 'operations'" class="card" style="padding:24px">
      <div class="form-grid">
        <FormField label="أجرة التوصيل الافتراضية" hint="تُستخدم عندما لا تحدّد المدينة أجرة">
          <input v-model.number="form.default_delivery_fee" type="number" min="0" step="0.5" class="input" dir="ltr" :disabled="!can('settings.update')">
        </FormField>
        <FormField label="الحد الأدنى للسحب">
          <input v-model.number="form.min_withdraw_amount" type="number" min="0" step="1" class="input" dir="ltr" :disabled="!can('settings.update')">
        </FormField>
      </div>

      <div style="border-top:1px solid var(--line);margin-top:20px;padding-top:18px;display:flex;flex-direction:column;gap:16px">
        <AppSwitch v-model="form.orders_enabled" label="استقبال الطلبات الجديدة" :disabled="!can('settings.update')" />
        <AppSwitch v-model="form.auto_assign_driver" label="التعيين التلقائي للسائق (عند التوفّر)" :disabled="!can('settings.update')" />
      </div>
    </div>

    <!-- ── safety ────────────────────────────────────────────────────── -->
    <div v-else-if="tab === 'safety'" class="card" style="padding:24px">
      <p class="muted" style="font-size:14px;margin-bottom:18px">
        هذه المفاتيح تتحكّم بالعمليات الحسّاسة في اللوحة، وتُطبَّق على الواجهة والـ API معاً.
      </p>

      <div style="display:flex;flex-direction:column;gap:18px">
        <div style="display:flex;align-items:flex-start;gap:14px">
          <AppSwitch v-model="form.deletion_enabled" :disabled="!can('settings.update')" />
          <div>
            <div style="font-weight:700;color:var(--head)">تفعيل الحذف</div>
            <div class="muted" style="font-size:13px">عند الإيقاف تختفي أزرار الحذف ويرفض الخادم أي طلب حذف.</div>
          </div>
        </div>

        <div style="display:flex;align-items:flex-start;gap:14px">
          <AppSwitch v-model="form.trash_enabled" :disabled="!can('settings.update')" />
          <div>
            <div style="font-weight:700;color:var(--head)">تفعيل سلّة المحذوفات</div>
            <div class="muted" style="font-size:13px">عند الإيقاف يُخفى قسم سلّة المحذوفات بالكامل.</div>
          </div>
        </div>

        <div style="display:flex;align-items:flex-start;gap:14px">
          <AppSwitch v-model="form.restore_enabled" :disabled="!can('settings.update')" />
          <div>
            <div style="font-weight:700;color:var(--head)">تفعيل الاستعادة</div>
            <div class="muted" style="font-size:13px">يسمح بإرجاع العناصر المحذوفة من السلّة.</div>
          </div>
        </div>

        <div style="display:flex;align-items:flex-start;gap:14px;border-top:1px solid var(--line);padding-top:18px">
          <AppSwitch v-model="form.maintenance_mode" :disabled="!can('settings.update')" />
          <div>
            <div style="font-weight:700;color:var(--head)">وضع الصيانة</div>
            <div class="muted" style="font-size:13px">لإشعار تطبيقات الزبائن والسائقين بأن المنصّة تحت الصيانة.</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── activity ──────────────────────────────────────────────────── -->
    <div v-else class="card">
      <div style="padding:18px 20px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between">
        <div style="font-weight:800;color:var(--head)">آخر النشاطات</div>
        <button class="btn btn-ghost btn-sm" :disabled="activityLoading" @click="loadActivity"><Icon name="refresh" /> تحديث</button>
      </div>

      <div v-if="activityLoading" style="padding:20px;display:flex;flex-direction:column;gap:12px">
        <div v-for="i in 5" :key="i" class="skeleton" style="height:38px" />
      </div>

      <EmptyState v-else-if="!activity.length" icon="clipboard" title="لا توجد نشاطات مسجّلة" />

      <div v-else>
        <div
          v-for="a in activity" :key="a.id"
          style="display:flex;align-items:center;gap:12px;padding:13px 20px;border-bottom:1px solid var(--line)"
        >
          <span
            class="pill"
            :class="{ created: 'pill-green', updated: 'pill-blue', deleted: 'pill-terra', restored: 'pill-sand' }[a.event] ?? 'pill-gray'"
          >{{ EVENTS[a.event] ?? a.event }}</span>

          <div style="flex:1;min-width:0">
            <div style="font-weight:700;font-size:14px;color:var(--head)">
              {{ SUBJECTS[a.subject_type] ?? a.subject_type }} <span class="num">#{{ a.subject_id }}</span>
            </div>
            <div class="muted" style="font-size:12px">{{ a.causer ?? 'النظام' }}</div>
          </div>

          <div class="muted" style="font-size:12px;white-space:nowrap">{{ ago(a.created_at) }}</div>
        </div>
      </div>
    </div>
  </div>
</template>
