<script setup lang="ts">
import type { Column } from '~/types/table'

const props = withDefaults(defineProps<{
  columns: Column[]
  rows: any[]
  loading?: boolean
  sort?: string
  dir?: string
  empty?: string
  emptyIcon?: string
  clickable?: boolean
}>(), { empty: 'لا توجد نتائج مطابقة', emptyIcon: 'folder' })

const emit = defineEmits<{ (e: 'sort', key: string): void; (e: 'row', row: any): void }>()

const align = (col: Column) => ({ start: 'right', center: 'center', end: 'left' }[col.align ?? 'start'])
</script>

<template>
  <div class="card">
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th
              v-for="col in columns" :key="col.key"
              :class="{ sortable: col.sortable }"
              :style="{ width: col.width, textAlign: align(col) }"
              @click="col.sortable && emit('sort', col.key)"
            >
              {{ col.label }}
              <span v-if="col.sortable && sort === col.key">{{ dir === 'asc' ? '▲' : '▼' }}</span>
            </th>
          </tr>
        </thead>

        <tbody v-if="loading && !rows.length">
          <tr v-for="i in 5" :key="i">
            <td v-for="col in columns" :key="col.key">
              <div class="skeleton" style="height:15px" />
            </td>
          </tr>
        </tbody>

        <tbody v-else-if="rows.length">
          <tr
            v-for="(row, i) in rows" :key="row.id ?? i"
            :style="clickable ? 'cursor:pointer' : ''"
            @click="clickable && emit('row', row)"
          >
            <td v-for="col in columns" :key="col.key" :style="{ textAlign: align(col) }">
              <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]" :index="i">
                {{ row[col.key] ?? '—' }}
              </slot>
            </td>
          </tr>
        </tbody>

        <tbody v-else>
          <tr>
            <td :colspan="columns.length" style="padding:0">
              <EmptyState :icon="emptyIcon" :text="empty">
                <slot name="empty-action" />
              </EmptyState>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="loading && rows.length" style="height:3px;background:var(--grad-green);opacity:.6;border-radius:0 0 var(--r) var(--r)" />
  </div>
</template>
