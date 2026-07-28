type Lookups = {
  cities: any[]
  store_categories: any[]
  services: any[]
  subscription_plans: any[]
  drivers: any[]
  merchants: any[]
  enums: Record<string, string[]>
}

const EMPTY: Lookups = {
  cities: [], store_categories: [], services: [],
  subscription_plans: [], drivers: [], merchants: [], enums: {},
}

/**
 * Dropdown data for the whole dashboard, fetched once per session.
 * Call `refresh()` after adding a city/category so the selects stay current.
 */
export function useLookups() {
  const api = useApi()
  const data = useState<Lookups>('lookups', () => ({ ...EMPTY }))
  const loaded = useState<boolean>('lookups_loaded', () => false)
  const pending = useState<Promise<void> | null>('lookups_pending', () => null)

  async function fetchOnce() {
    const res = await api.get<{ data: Lookups }>('/lookups')
    data.value = { ...EMPTY, ...res.data }
    loaded.value = true
  }

  async function load() {
    if (loaded.value) return data.value
    // in-flight de-dupe: several components may ask on the same page
    pending.value ??= fetchOnce().finally(() => { pending.value = null })
    await pending.value
    return data.value
  }

  async function refresh() {
    loaded.value = false
    await load()
  }

  return { lookups: data, load, refresh }
}
