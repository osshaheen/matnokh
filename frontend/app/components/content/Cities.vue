<script setup lang="ts">
import type { Column } from '~/types/table'

const { can } = useAuth()
const { confirm } = useConfirm()
const { refresh: refreshLookups } = useLookups()

const cities = useResource('/cities', { is_active: '' })

const columns: Column[] = [
  { key: 'name', label: 'المدينة', sortable: true },
  { key: 'delivery_fee', label: 'سعر الكيلومتر', sortable: true },
  { key: 'merchants_count', label: 'التجّار' },
  { key: 'drivers_count', label: 'السائقون' },
  { key: 'orders_count', label: 'الطلبات' },
  { key: 'sort', label: 'الترتيب', sortable: true },
  { key: 'is_active', label: 'مفعّلة' },
  { key: 'actions', label: '', width: '90px' },
]

const show = ref(false)
const editing = ref<any>(null)
const form = reactive({ name: '', name_en: '', delivery_fee: 0, is_active: true, sort: 0 })

function open(row: any = null) {
  editing.value = row
  Object.assign(form, {
    name: row?.name ?? '', name_en: row?.name_en ?? '',
    delivery_fee: row?.delivery_fee ?? 0, is_active: row?.is_active ?? true, sort: row?.sort ?? 0,
  })
  show.value = true
}

async function submit() {
  const res = editing.value ? await cities.update(editing.value.id, { ...form }) : await cities.create({ ...form })
  if (res) { show.value = false; refreshLookups() }
}

async function remove(row: any) {
  if (!await confirm({ title: `حذف مدينة ${row.name}؟`, danger: true, confirmText: 'حذف' })) return
  await cities.remove(row.id)
  refreshLookups()
}
</script>

<template>
  <div>
    <div class="toolbar">
      <input v-model="cities.query.search" class="input input-sm grow" placeholder="بحث باسم المدينة…">
      <select v-model="cities.query.is_active" class="input input-sm">
        <option value="">الكل</option>
        <option value="1">مفعّلة</option>
        <option value="0">معطّلة</option>
      </select>
      <button v-if="can('city.create')" class="btn btn-sm" style="margin-right:auto" @click="open()"><Icon name="plus" :size="15" /> مدينة جديدة</button>
    </div>

    <DataTable
      :columns="columns" :rows="cities.items" :loading="cities.loading"
      :sort="cities.query.sort" :dir="cities.query.dir"
      empty="لا توجد مدن" empty-icon="city" @sort="cities.sortBy"
    >
      <template #cell-name="{ row }">
        <div style="font-weight:700;color:var(--head)">{{ row.name }}</div>
        <div class="muted" style="font-size:12px" dir="ltr">{{ row.name_en ?? '' }}</div>
      </template>
      <template #cell-delivery_fee="{ row }"><span class="num">{{ money(row.delivery_fee) }}</span></template>
      <template #cell-merchants_count="{ row }"><span class="num">{{ num(row.merchants_count ?? 0) }}</span></template>
      <template #cell-drivers_count="{ row }"><span class="num">{{ num(row.drivers_count ?? 0) }}</span></template>
      <template #cell-orders_count="{ row }"><span class="num">{{ num(row.orders_count ?? 0) }}</span></template>
      <template #cell-sort="{ row }"><span class="num muted">{{ row.sort }}</span></template>
      <template #cell-is_active="{ row }">
        <span class="pill" :class="row.is_active ? 'pill-green' : 'pill-gray'">{{ row.is_active ? 'مفعّلة' : 'معطّلة' }}</span>
      </template>
      <template #cell-actions="{ row }">
        <div class="row-actions">
          <button v-if="can('city.update')" class="icon-btn" title="تعديل" @click="open(row)"><Icon name="edit" /></button>
          <button v-if="can('city.delete')" class="icon-btn danger" title="حذف" @click="remove(row)"><Icon name="trash" /></button>
        </div>
      </template>
    </DataTable>

    <AppPagination :meta="cities.meta" @change="cities.query.page = $event" />

    <AppModal v-model="show" :title="editing ? 'تعديل المدينة' : 'مدينة جديدة'">
      <form id="city-form" class="form-grid" @submit.prevent="submit">
        <FormField label="الاسم بالعربية *"><input v-model="form.name" class="input" required></FormField>
        <FormField label="الاسم بالإنجليزية"><input v-model="form.name_en" class="input" dir="ltr"></FormField>
        <FormField label="سعر التوصيل لكل كيلومتر" hint="التكلفة لكل 1 كم من نقطة الاستلام إلى الوجهة"><input v-model.number="form.delivery_fee" type="number" min="0" step="0.5" class="input" dir="ltr"></FormField>
        <FormField label="ترتيب العرض"><input v-model.number="form.sort" type="number" min="0" class="input" dir="ltr"></FormField>
        <FormField label="الحالة" full><AppSwitch v-model="form.is_active" label="المدينة مفعّلة" /></FormField>
      </form>

      <template #footer>
        <button type="submit" form="city-form" class="btn" :disabled="cities.saving">حفظ</button>
        <button class="btn btn-ghost" @click="show = false">إلغاء</button>
      </template>
    </AppModal>
  </div>
</template>
