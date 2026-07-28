<script setup lang="ts">
const { state, answer } = useConfirm()
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="state.open" class="modal-backdrop" @click.self="answer(false)">
        <div class="modal" style="max-width:420px">
          <div class="modal-body" style="text-align:center;padding-top:28px">
            <div style="font-size:38px;margin-bottom:8px">{{ state.options.danger ? '⚠️' : '❓' }}</div>
            <div style="font-weight:800;color:var(--head);font-size:17px">{{ state.options.title }}</div>
            <p v-if="state.options.text" class="muted" style="font-size:14px;margin-top:6px">{{ state.options.text }}</p>
          </div>
          <div class="modal-foot" style="justify-content:stretch">
            <button class="btn" :class="state.options.danger ? 'btn-danger' : ''" style="flex:1" @click="answer(true)">
              {{ state.options.confirmText || 'تأكيد' }}
            </button>
            <button class="btn btn-ghost" style="flex:1" @click="answer(false)">إلغاء</button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
