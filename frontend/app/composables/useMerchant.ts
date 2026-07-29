// Merchant-app auth + api — a token separate from the admin's (key: wsm_token).
export function useMerchant() {
  const token = useState<string | null>('m_token', () => null)
  const store = useState<any>('m_store', () => null)
  const config = useRuntimeConfig()
  const api = (p: string) => `${config.public.apiBase}/merchant${p}`

  function loadToken() {
    if (import.meta.client && !token.value) token.value = localStorage.getItem('wsm_token')
    return token.value
  }
  function headers(): Record<string, string> {
    const t = loadToken()
    return t ? { Authorization: `Bearer ${t}`, Accept: 'application/json' } : { Accept: 'application/json' }
  }
  async function loginMethod(): Promise<string> {
    try { const r = await $fetch<{ method: string }>(api('/login-method')); return r.method } catch { return 'phone_password' }
  }
  async function requestOtp(phone: string) {
    return $fetch<{ message: string; dev_code?: string }>(api('/request-otp'), { method: 'POST', body: { phone }, headers: { Accept: 'application/json' } })
  }
  async function login(body: Record<string, any>) {
    const res = await $fetch<{ token: string; merchant: any }>(api('/login'), { method: 'POST', body, headers: { Accept: 'application/json' } })
    token.value = res.token; store.value = res.merchant
    if (import.meta.client) localStorage.setItem('wsm_token', res.token)
    return res
  }
  async function logout() {
    try { await $fetch(api('/logout'), { method: 'POST', headers: headers() }) } catch {}
    token.value = null; store.value = null
    if (import.meta.client) localStorage.removeItem('wsm_token')
    await navigateTo('/merchant/login')
  }
  function req<T = any>(path: string, opts: any = {}): Promise<T> {
    return $fetch<T>(api(path), { ...opts, headers: { ...headers(), ...(opts.headers || {}) } })
  }
  const http = {
    get: <T = any>(p: string, query?: any) => req<T>(p, { method: 'GET', query }),
    post: <T = any>(p: string, body?: any) => req<T>(p, { method: 'POST', body }),
    put: <T = any>(p: string, body?: any) => req<T>(p, { method: 'PUT', body }),
    patch: <T = any>(p: string, body?: any) => req<T>(p, { method: 'PATCH', body }),
    del: <T = any>(p: string) => req<T>(p, { method: 'DELETE' }),
  }
  return { token, store, loadToken, headers, api, loginMethod, requestOtp, login, logout, http }
}
