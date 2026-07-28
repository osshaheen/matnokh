export function useAuth() {
  const token = useState<string | null>('token', () => null)
  const user = useState<any>('user', () => null)
  const abilities = useState<string[]>('abilities', () => [])
  const roles = useState<string[]>('roles', () => [])
  // mirrors GET /settings — the safety toggles gate `can()` below
  const sys = useState<Record<string, any>>('sys', () => ({
    deletion_enabled: true, trash_enabled: true, restore_enabled: true,
  }))
  const config = useRuntimeConfig()
  const api = (p: string) => `${config.public.apiBase}${p}`

  function loadToken() {
    if (import.meta.client && !token.value) token.value = localStorage.getItem('ws_token')
    return token.value
  }
  function headers(): Record<string, string> {
    const t = loadToken()
    return t ? { Authorization: `Bearer ${t}`, Accept: 'application/json' } : { Accept: 'application/json' }
  }
  function can(permission: string): boolean {
    if (!abilities.value.includes(permission)) return false
    if (permission.endsWith('.delete') && !sys.value.deletion_enabled) return false
    if (permission.startsWith('trash.') && !sys.value.trash_enabled) return false
    return true
  }
  function hasRole(role: string): boolean { return roles.value.includes(role) }

  async function loadSettings() {
    try { const res = await $fetch<{ data: any }>(api('/settings'), { headers: headers() }); sys.value = res.data } catch {}
  }
  async function login(email: string, password: string) {
    const res = await $fetch<{ token: string; user: any }>(api('/login'), {
      method: 'POST', body: { email, password }, headers: { Accept: 'application/json' },
    })
    token.value = res.token; user.value = res.user
    abilities.value = res.user?.abilities ?? []; roles.value = res.user?.roles ?? []
    if (import.meta.client) localStorage.setItem('ws_token', res.token)
    await loadSettings()
    return res
  }
  async function fetchMe() {
    try {
      const res = await $fetch<{ data: any }>(api('/me'), { headers: headers() })
      user.value = res.data; abilities.value = res.data?.abilities ?? []; roles.value = res.data?.roles ?? []
      loadSettings(); return res.data
    } catch { logout(); return null }
  }
  async function logout() {
    try { await $fetch(api('/logout'), { method: 'POST', headers: headers() }) } catch {}
    token.value = null; user.value = null; abilities.value = []; roles.value = []
    if (import.meta.client) localStorage.removeItem('ws_token')
    await navigateTo('/login')
  }
  return { token, user, abilities, roles, sys, can, hasRole, login, logout, fetchMe, loadToken, headers, api, loadSettings }
}
