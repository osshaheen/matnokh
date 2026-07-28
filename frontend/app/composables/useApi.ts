// thin $fetch wrapper that injects the bearer token + base url
export function useApi() {
  const { api, headers } = useAuth()

  // empty strings would be sent as real filters, so drop them before the request
  function clean(query?: Record<string, any>) {
    if (!query) return undefined
    return Object.fromEntries(
      Object.entries(query).filter(([, v]) => v !== '' && v !== null && v !== undefined),
    )
  }

  function req<T = any>(path: string, opts: any = {}): Promise<T> {
    return $fetch<T>(api(path), { ...opts, headers: { ...headers(), ...(opts.headers || {}) } })
  }

  return {
    get: <T = any>(p: string, query?: Record<string, any>) => req<T>(p, { method: 'GET', query: clean(query) }),
    post: <T = any>(p: string, body?: any) => req<T>(p, { method: 'POST', body }),
    put: <T = any>(p: string, body?: any) => req<T>(p, { method: 'PUT', body }),
    patch: <T = any>(p: string, body?: any) => req<T>(p, { method: 'PATCH', body }),
    del: <T = any>(p: string) => req<T>(p, { method: 'DELETE' }),
  }
}

/** Pulls a human message out of a Laravel error response. */
export function apiError(e: any, fallback = 'حدث خطأ غير متوقّع'): string {
  const data = e?.data
  if (data?.errors) {
    const first = Object.values(data.errors)[0]
    if (Array.isArray(first) && first[0]) return String(first[0])
  }
  return data?.message || e?.message || fallback
}
