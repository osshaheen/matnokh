<script setup lang="ts">
import type { Column } from '~/types/table'

const { can } = useAuth()
const api = useApi()
const toast = useToast()
const { confirm } = useConfirm()

const articles = useResource('/articles', { is_published: '' })

const columns: Column[] = [
  { key: 'title', label: 'المقال', sortable: true },
  { key: 'views', label: 'المشاهدات', sortable: true },
  { key: 'published_at', label: 'النشر', sortable: true },
  { key: 'is_published', label: 'الحالة' },
  { key: 'actions', label: '', width: '90px' },
]

const show = ref(false)
const editing = ref<any>(null)
const loadingBody = ref(false)
const form = reactive({ title: '', excerpt: '', body: '', cover: '', is_published: false })

async function open(row: any = null) {
  editing.value = row
  Object.assign(form, {
    title: row?.title ?? '', excerpt: row?.excerpt ?? '', body: row?.body ?? '',
    cover: row?.cover ?? '', is_published: row?.is_published ?? false,
  })
  show.value = true

  // list responses omit the body — fetch it only when actually editing
  if (row && !row.body) {
    loadingBody.value = true
    try {
      const res = await api.get<{ data: any }>(`/articles/${row.id}`)
      form.body = res.data.body ?? ''
    } catch (e) {
      toast.error(apiError(e))
    } finally {
      loadingBody.value = false
    }
  }
}

async function submit() {
  const res = editing.value ? await articles.update(editing.value.id, { ...form }) : await articles.create({ ...form })
  if (res) show.value = false
}

async function togglePublish(row: any) {
  await articles.update(row.id, { is_published: !row.is_published }, row.is_published ? 'تم إخفاء المقال' : 'تم نشر المقال')
}

async function remove(row: any) {
  if (!await confirm({ title: `حذف المقال «${row.title}»؟`, danger: true, confirmText: 'حذف' })) return
  await articles.remove(row.id)
}
</script>

<template>
  <div>
    <div class="toolbar">
      <input v-model="articles.query.search" class="input input-sm grow" placeholder="🔍 بحث بعنوان المقال…">
      <select v-model="articles.query.is_published" class="input input-sm">
        <option value="">الكل</option>
        <option value="1">منشور</option>
        <option value="0">مسوّدة</option>
      </select>
      <button v-if="can('article.create')" class="btn btn-sm" style="margin-right:auto" @click="open()">＋ مقال جديد</button>
    </div>

    <DataTable
      :columns="columns" :rows="articles.items" :loading="articles.loading"
      :sort="articles.query.sort" :dir="articles.query.dir"
      empty="لا توجد مقالات" empty-icon="📄" @sort="articles.sortBy"
    >
      <template #cell-title="{ row }">
        <div style="font-weight:700;color:var(--head)">{{ row.title }}</div>
        <div class="muted" style="font-size:12px;max-width:420px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
          {{ row.excerpt ?? '' }}
        </div>
      </template>
      <template #cell-views="{ row }"><span class="num">{{ num(row.views) }}</span></template>
      <template #cell-published_at="{ row }"><span class="muted num" style="font-size:13px">{{ date(row.published_at) }}</span></template>
      <template #cell-is_published="{ row }">
        <button
          class="pill" :class="row.is_published ? 'pill-green' : 'pill-sand'"
          :disabled="!can('article.update')" @click="togglePublish(row)"
        >{{ row.is_published ? 'منشور' : 'مسوّدة' }}</button>
      </template>
      <template #cell-actions="{ row }">
        <div class="row-actions">
          <button v-if="can('article.update')" class="icon-btn" title="تعديل" @click="open(row)">✏️</button>
          <button v-if="can('article.delete')" class="icon-btn danger" title="حذف" @click="remove(row)">🗑</button>
        </div>
      </template>
    </DataTable>

    <AppPagination :meta="articles.meta" @change="articles.query.page = $event" />

    <AppModal v-model="show" :title="editing ? 'تعديل المقال' : 'مقال جديد'" width="720px">
      <form id="article-form" class="form-grid" @submit.prevent="submit">
        <FormField label="العنوان *" full><input v-model="form.title" class="input" required></FormField>
        <FormField label="مقتطف" full hint="يظهر في قائمة المقالات"><input v-model="form.excerpt" class="input"></FormField>
        <FormField label="رابط صورة الغلاف" full><input v-model="form.cover" class="input" dir="ltr" placeholder="https://…"></FormField>
        <FormField label="المحتوى" full>
          <div v-if="loadingBody" class="skeleton" style="height:200px" />
          <textarea v-else v-model="form.body" class="input" style="min-height:220px" />
        </FormField>
        <FormField label="الحالة" full><AppSwitch v-model="form.is_published" label="منشور" /></FormField>
      </form>

      <template #footer>
        <button type="submit" form="article-form" class="btn" :disabled="articles.saving">حفظ</button>
        <button class="btn btn-ghost" @click="show = false">إلغاء</button>
      </template>
    </AppModal>
  </div>
</template>
