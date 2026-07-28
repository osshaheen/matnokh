/**
 * List-screen state for a paginated API resource: query params, loading,
 * debounced reload, sorting and the create/update/delete calls.
 *
 * const orders = useResource('/orders', { status: '' })
 */
export function useResource<T = any>(endpoint: string, extraQuery: Record<string, any> = {}) {
  const api = useApi()
  const toast = useToast()

  const items = ref<T[]>([])
  const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
  const loading = ref(false)
  const saving = ref(false)

  const query = reactive({
    search: '',
    page: 1,
    per_page: 15,
    sort: 'id',
    dir: 'desc' as 'asc' | 'desc',
    ...extraQuery,
  })

  async function load() {
    loading.value = true
    try {
      const res = await api.get<any>(endpoint, query)
      items.value = res.data ?? []
      meta.value = res.meta ?? { current_page: 1, last_page: 1, per_page: query.per_page, total: items.value.length }
    } catch (e) {
      toast.error(apiError(e, 'تعذّر تحميل البيانات'))
    } finally {
      loading.value = false
    }
  }

  /** Wraps a write call: toasts on both outcomes, reloads on success. */
  async function submit<R = any>(fn: () => Promise<R>, okMessage?: string): Promise<R | null> {
    saving.value = true
    try {
      const res = await fn()
      if (okMessage) toast.success(okMessage)
      await load()
      return res
    } catch (e) {
      toast.error(apiError(e))
      return null
    } finally {
      saving.value = false
    }
  }

  const create = (payload: any, okMessage = 'تمت الإضافة بنجاح') =>
    submit(() => api.post(endpoint, payload), okMessage)

  const update = (id: number | string, payload: any, okMessage = 'تم الحفظ بنجاح') =>
    submit(() => api.put(`${endpoint}/${id}`, payload), okMessage)

  const remove = (id: number | string, okMessage = 'تم الحذف بنجاح') =>
    submit(() => api.del(`${endpoint}/${id}`), okMessage)

  const patch = (path: string, payload: any, okMessage?: string) =>
    submit(() => api.patch(path, payload), okMessage)

  function sortBy(column: string) {
    if (query.sort === column) query.dir = query.dir === 'asc' ? 'desc' : 'asc'
    else { query.sort = column; query.dir = 'desc' }
  }

  function reset() {
    Object.assign(query, { search: '', page: 1, sort: 'id', dir: 'desc' as const, ...extraQuery })
  }

  // changing anything other than the page sends you back to page 1
  const fingerprint = () => JSON.stringify({ ...query, page: undefined })
  watch(fingerprint, () => { query.page = 1 })

  let timer: ReturnType<typeof setTimeout>
  watch(query, () => {
    clearTimeout(timer)
    timer = setTimeout(load, 250)
  }, { deep: true })

  onMounted(load)

  // reactive() so callers write `orders.items` / `orders.loading` everywhere —
  // no `.value` in templates, no lost reactivity in script
  return reactive({ items, meta, loading, saving, query, load, create, update, remove, patch, submit, sortBy, reset })
}
