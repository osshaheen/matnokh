<script setup lang="ts">
definePageMeta({ layout: 'auth', middleware: 'auth' })
const { login } = useAuth()
const email = ref('admin@wassilha.ps')
const password = ref('')
const loading = ref(false)
const error = ref('')

async function submit() {
  loading.value = true; error.value = ''
  try { await login(email.value, password.value); await navigateTo('/') }
  catch (e: any) { error.value = e?.data?.message || 'تعذّر تسجيل الدخول' }
  finally { loading.value = false }
}
</script>

<template>
  <div class="card" style="width:100%;max-width:400px;padding:34px 30px">
    <div style="text-align:center;margin-bottom:26px">
      <div style="width:64px;height:64px;border-radius:20px;background:var(--grad-green);display:flex;
           align-items:center;justify-content:center;margin:0 auto 14px;color:#fff;font-size:30px">🚚</div>
      <h1 style="font-size:24px;font-weight:800;color:var(--head)">وصلها</h1>
      <p class="muted" style="font-size:14px">لوحة التحكم — إدارة المنصّة</p>
    </div>

    <div v-if="error" style="background:#f7e2da;color:#a5623f;padding:11px 14px;border-radius:12px;
         font-size:13px;font-weight:700;margin-bottom:14px;text-align:center">⚠️ {{ error }}</div>

    <form @submit.prevent="submit" style="display:flex;flex-direction:column;gap:14px">
      <div>
        <label class="muted" style="font-size:13px;font-weight:700;display:block;margin-bottom:6px">البريد الإلكتروني</label>
        <input v-model="email" type="email" class="input" placeholder="admin@wassilha.ps" dir="ltr" required />
      </div>
      <div>
        <label class="muted" style="font-size:13px;font-weight:700;display:block;margin-bottom:6px">كلمة المرور</label>
        <input v-model="password" type="password" class="input" placeholder="••••••••" dir="ltr" required />
      </div>
      <button class="btn" :disabled="loading" style="padding:14px;font-size:16px;margin-top:6px">
        {{ loading ? 'جارٍ الدخول…' : 'تسجيل الدخول' }}
      </button>
    </form>
  </div>
</template>
