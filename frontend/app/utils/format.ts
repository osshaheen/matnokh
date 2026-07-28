/** Display helpers — all Gregorian/Arabic-numeral so numbers stay scannable. */

export function money(value: number | string | null | undefined, currency = '₪'): string {
  const n = Number(value ?? 0)
  return `${n.toLocaleString('en-US', { minimumFractionDigits: n % 1 ? 2 : 0, maximumFractionDigits: 2 })} ${currency}`
}

export function num(value: number | string | null | undefined): string {
  return Number(value ?? 0).toLocaleString('en-US')
}

export function date(value: string | null | undefined): string {
  if (!value) return '—'
  const d = new Date(value)
  return isNaN(+d) ? '—' : d.toLocaleDateString('en-GB')
}

export function dateTime(value: string | null | undefined): string {
  if (!value) return '—'
  const d = new Date(value)
  if (isNaN(+d)) return '—'
  return `${d.toLocaleDateString('en-GB')} · ${d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' })}`
}

export function ago(value: string | null | undefined): string {
  if (!value) return '—'
  const d = new Date(value)
  if (isNaN(+d)) return '—'
  const secs = Math.round((Date.now() - +d) / 1000)
  if (secs < 60) return 'الآن'
  const mins = Math.round(secs / 60)
  if (mins < 60) return `قبل ${mins} دقيقة`
  const hours = Math.round(mins / 60)
  if (hours < 24) return `قبل ${hours} ساعة`
  const days = Math.round(hours / 24)
  if (days < 30) return `قبل ${days} يوم`
  return date(value)
}

/** Today's date as `YYYY-MM-DD`, for date inputs. */
export function todayISO(): string {
  return new Date().toISOString().slice(0, 10)
}
