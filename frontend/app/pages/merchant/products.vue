<script setup lang="ts">
definePageMeta({ layout: 'merchant', middleware: 'merchant-auth' })
const m = useMerchant()
const toast = useToast()
const { confirm } = useConfirm()

const tab = ref('')
const rows = ref<any[]>([])
const sections = ref<any[]>([])
const branches = ref<any[]>([])
const loading = ref(true)

async function load() {
  loading.value = true
  try {
    const [p, s, b] = await Promise.all([
      m.http.get('/products', { status: tab.value }),
      m.http.get('/sections'), m.http.get('/branches'),
    ])
    rows.value = p.data; sections.value = s.data; branches.value = b.data
  } finally { loading.value = false }
}
watch(tab, load)
onMounted(load)

// form
const show = ref(false)
const editing = ref<any>(null)
const form = reactive<any>({ name: '', description: '', store_section_id: '', price: 0, price_before: null, status: 'active', images: [] as string[], addons: [] as any[] })
const saving = ref(false)

function open(row: any = null) {
  editing.value = row
  Object.assign(form, {
    name: row?.name ?? '', description: row?.description ?? '', store_section_id: row?.store_section_id ?? '',
    price: row?.price ?? 0, price_before: row?.price_before ?? null, status: row?.status ?? 'active',
    images: row?.images ? [...row.images] : [], addons: row?.addons ? row.addons.map((a: any) => ({ name: a.name, price: a.price })) : [],
  })
  show.value = true
}
function addImg(url: string) { if (url) form.images.push(url) }
function rmImg(i: number) { form.images.splice(i, 1) }
function addAddon() { if (form.addons.length < 3) form.addons.push({ name: '', price: 0 }) }
function rmAddon(i: number) { form.addons.splice(i, 1) }

async function submit() {
  if (!form.name.trim()) return toast.error('اكتب اسم المنتج')
  if (!form.images.length) return toast.error('أضف صورة واحدة على الأقل')
  saving.value = true
  const payload: any = { ...form, price_before: form.price_before || null }
  if (!form.store_section_id) delete payload.store_section_id
  try {
    if (editing.value) await m.http.put(`/products/${editing.value.id}`, payload)
    else await m.http.post('/products', payload)
    toast.success('تم حفظ المنتج'); show.value = false; await load()
  } catch (e) { toast.error(apiError(e)) } finally { saving.value = false }
}

async function remove(row: any) {
  if (!await confirm({ title: `حذف ${row.name}؟`, danger: true, confirmText: 'حذف' })) return
  await m.http.del(`/products/${row.id}`); toast.success('تم الحذف'); await load()
}
async function toggleStock(row: any, branchId: number, val: boolean) {
  try { await m.http.patch(`/products/${row.id}/stock`, { branch_id: branchId, in_stock: val }); await load() }
  catch (e) { toast.error(apiError(e)) }
}
const STATUS: Record<string, string> = { active: 'نشط', draft: 'مسودة', archived: 'مؤرشف' }
</script>

