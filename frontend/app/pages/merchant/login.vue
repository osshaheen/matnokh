<script setup lang="ts">
definePageMeta({ layout: false, middleware: 'merchant-auth' })
const m = useMerchant()

const method = ref('phone_password')
const phone = ref('')
const email = ref('')
const password = ref('')
const code = ref('')
const otpSent = ref(false)
const devCode = ref('')
const loading = ref(false)
const error = ref('')

onMounted(async () => { method.value = await m.loginMethod() })

async function sendOtp() {
  loading.value = true; error.value = ''
  try { const r = await m.requestOtp(phone.value); otpSent.value = true; devCode.value = r.dev_code || '' }
  catch (e: any) { error.value = apiError(e) } finally { loading.value = false }
}

async function submit() {
  loading.value = true; error.value = ''
  try {
    const body: any = method.value === 'email_password'
      ? { email: email.value, password: password.value }
      : method.value === 'phone_otp'
        ? { phone: phone.value, code: code.value }
        : { phone: phone.value, password: password.value }
    await m.login(body)
    await navigateTo('/merchant')
  } catch (e: any) { error.value = apiError(e, 'تعذّر تسجيل الدخول') }
  finally { loading.value = false }
}
</script>

<template>
  <div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;
       background:radial-gradient(1200px 500px at 50% -10%, #e8efe8, var(--body))">
    <div class="m-card" style="width:100%;max-width:400px;padding:32px 26px">
      <div style="text-align:center;margin-bottom:24px">
        <div style="width:64px;height:64px;border-radius:20px;background:var(--grad-green);display:flex;
             align-items:center;justify-content:center;margin:0 auto 12px;color:#fff"><Icon name="store" :size="30" /></div>
        <h1 class="m-h1">مطنوخ — تاجر</h1>
        <p class="muted" style="font-size:13px">ادخل لمتجرك</p>
      </div>

      <div v-if="error" style="background:#f7e2da;color:#a5623f;padding:11px 14px;border-radius:12px;
           font-size:13px;font-weight:700;margin-bottom:14px;text-align:center">{{ error }}</div>

      <form @submit.prevent="method === 'phone_otp' && !otpSent ? sendOtp() : submit()" style="display:flex;flex-direction:column;gap:14px">
        <template v-if="method === 'email_password'">
          <input v-model="email" type="email" class="input" placeholder="البريد الإلكتروني" dir="ltr" required>
          <input v-model="password" type="password" class="input" placeholder="كلمة المرور" dir="ltr" required>
        </template>

        <template v-else-if="method === 'phone_otp'">
          <input v-model="phone" class="input" placeholder="رقم الهاتف" dir="ltr" :disabled="otpSent" required>
          <template v-if="otpSent">
            <input v-model="code" class="input" placeholder="رمز التحقق" dir="ltr" required>
            <p v-if="devCode" class="muted" style="font-size:12px;text-align:center">رمز التجربة: {{ devCode }}</p>
          </template>
        </template>

        <template v-else>
          <input v-model="phone" class="input" placeholder="رقم الهاتف" dir="ltr" required>
          <input v-model="password" type="password" class="input" placeholder="كلمة المرور" dir="ltr" required>
        </template>

        <button class="btn" :disabled="loading" style="padding:14px;font-size:16px;margin-top:4px">
          {{ loading ? '...' : (method === 'phone_otp' && !otpSent ? 'إرسال الرمز' : 'دخول') }}
        </button>
      </form>
    </div>
  </div>
</template>
