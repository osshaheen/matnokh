<script setup lang="ts">
const { user, logout, can } = useAuth()
const route = useRoute()
const nav = [
  { to: '/', icon: '📊', label: 'الرئيسية', perm: 'dashboard.view' },
  { to: '/orders', icon: '📦', label: 'الطلبات', perm: 'order.view' },
  { to: '/drivers', icon: '🚗', label: 'السائقون', perm: 'driver.view' },
  { to: '/merchants', icon: '🏪', label: 'التجّار', perm: 'merchant.view' },
  { to: '/customers', icon: '👥', label: 'الزبائن', perm: 'customer.view' },
  { to: '/withdraws', icon: '💵', label: 'السحوبات', perm: 'withdraw.view' },
  { to: '/subscriptions', icon: '⭐', label: 'الاشتراكات', perm: 'subscription.view' },
  { to: '/content', icon: '🗂️', label: 'المحتوى', perm: 'city.view' },
  { to: '/settings', icon: '⚙️', label: 'الإعدادات', perm: 'settings.view' },
]
const items = computed(() => nav.filter(n => can(n.perm)))
</script>

<template>
  <div style="min-height:100vh;display:flex">
    <!-- sidebar -->
    <aside style="width:236px;background:var(--card);border-left:1px solid var(--line);
           display:flex;flex-direction:column;position:sticky;top:0;height:100vh;flex-shrink:0">
      <div style="padding:22px 20px;display:flex;align-items:center;gap:11px;border-bottom:1px solid var(--line)">
        <div style="width:42px;height:42px;border-radius:13px;background:var(--grad-green);display:flex;
             align-items:center;justify-content:center;color:#fff;font-size:21px">🚚</div>
        <div><div style="font-weight:800;color:var(--head);font-size:18px">وصلها</div>
          <div class="muted" style="font-size:11px">لوحة التحكم</div></div>
      </div>
      <nav style="flex:1;padding:14px 12px;display:flex;flex-direction:column;gap:4px;overflow-y:auto">
        <NuxtLink v-for="n in items" :key="n.to" :to="n.to"
          :style="{display:'flex',alignItems:'center',gap:'11px',padding:'11px 14px',borderRadius:'13px',
                   fontWeight:'700',fontSize:'14px',transition:'.15s',
                   background: route.path===n.to ? 'var(--grad-green)' : 'transparent',
                   color: route.path===n.to ? '#fff' : 'var(--head)'}">
          <span style="font-size:18px">{{ n.icon }}</span>{{ n.label }}
        </NuxtLink>
      </nav>
      <div style="padding:14px;border-top:1px solid var(--line)">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
          <div style="width:36px;height:36px;border-radius:11px;background:var(--sage);display:flex;
               align-items:center;justify-content:center;color:#fff;font-weight:800">{{ (user?.name||'م')[0] }}</div>
          <div style="min-width:0"><div style="font-weight:700;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ user?.name }}</div>
            <div class="muted" style="font-size:11px">{{ (user?.roles||[]).join(', ') }}</div></div>
        </div>
        <button class="btn-ghost btn" style="width:100%;padding:9px" @click="logout">تسجيل الخروج</button>
      </div>
    </aside>

    <!-- content -->
    <main style="flex:1;padding:26px 30px;max-width:1200px;margin:0 auto;width:100%">
      <slot />
    </main>
  </div>
</template>
