<script setup lang="ts">
const props = withDefaults(defineProps<{
  modelValue: boolean
  title: string
  subtitle?: string
  width?: string
}>(), { width: '560px' })

const emit = defineEmits<{ (e: 'update:modelValue', v: boolean): void }>()

const close = () => emit('update:modelValue', false)

function onKey(e: KeyboardEvent) {
  if (e.key === 'Escape' && props.modelValue) close()
}

// the page behind a modal must not scroll away under it
watch(() => props.modelValue, (open) => {
  if (import.meta.client) document.body.style.overflow = open ? 'hidden' : ''
})

onMounted(() => window.addEventListener('keydown', onKey))
onUnmounted(() => {
  window.removeEventListener('keydown', onKey)
  if (import.meta.client) document.body.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="modelValue" class="modal-backdrop" @click.self="close">
        <div class="modal" :style="{ maxWidth: width }">
          <div class="modal-head">
            <div style="min-width:0">
              <div style="font-weight:800;color:var(--head);font-size:17px">{{ title }}</div>
              <div v-if="subtitle" class="muted" style="font-size:13px">{{ subtitle }}</div>
            </div>
            <button class="icon-btn" aria-label="إغلاق" @click="close">✕</button>
          </div>

          <div class="modal-body"><slot /></div>

          <div v-if="$slots.footer" class="modal-foot"><slot name="footer" /></div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
