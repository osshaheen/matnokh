<script setup lang="ts">
definePageMeta({ middleware: 'auth' })
const { user } = useAuth()
// placeholder KPIs — wired to real endpoints as modules are built
const stats = [
  { label: 'الطلبات اليوم', value: '—', icon: '📦', grad: 'var(--grad-green)' },
  { label: 'السائقون المتاحون', value: '—', icon: '🚗', grad: 'var(--grad-blue)' },
  { label: 'التجّار النشطون', value: '—', icon: '🏪', grad: 'var(--grad-terra)' },
  { label: 'إيراد اليوم', value: '—', icon: '💰', grad: 'var(--grad-sand)' },
]
</script>

<template>
  <div>
    <div style="margin-bottom:24px">
      <h1 style="font-size:26px;font-weight:800;color:var(--head)">مرحباً، {{ user?.name }} 👋</h1>
      <p class="muted">نظرة عامة على منصّة وصلها</p>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;margin-bottom:26px">
      <div v-for="(s,i) in stats" :key="i" class="card" style="padding:22px">
        <div :style="{width:'46px',height:'46px',borderRadius:'14px',background:s.grad,display:'flex',
             alignItems:'center',justifyContent:'center',fontSize:'23px',marginBottom:'14px'}">{{ s.icon }}</div>
        <div style="font-size:28px;font-weight:800;color:var(--head)">{{ s.value }}</div>
        <div class="muted" style="font-size:13px;margin-top:2px">{{ s.label }}</div>
      </div>
    </div>

    <div class="card" style="padding:40px;text-align:center">
      <div style="font-size:44px;margin-bottom:10px">🌿</div>
      <div style="font-weight:800;color:var(--head);font-size:18px;margin-bottom:6px">لوحة التحكم جاهزة</div>
      <p class="muted" style="max-width:440px;margin:0 auto">تسجيل الدخول والصلاحيات تعمل. الوحدات (الطلبات، السائقون، التجّار…) تُبنى تِباعاً وتظهر في القائمة الجانبية.</p>
    </div>
  </div>
</template>
