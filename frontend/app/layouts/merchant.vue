<script setup lang="ts">
const route = useRoute()
const nav = [
  { to: '/merchant', icon: 'dashboard', label: 'الرئيسية' },
  { to: '/merchant/products', icon: 'box', label: 'المنتجات' },
  { to: '/merchant/orders', icon: 'clipboard', label: 'الطلبات' },
  { to: '/merchant/wallet', icon: 'wallet', label: 'المحفظة' },
  { to: '/merchant/store', icon: 'store', label: 'المتجر' },
]
const active = (to: string) => to === '/merchant' ? route.path === '/merchant' : route.path.startsWith(to)
</script>

<template>
  <div class="m-shell">
    <div class="m-body"><slot /></div>
    <nav class="m-tabbar">
      <NuxtLink v-for="n in nav" :key="n.to" :to="n.to" class="m-tab" :class="{ active: active(n.to) }">
        <Icon :name="n.icon" :size="22" />
        <span>{{ n.label }}</span>
      </NuxtLink>
    </nav>
  </div>
</template>

<style>
.m-shell{max-width:440px;margin:0 auto;min-height:100vh;background:var(--body);position:relative;display:flex;flex-direction:column}
.m-body{flex:1;padding:16px 16px calc(78px + var(--safe-bottom,0px));overflow-x:hidden}
.m-tabbar{position:fixed;bottom:0;left:50%;transform:translateX(-50%);width:100%;max-width:440px;
  background:var(--card);border-top:1px solid var(--line);display:flex;justify-content:space-around;
  padding:8px 6px calc(8px + var(--safe-bottom,0px));z-index:40}
.m-tab{display:flex;flex-direction:column;align-items:center;gap:3px;font-size:11px;font-weight:700;
  color:var(--muted);padding:4px 10px;border-radius:12px;transition:.15s;flex:1}
.m-tab.active{color:var(--green-d)}
.m-tab.active :deep(svg){color:var(--green)}
.m-card{background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:var(--sh)}
.m-h1{font-size:20px;font-weight:800;color:var(--head)}
</style>
