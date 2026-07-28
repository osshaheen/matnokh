// thin $fetch wrapper that injects the bearer token + base url
export function useApi() {
  const { api, headers } = useAuth()
  function req<T = any>(path: string, opts: any = {}): Promise<T> {
    return $fetch<T>(api(path), { ...opts, headers: { ...headers(), ...(opts.headers || {}) } })
  }
  return {
    get: <T = any>(p: string, query?: any) => req<T>(p, { method: 'GET', query }),
    post: <T = any>(p: string, body?: any) => req<T>(p, { method: 'POST', body }),
    put: <T = any>(p: string, body?: any) => req<T>(p, { method: 'PUT', body }),
    del: <T = any>(p: string) => req<T>(p, { method: 'DELETE' }),
  }
}
