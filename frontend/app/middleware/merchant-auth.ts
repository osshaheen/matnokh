export default defineNuxtRouteMiddleware((to) => {
  if (!import.meta.client) return
  const t = localStorage.getItem('wsm_token')
  if (!t && to.path !== '/merchant/login') return navigateTo('/merchant/login')
  if (t && to.path === '/merchant/login') return navigateTo('/merchant')
})
