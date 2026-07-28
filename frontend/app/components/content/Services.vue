<script setup lang="ts">
import type { Column } from '~/types/table'

const { can } = useAuth()
const { confirm } = useConfirm()
const { refresh: refreshLookups } = useLookups()

const services = useResource('/services', { is_active: '' })

const columns: Column[] = [
  { key: 'name', label: 'الخدمة', sortable: true },
  { key: 'base_price', label: 'السعر الأساسي', sortable: true },
  { key: 'orders_count', label: 'الطلبات' },
  { key: 'sort', label: 'الترتيب', sortable: true },
  { key: 'is_active', label: 'مفعّلة' },
  { key: 'actions', label: '', width: '90px' },
]

const show = ref(false)
const editing = ref<any>(null)
const form = reactive({ name: '', description: '', icon: '', base_price: 0, is_active: true, sort: 0 })

function open(row: any = null) {
  editing.value = row
  Object.assign(form, {
    name: row?.name ?? '', description: row?.description ?? '', icon: row?.icon ?? '',
    base_price: row?.base_price ?? 0, is_active: row?.is_active ?? true, sort: row?.sort ?? 0,
  })
  show.value = true
}

async function submit() {
  const res = editing.value ? await services.update(editing.value.id, { ...form }) : await services.create({ ...form })
  if (res) { show.value = false; refreshLookups() }
}

async function remove(row: any) {
  if (!await confirm({ title: `حذف خدمة ${row.name}؟`, danger: true, confirmText: 'حذف' })) return
  await services.remove(row.id)
  refreshLookups()
}
</script>

<template>
  <div>
    <div class="toolbar">
      <input v-model="services.query.search" class="input input-sm grow" placeholder="🔍 بحث باسم الخدمة…">
      <select v-model="services.query.is_active" class="input input-sm">
        <option value="">الكل</option>
        <option value="1">مفعّلة</option>
        <option value="0">معطّلة</option>
      </select>
      <button v-if="can('service.create')" class="btn btn-sm" style="margin-right:auto" @click="open()">＋ خدمة جديدة</button>
    </div>

    <DataTable
      :columns="columns" :rows="services.items" :loading="services.loading"
      :sort="services.query.sort" :dir="services.query.dir"
      empty="لا توجد خدمات" empty-icon="🧰" @sort="services.sortBy"
    >
      <template #cell-name="{ row }">
        <div style="display:flex;align-items:center;gap:10px">
          <div style="width:34px;height:34px;border-radius:11px;background:var(--card2);display:flex;
               align-items:center;justify-content:center;font-size:18px">{{ row.icon || '🧰' }}</div>
          <div style="min-width:0">
            <div style="font-weight:700;color:var(--head)">{{ row.name }}</div>
            <div class="muted" style="font-size:12px">{{ row.description ?? '' }}</div>
          </div>
        </div>
      </template>
      <template #cell-base_price="{ row }"><span class="num">{{ money(row.base_price) }}</span></template>
      <template #cell-orders_count="{ row }"><span class="num">{{ num(row.orders_count ?? 0) }}</span></template>
      <template #cell-sort="{ row }"><span class="num muted">{{ row.sort }}</span></template>
      <template #cell-is_active="{ row }">
        <span class="pill" :class="row.is_active ? 'pill-green' : 'pill-gray'">{{ row.is_active ? 'مفعّلة' : 'معطّلة' }}</span>
      </template>
      <template #cell-actions="{ row }">
        <div class="row-actions">
          <button v-if="can('service.update')" class="icon-btn" title="تعديل" @click="open(row)">✏️</button>
          <button v-if="can('service.delete')" class="icon-btn danger" title="حذف" @click="remove(row)">🗑</button>
        </div>
      </template>
    </DataTable>

    <AppPagination :meta="services.meta" @change="services.query.page = $event" />

    <AppModal v-model="show" :title="editing ? 'تعديل الخدمة' : 'خدمة جديدة'">
      <form id="service-form" class="form-grid" @submit.prevent="submit">
        <FormField label="اسم الخدمة *"><input v-model="form.name" class="input" required></FormField>
        <FormField label="السعر الأساسي"><input v-model.number="form.base_price" type="number" min="0" step="0.5" class="input" dir="ltr"></FormField>
        <FormField label="الأيقونة" hint="رمز تعبيري مثل 📦"><input v-model="form.icon" class="input"></FormField>
        <FormField label="ترتيب العرض"><input v-model.number="form.sort" type="number" min="0" class="input" dir="ltr"></FormField>
        <FormField label="الوصف" full><textarea v-model="form.description" class="input" /></FormField>
        <FormField label="الحالة" full><AppSwitch v-model="form.is_active" label="الخدمة مفعّلة" /></FormField>
      </form>

      <template #footer>
        <button type="submit" form="service-form" class="btn" :disabled="services.saving">حفظ</button>
        <button class="btn btn-ghost" @click="show = false">إلغاء</button>
      </template>
    </AppModal>
  </div>
</template>
