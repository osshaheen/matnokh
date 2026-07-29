<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const { can } = useAuth()

const tabs = [
  { key: 'cities', label: 'المدن', perm: 'city.view' },
  { key: 'categories', label: 'تصنيفات المتاجر', perm: 'store_category.view' },
  { key: 'banners', label: 'البانرات', perm: 'banner.view' },
  { key: 'articles', label: 'المقالات', perm: 'article.view' },
]

const visible = computed(() => tabs.filter(t => can(t.perm)))
const tab = ref(visible.value[0]?.key ?? 'cities')
</script>

<template>
  <div>
    <PageHeader title="المحتوى" subtitle="المدن والتصنيفات والخدمات والمحتوى التسويقي" />

    <div class="tabs">
      <button
        v-for="t in visible" :key="t.key"
        class="tab" :class="{ active: tab === t.key }"
        @click="tab = t.key"
      >{{ t.label }}</button>
    </div>

    <ContentCities v-if="tab === 'cities'" />
    <ContentCategories v-else-if="tab === 'categories'" />
    <ContentBanners v-else-if="tab === 'banners'" />
    <ContentArticles v-else-if="tab === 'articles'" />
    <EmptyState v-else icon="lock" title="لا تملك صلاحية عرض هذا القسم" />
  </div>
</template>
