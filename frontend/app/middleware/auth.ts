export default defineNuxtRouteMiddleware(async (to) => {
  if (!import.meta.client) return
  const t = localStorage.getItem('ws_token')
  if (!t && to.path !== '/login') return navigateTo('/login')
  if (t && to.path === '/login') return navigateTo('/')

  const { abilities, fetchMe } = useAuth()
  if (t) {
    const last = +(sessionStorage.getItem('ws_abilities_ts') || 0)
    const stale = Date.now() - last > 3 * 60 * 1000
    if (abilities.value.length === 0 || stale) {
      await fetchMe(); sessionStorage.setItem('ws_abilities_ts', String(Date.now()))
    }
  }
})
