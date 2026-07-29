<script setup lang="ts">
// Upload an image straight to the server (no external hosting) and bind its URL.
// <ImageUpload v-model="form.image" />
const props = defineProps<{ modelValue: string | null; label?: string }>()
const emit = defineEmits<{ 'update:modelValue': [string] }>()

const { api, headers } = useAuth()
const toast = useToast()
const uploading = ref(false)
const input = ref<HTMLInputElement | null>(null)

async function pick(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  const fd = new FormData()
  fd.append('file', file)
  uploading.value = true
  try {
    const res = await $fetch<{ url: string }>(api('/uploads'), { method: 'POST', body: fd, headers: headers() })
    emit('update:modelValue', res.url)
  } catch (err) { toast.error(apiError(err)) } finally {
    uploading.value = false
    if (input.value) input.value.value = ''
  }
}
function clear() { emit('update:modelValue', '') }
</script>

<template>
  <div>
    <div style="display:flex;align-items:center;gap:12px">
      <div
        style="width:64px;height:64px;border-radius:14px;border:1px solid var(--line);background:var(--card2);
               display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;color:var(--muted)"
      >
        <img v-if="modelValue" :src="modelValue" alt="" style="width:100%;height:100%;object-fit:cover">
        <Icon v-else name="image" :size="24" />
      </div>
      <div style="display:flex;flex-direction:column;gap:6px">
        <button type="button" class="btn btn-ghost btn-sm" :disabled="uploading" @click="input?.click()">
          <Icon name="image" :size="14" /> {{ uploading ? 'جارٍ الرفع…' : (modelValue ? 'تغيير الصورة' : 'رفع صورة') }}
        </button>
        <button v-if="modelValue" type="button" class="btn btn-ghost btn-sm" style="color:var(--terra)" @click="clear">إزالة</button>
      </div>
      <input ref="input" type="file" accept="image/*" style="display:none" @change="pick">
    </div>
  </div>
</template>
