<script setup lang="ts">
import type { Column } from '~/types/table'

const { can } = useAuth()
const { confirm } = useConfirm()
const { refresh: refreshLookups } = useLookups()

const categories = useResource('/store-categories', { is_active: '' })

const columns: Column[] = [
  { key: 'name', label: 'التصنيف', sortable: true },
  { key: 'merchants_count', label: 'عدد المتاجر' },
  { key: 'sort', label: 'الترتيب', sortable: true },
  { key: 'is_active', label: 'مفعّل' },
  { key: 'actions', label: '', width: '90px' },
]

const show = ref(false)
const editing = ref<any>(null)
const form = reactive({ name: '', name_en: '', icon: '', is_active: true, sort: 0 })

function open(row: any = null) {
  editing.value = row
  Object.assign(form, {
    name: row?.name ?? '', name_en: row?.name_en ?? '', icon: row?.icon ?? '',
    is_active: row?.is_active ?? true, sort: row?.sort ?? 0,
  })
  show.value = true
}

async function submit() {
  const res = editing.value ? await categories.update(editing.value.id, { ...form }) : await categories.create({ ...form })
  if (res) { show.value = false; refreshLookups() }
}

async function remove(row: any) {
  if (!await confirm({ title: `حذف تصنيف ${row.name}؟`, danger: true, confirmText: 'حذف' })) return
  await categories.remove(row.id)
  refreshLookups()
}
</script>

<template>
  <div>
    <div class="toolbar">
      <input v-model="categories.query.search" class="input input-sm grow" placeholder="🔍 بحث باسم التصنيف…">
      <select v-model="categories.query.is_active" class="input input-sm">
        <option value="">الكل</option>
        <option value="1">مفعّل</option>
        <option value="0">معطّل</option>
      </select>
      <button v-if="can('store_category.create')" class="btn btn-sm" style="margin-right:auto" @click="open()">＋ تصنيف جديد</button>
    </div>

    <DataTable
      :columns="columns" :rows="categories.items" :loading="categories.loading"
      :sort="categories.query.sort" :dir="categories.query.dir"
      empty="لا توجد تصنيفات" empty-icon="🏷️" @sort="categories.sortBy"
    >
      <template #cell-name="{ row }">
        <div style="display:flex;align-items:center;gap:10px">
          <div style="width:34px;height:34px;border-radius:11px;background:var(--card2);display:flex;
               align-items:center;justify-content:center;font-size:18px">{{ row.icon || '🏷️' }}</div>
          <div>
            <div style="font-weight:700;color:var(--head)">{{ row.name }}</div>
            <div class="muted" style="font-size:12px" dir="ltr">{{ row.name_en ?? '' }}</div>
          </div>
        </div>
      </template>
      <template #cell-merchants_count="{ row }"><span class="num">{{ num(row.merchants_count ?? 0) }}</span></template>
      <template #cell-sort="{ row }"><span class="num muted">{{ row.sort }}</span></template>
      <template #cell-is_active="{ row }">
        <span class="pill" :class="row.is_active ? 'pill-green' : 'pill-gray'">{{ row.is_active ? 'مفعّل' : 'معطّل' }}</span>
      </template>
      <template #cell-actions="{ row }">
        <div class="row-actions">
          <button v-if="can('store_category.update')" class="icon-btn" title="تعديل" @click="open(row)">✏️</button>
          <button v-if="can('store_category.delete')" class="icon-btn danger" title="حذف" @click="remove(row)">🗑</button>
        </div>
      </template>
    </DataTable>

    <AppPagination :meta="categories.meta" @change="categories.query.page = $event" />

    <AppModal v-model="show" :title="editing ? 'تعديل التصنيف' : 'تصنيف جديد'">
      <form id="category-form" class="form-grid" @submit.prevent="submit">
        <FormField label="الاسم بالعربية *"><input v-model="form.name" class="input" required></FormField>
        <FormField label="الاسم بالإنجليزية"><input v-model="form.name_en" class="input" dir="ltr"></FormField>
        <FormField label="الأيقونة" hint="رمز تعبيري مثل 🍽️"><input v-model="form.icon" class="input"></FormField>
        <FormField label="ترتيب العرض"><input v-model.number="form.sort" type="number" min="0" class="input" dir="ltr"></FormField>
        <FormField label="الحالة" full><AppSwitch v-model="form.is_active" label="التصنيف مفعّل" /></FormField>
      </form>

      <template #footer>
        <button type="submit" form="category-form" class="btn" :disabled="categories.saving">حفظ</button>
        <button class="btn btn-ghost" @click="show = false">إلغاء</button>
      </template>
    </AppModal>
  </div>
</template>
