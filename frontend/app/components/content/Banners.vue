<script setup lang="ts">
import type { Column } from '~/types/table'

const { can } = useAuth()
const { confirm } = useConfirm()

const banners = useResource('/banners', { is_active: '', position: '', audience: '' })

const columns: Column[] = [
  { key: 'title', label: 'البانر', sortable: true },
  { key: 'position', label: 'الموضع' },
  { key: 'audience', label: 'الجمهور' },
  { key: 'period', label: 'الفترة' },
  { key: 'sort', label: 'الترتيب', sortable: true },
  { key: 'is_active', label: 'مفعّل' },
  { key: 'actions', label: '', width: '90px' },
]

const show = ref(false)
const editing = ref<any>(null)
const form = reactive({
  title: '', image: '', link: '', position: 'home_top', audience: 'all',
  is_active: true, sort: 0, starts_at: '', ends_at: '',
})

function open(row: any = null) {
  editing.value = row
  Object.assign(form, {
    title: row?.title ?? '', image: row?.image ?? '', link: row?.link ?? '',
    position: row?.position ?? 'home_top', audience: row?.audience ?? 'all',
    is_active: row?.is_active ?? true, sort: row?.sort ?? 0,
    starts_at: row?.starts_at ?? '', ends_at: row?.ends_at ?? '',
  })
  show.value = true
}

async function submit() {
  const payload = { ...form, starts_at: form.starts_at || null, ends_at: form.ends_at || null }
  const res = editing.value ? await banners.update(editing.value.id, payload) : await banners.create(payload)
  if (res) show.value = false
}

async function remove(row: any) {
  if (!await confirm({ title: `حذف البانر «${row.title}»؟`, danger: true, confirmText: 'حذف' })) return
  await banners.remove(row.id)
}
</script>

<template>
  <div>
    <div class="toolbar">
      <input v-model="banners.query.search" class="input input-sm grow" placeholder="بحث بعنوان البانر…">
      <select v-model="banners.query.position" class="input input-sm">
        <option value="">كل المواضع</option>
        <option v-for="o in options(BANNER_POSITION)" :key="o.value" :value="o.value">{{ o.label }}</option>
      </select>
      <select v-model="banners.query.audience" class="input input-sm">
        <option value="">كل الجماهير</option>
        <option v-for="o in options(AUDIENCE)" :key="o.value" :value="o.value">{{ o.label }}</option>
      </select>
      <button v-if="can('banner.create')" class="btn btn-sm" style="margin-right:auto" @click="open()"><Icon name="plus" :size="15" /> بانر جديد</button>
    </div>

    <DataTable
      :columns="columns" :rows="banners.items" :loading="banners.loading"
      :sort="banners.query.sort" :dir="banners.query.dir"
      empty="لا توجد بانرات" empty-icon="image" @sort="banners.sortBy"
    >
      <template #cell-title="{ row }">
        <div style="display:flex;align-items:center;gap:10px">
          <img
            v-if="row.image" :src="row.image" alt=""
            style="width:54px;height:34px;object-fit:cover;border-radius:9px;flex-shrink:0"
          >
          <div v-else style="width:54px;height:34px;border-radius:9px;background:var(--card2);display:flex;
               align-items:center;justify-content:center;font-size:15px;flex-shrink:0"><Icon name="image" /></div>
          <div style="min-width:0">
            <div style="font-weight:700;color:var(--head)">{{ row.title }}</div>
            <div class="muted" style="font-size:12px" dir="ltr">{{ row.link ?? '' }}</div>
          </div>
        </div>
      </template>
      <template #cell-position="{ row }">{{ BANNER_POSITION[row.position] ?? row.position }}</template>
      <template #cell-audience="{ row }">{{ AUDIENCE[row.audience] ?? row.audience }}</template>
      <template #cell-period="{ row }">
        <span class="muted num" style="font-size:13px">
          {{ row.starts_at || row.ends_at ? `${date(row.starts_at)} <Icon name="arrow" /> ${date(row.ends_at)}` : 'دائم' }}
        </span>
      </template>
      <template #cell-sort="{ row }"><span class="num muted">{{ row.sort }}</span></template>
      <template #cell-is_active="{ row }">
        <span class="pill" :class="row.is_active ? 'pill-green' : 'pill-gray'">{{ row.is_active ? 'مفعّل' : 'معطّل' }}</span>
      </template>
      <template #cell-actions="{ row }">
        <div class="row-actions">
          <button v-if="can('banner.update')" class="icon-btn" title="تعديل" @click="open(row)"><Icon name="edit" /></button>
          <button v-if="can('banner.delete')" class="icon-btn danger" title="حذف" @click="remove(row)"><Icon name="trash" /></button>
        </div>
      </template>
    </DataTable>

    <AppPagination :meta="banners.meta" @change="banners.query.page = $event" />

    <AppModal v-model="show" :title="editing ? 'تعديل البانر' : 'بانر جديد'" width="600px">
      <form id="banner-form" class="form-grid" @submit.prevent="submit">
        <FormField label="العنوان *" full><input v-model="form.title" class="input" required></FormField>
        <FormField label="رابط الصورة" full hint="ارفع الصورة على التخزين وضع رابطها هنا">
          <input v-model="form.image" class="input" dir="ltr" placeholder="https://…">
        </FormField>
        <FormField label="رابط الوجهة" full><input v-model="form.link" class="input" dir="ltr" placeholder="https://…"></FormField>
        <FormField label="الموضع">
          <select v-model="form.position" class="input">
            <option v-for="o in options(BANNER_POSITION)" :key="o.value" :value="o.value">{{ o.label }}</option>
          </select>
        </FormField>
        <FormField label="الجمهور">
          <select v-model="form.audience" class="input">
            <option v-for="o in options(AUDIENCE)" :key="o.value" :value="o.value">{{ o.label }}</option>
          </select>
        </FormField>
        <FormField label="يبدأ في"><input v-model="form.starts_at" type="date" class="input"></FormField>
        <FormField label="ينتهي في"><input v-model="form.ends_at" type="date" class="input"></FormField>
        <FormField label="ترتيب العرض"><input v-model.number="form.sort" type="number" min="0" class="input" dir="ltr"></FormField>
        <FormField label="الحالة"><AppSwitch v-model="form.is_active" label="البانر مفعّل" /></FormField>
      </form>

      <template #footer>
        <button type="submit" form="banner-form" class="btn" :disabled="banners.saving">حفظ</button>
        <button class="btn btn-ghost" @click="show = false">إلغاء</button>
      </template>
    </AppModal>
  </div>
</template>
