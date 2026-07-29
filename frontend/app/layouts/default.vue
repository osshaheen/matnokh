<script setup lang="ts">
const { user, logout, can } = useAuth()
const route = useRoute()

const nav = [
  { to: '/', icon: 'chart', label: 'الرئيسية', perm: 'dashboard.view' },
  { to: '/orders', icon: 'box', label: 'الطلبات', perm: 'order.view' },
  { to: '/drivers', icon: 'car', label: 'السائقون', perm: 'driver.view' },
  { to: '/merchants', icon: 'store', label: 'التجّار', perm: 'merchant.view' },
  { to: '/customers', icon: 'users', label: 'الزبائن', perm: 'customer.view' },
  { to: '/withdraws', icon: 'cash', label: 'السحوبات', perm: 'withdraw.view' },
  { to: '/subscriptions', icon: 'star', label: 'الاشتراكات', perm: 'subscription.view' },
  { to: '/content', icon: 'folder', label: 'المحتوى', perm: 'city.view' },
  { to: '/notifications', icon: 'bell', label: 'الإشعارات', perm: 'notification.view' },
  { to: '/trash', icon: 'trash', label: 'سلّة المحذوفات', perm: 'trash.view' },
  { to: '/settings', icon: 'gear', label: 'الإعدادات', perm: 'settings.view' },
]

const items = computed(() => nav.filter(n => can(n.perm)))
const current = computed(() => items.value.find(n => isActive(n.to)))

function isActive(path: string) {
  return path === '/' ? route.path === '/' : route.path === path || route.path.startsWith(path + '/')
}

// the sidebar is a slide-over below 900px; navigating closes it
const open = ref(false)
watch(() => route.fullPath, () => { open.value = false })
</script>

<template>
  <div class="shell">
    <div v-if="open" class="backdrop" @click="open = false" />

    <aside class="sidebar" :class="{ open }">
      <div style="padding:22px 20px;display:flex;align-items:center;gap:11px;border-bottom:1px solid var(--line)">
        <div style="width:42px;height:42px;border-radius:13px;background:var(--grad-green);display:flex;
             align-items:center;justify-content:center;color:#fff;font-size:21px"><Icon name="truck" /></div>
        <div>
          <div style="font-weight:800;color:var(--head);font-size:18px">مطنوخ</div>
          <div class="muted" style="font-size:11px">لوحة التحكم</div>
        </div>
      </div>

      <nav style="flex:1;padding:14px 12px;display:flex;flex-direction:column;gap:4px;overflow-y:auto">
        <NuxtLink
          v-for="n in items" :key="n.to" :to="n.to"
          class="sidebar-link" :class="{ active: isActive(n.to) }"
        >
          <Icon :name="n.icon" :size="19" />{{ n.label }}
        </NuxtLink>
      </nav>

      <div style="padding:14px;border-top:1px solid var(--line)">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
          <div style="width:36px;height:36px;border-radius:11px;background:var(--sage);display:flex;
               align-items:center;justify-content:center;color:#fff;font-weight:800">{{ (user?.name || 'م')[0] }}</div>
          <div style="min-width:0">
            <div style="font-weight:700;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ user?.name }}</div>
            <div class="muted" style="font-size:11px">{{ (user?.roles || []).join('، ') }}</div>
          </div>
        </div>
        <button class="btn btn-ghost" style="width:100%;padding:9px" @click="logout">تسجيل الخروج</button>
      </div>
    </aside>

    <main class="main">
      <div class="topbar">
        <button class="icon-btn" style="width:40px;height:40px;font-size:18px" aria-label="القائمة" @click="open = true"><Icon name="menu" /></button>
        <div style="font-weight:800;color:var(--head)">{{ current?.label || 'مطنوخ' }}</div>
        <div style="width:40px;height:40px;border-radius:12px;background:var(--sage);display:flex;
             align-items:center;justify-content:center;color:#fff;font-weight:800">{{ (user?.name || 'م')[0] }}</div>
      </div>

      <slot />
    </main>

    <ToastHost />
    <ConfirmHost />
  </div>
</template>
