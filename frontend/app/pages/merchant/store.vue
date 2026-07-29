<script setup lang="ts">
definePageMeta({ layout: 'merchant', middleware: 'merchant-auth' })
const m = useMerchant()
const toast = useToast()
const { confirm } = useConfirm()
const store = ref<any>(null)
const loading = ref(true)

async function load() { loading.value = true; try { store.value = (await m.http.get('/store')).data } finally { loading.value = false } }
onMounted(load)

async function patch(field: string, value: any) {
  try { store.value = (await m.http.put('/store', { [field]: value })).data }
  catch (e) { toast.error(apiError(e)); await load() }
}

// branches
const branches = ref<any[]>([])
const showBranches = ref(false)
const branchForm = reactive<any>({ name: '', phone: '', hours: '', city_id: '', lat: null, lng: null })
async function loadBranches() { branches.value = (await m.http.get('/branches')).data }
async function addBranch() {
  if (!branchForm.name.trim()) return toast.error('اكتب اسم الفرع')
  try { await m.http.post('/branches', { ...branchForm, city_id: branchForm.city_id || null }); toast.success('تمت إضافة الفرع'); Object.assign(branchForm, { name: '', phone: '', hours: '' }); await loadBranches(); await load() }
  catch (e) { toast.error(apiError(e)) }
}
async function rmBranch(b: any) { if (!await confirm({ title: `حذف ${b.name}؟`, danger: true, confirmText: 'حذف' })) return; try { await m.http.del(`/branches/${b.id}`); await loadBranches(); await load() } catch (e) { toast.error(apiError(e)) } }

// sections
const sections = ref<any[]>([])
const showSections = ref(false)
const secForm = reactive({ name: '', icon: '🍽️' })
async function loadSections() { sections.value = (await m.http.get('/sections')).data }
async function addSection() {
  if (!secForm.name.trim()) return toast.error('اكتب اسم القسم')
  try { await m.http.post('/sections', { ...secForm }); toast.success('تمت إضافة القسم'); secForm.name = ''; await loadSections() }
  catch (e) { toast.error(apiError(e)) }
}
async function rmSection(s: any) { if (!await confirm({ title: `حذف ${s.name}؟`, danger: true, confirmText: 'حذف' })) return; await m.http.del(`/sections/${s.id}`); await loadSections() }

function openBranches() { showBranches.value = true; loadBranches() }
function openSections() { showSections.value = true; loadSections() }
</script>

<template>
  <div>
    <div class="m-h1" style="margin-bottom:14px">إعدادات المتجر</div>
    <div v-if="loading" class="m-card" style="height:120px" />
    <template v-else>
      <div class="m-card" style="padding:16px;margin-bottom:14px;display:flex;align-items:center;gap:12px">
        <div style="width:52px;height:52px;border-radius:14px;background:var(--grad-green);display:flex;align-items:center;justify-content:center;color:#fff"><Icon name="store" :size="26" /></div>
        <div style="flex:1"><div style="font-weight:800;color:var(--head)">{{ store.store_name }}</div>
          <div class="muted" style="font-size:12px">{{ store.category }} · {{ store.city }}</div></div>
        <div style="text-align:left"><div class="num" style="font-weight:800">{{ money(store.balance) }}</div><div class="muted" style="font-size:11px">الرصيد</div></div>
      </div>

      <!-- status + operating switches -->
      <div class="m-card" style="padding:6px 16px;margin-bottom:14px">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid var(--line)">
          <div><div style="font-weight:700">حالة المتجر الآن</div><div class="muted" style="font-size:12px">{{ store.is_open ? 'متاح — يستقبل الطلبات' : 'مغلق' }}</div></div>
          <AppSwitch :model-value="store.is_open" @update:model-value="v => patch('is_open', v)" />
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid var(--line)">
          <div style="font-weight:700">وضع التجهيز</div>
          <AppSwitch :model-value="store.prep_mode" @update:model-value="v => patch('prep_mode', v)" />
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0">
          <div style="font-weight:700">قبول تلقائي للطلبات</div>
          <AppSwitch :model-value="store.auto_accept" @update:model-value="v => patch('auto_accept', v)" />
        </div>
      </div>

      <!-- links -->
      <div class="m-card" style="padding:6px 16px;margin-bottom:14px">
        <button style="display:flex;justify-content:space-between;align-items:center;width:100%;padding:14px 0;border-bottom:1px solid var(--line)" @click="openBranches">
          <span style="display:flex;align-items:center;gap:10px;font-weight:700"><Icon name="city" :size="18" /> الفروع ({{ store.branches_count }})</span>
          <Icon name="arrow" :size="16" style="transform:scaleX(-1)" />
        </button>
        <button style="display:flex;justify-content:space-between;align-items:center;width:100%;padding:14px 0;border-bottom:1px solid var(--line)" @click="openSections">
          <span style="display:flex;align-items:center;gap:10px;font-weight:700"><Icon name="folder" :size="18" /> الأقسام</span>
          <Icon name="arrow" :size="16" style="transform:scaleX(-1)" />
        </button>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 0">
          <span style="display:flex;align-items:center;gap:10px;font-weight:700"><Icon name="star" :size="18" /> الباقة</span>
          <span class="pill pill-sand">{{ store.subscription?.plan || 'بدون باقة' }}</span>
        </div>
      </div>

      <button class="btn btn-ghost" style="width:100%;color:var(--terra)" @click="m.logout()">تسجيل الخروج</button>
    </template>

    <!-- branches modal -->
    <AppModal v-model="showBranches" title="فروع المتجر">
      <div class="m-card" style="padding:12px;margin-bottom:12px">
        <div class="form-grid">
          <FormField label="اسم الفرع"><input v-model="branchForm.name" class="input"></FormField>
          <FormField label="هاتف الفرع"><input v-model="branchForm.phone" class="input" dir="ltr"></FormField>
          <FormField label="أوقات الدوام" full><input v-model="branchForm.hours" class="input" placeholder="09:00 - 23:00"></FormField>
        </div>
        <button class="btn btn-sm" style="width:100%;margin-top:8px" @click="addBranch"><Icon name="plus" :size="14" /> أضف الفرع</button>
      </div>
      <div v-for="b in branches" :key="b.id" style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--line)">
        <div><div style="font-weight:700">{{ b.name }} <span v-if="b.is_main" class="pill pill-green" style="font-size:10px">رئيسي</span></div>
          <div class="muted" style="font-size:12px" dir="ltr">{{ b.phone }} · {{ b.hours }}</div></div>
        <button class="icon-btn danger" @click="rmBranch(b)"><Icon name="trash" :size="15" /></button>
      </div>
    </AppModal>

    <!-- sections modal -->
    <AppModal v-model="showSections" title="أقسام المتجر">
      <div style="display:flex;gap:8px;margin-bottom:12px">
        <input v-model="secForm.icon" class="input" style="width:64px;text-align:center">
        <input v-model="secForm.name" class="input" placeholder="اسم القسم">
        <button class="btn btn-sm" @click="addSection">أضف</button>
      </div>
      <div v-for="s in sections" :key="s.id" style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--line)">
        <div style="font-weight:700">{{ s.icon }} {{ s.name }} <span class="muted" style="font-size:12px">({{ s.products_count ?? 0 }})</span></div>
        <button class="icon-btn danger" @click="rmSection(s)"><Icon name="trash" :size="15" /></button>
      </div>
    </AppModal>
  </div>
</template>
