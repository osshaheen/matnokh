<script setup lang="ts">
const { toasts, dismiss } = useToast()
</script>

<template>
  <Teleport to="body">
    <div class="toast-host">
      <TransitionGroup name="fade">
        <div v-for="t in toasts" :key="t.id" class="toast" :class="t.kind" @click="dismiss(t.id)">
          {{ t.kind === 'ok' ? '✓' : '⚠️' }} {{ t.text }}
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>