<template>
  <div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
      <div class="m-h1">المنتجات</div>
      <button class="btn btn-sm" @click="open()"><Icon name="plus" :size="15" /> منتج</button>
    </div>

    <div style="display:flex;gap:6px;margin-bottom:14px;overflow-x:auto">
      <button v-for="t in [['','الكل'],['active','نشط'],['draft','مسودة'],['archived','مؤرشف']]" :key="t[0]"
        class="btn btn-sm" :class="tab === t[0] ? '' : 'btn-ghost'" @click="tab = t[0]">{{ t[1] }}</button>
    </div>

    <div v-if="loading" class="m-card" style="height:120px" />
    <div v-else-if="!rows.length" class="m-card" style="padding:40px;text-align:center">
      <Icon name="box" :size="40" style="color:var(--sage)" /><div class="muted" style="margin-top:8px">لا توجد منتجات</div>
    </div>

    <div v-for="p in rows" :key="p.id" class="m-card" style="padding:12px;margin-bottom:12px">
      <div style="display:flex;gap:12px">
        <div style="width:60px;height:60px;border-radius:12px;overflow:hidden;background:var(--card2);flex-shrink:0">
          <img v-if="p.images?.[0]" :src="p.images[0]" style="width:100%;height:100%;object-fit:cover">
        </div>
        <div style="flex:1;min-width:0">
          <div style="display:flex;justify-content:space-between">
            <div style="font-weight:700;color:var(--head)">{{ p.name }}</div>
            <span class="pill" style="font-size:10px" :class="p.status === 'active' ? 'pill-green' : 'pill-sand'">{{ STATUS[p.status] }}</span>
          </div>
          <div class="muted" style="font-size:12px">{{ p.section || '—' }}</div>
          <div style="display:flex;align-items:center;gap:8px;margin-top:3px">
            <span class="num" style="font-weight:800">{{ money(p.price) }}</span>
            <span v-if="p.discount" class="num muted" style="text-decoration:line-through;font-size:12px">{{ money(p.price_before) }}</span>
            <span v-if="p.discount" class="pill pill-terra" style="font-size:10px">خصم {{ p.discount }}%</span>
          </div>
        </div>
      </div>
      <!-- per-branch stock -->
      <div v-if="branches.length" style="display:flex;flex-wrap:wrap;gap:6px;margin-top:10px">
        <button v-for="b in branches" :key="b.id" class="pill" style="font-size:11px;cursor:pointer"
          :class="(p.stock?.find((s:any)=>s.branch_id===b.id)?.in_stock ?? true) ? 'pill-green' : 'pill-terra'"
          @click="toggleStock(p, b.id, !(p.stock?.find((s:any)=>s.branch_id===b.id)?.in_stock ?? true))">
          {{ b.name }}: {{ (p.stock?.find((s:any)=>s.branch_id===b.id)?.in_stock ?? true) ? 'متوفر' : 'نفد' }}
        </button>
      </div>
      <div style="display:flex;gap:8px;margin-top:10px">
        <button class="btn btn-ghost btn-sm" style="flex:1" @click="open(p)"><Icon name="edit" :size="13" /> تعديل</button>
        <button class="icon-btn danger" @click="remove(p)"><Icon name="trash" :size="15" /></button>
      </div>
    </div>

    <AppModal v-model="show" :title="editing ? 'تعديل منتج' : 'منتج جديد'">
      <div class="form-grid">
        <FormField label="اسم المنتج *"><input v-model="form.name" class="input"></FormField>
        <FormField label="القسم">
          <select v-model="form.store_section_id" class="input">
            <option value="">— بدون —</option>
            <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
        </FormField>
        <FormField label="السعر (₪) *"><input v-model.number="form.price" type="number" min="0" step="0.5" class="input" dir="ltr"></FormField>
        <FormField label="السعر قبل الخصم (اختياري)"><input v-model.number="form.price_before" type="number" min="0" step="0.5" class="input" dir="ltr" placeholder="اتركه فارغاً بلا عرض"></FormField>
        <FormField label="الوصف" full><textarea v-model="form.description" class="input"></textarea></FormField>
        <FormField label="الحالة">
          <select v-model="form.status" class="input"><option value="active">نشط</option><option value="draft">مسودة</option><option value="archived">مؤرشف</option></select>
        </FormField>

        <FormField label="صور المنتج (واحدة على الأقل)" full>
          <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
            <div v-for="(img, i) in form.images" :key="i" style="position:relative;width:56px;height:56px;border-radius:10px;overflow:hidden">
              <img :src="img" style="width:100%;height:100%;object-fit:cover">
              <button type="button" style="position:absolute;top:2px;left:2px;background:#0008;color:#fff;border-radius:6px;width:18px;height:18px;font-size:11px" @click="rmImg(i)">×</button>
            </div>
            <ImageUpload :model-value="''" @update:model-value="addImg" />
          </div>
        </FormField>

        <FormField label="إضافات على المنتج (حتى 3)" full>
          <div v-for="(a, i) in form.addons" :key="i" style="display:flex;gap:8px;margin-bottom:6px">
            <input v-model="a.name" class="input" placeholder="اسم الإضافة">
            <input v-model.number="a.price" type="number" min="0" class="input" style="width:90px" dir="ltr" placeholder="السعر">
            <button type="button" class="icon-btn danger" @click="rmAddon(i)"><Icon name="x" :size="14" /></button>
          </div>
          <button v-if="form.addons.length < 3" type="button" class="btn btn-ghost btn-sm" @click="addAddon"><Icon name="plus" :size="13" /> إضافة</button>
        </FormField>
      </div>
      <template #footer>
        <button class="btn" :disabled="saving" @click="submit">حفظ المنتج</button>
        <button class="btn btn-ghost" @click="show = false">إلغاء</button>
      </template>
    </AppModal>
  </div>
</template>
