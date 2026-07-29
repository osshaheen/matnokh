/** Arabic labels + pill colours for every enum the API returns. */

type Pill = 'pill-green' | 'pill-sand' | 'pill-terra' | 'pill-blue' | 'pill-gray'

export const ORDER_STATUS: Record<string, [string, Pill]> = {
  pending: ['بانتظار القبول', 'pill-sand'],
  accepted: ['تم القبول', 'pill-blue'],
  picked_up: ['تم الاستلام', 'pill-blue'],
  on_the_way: ['في الطريق', 'pill-blue'],
  delivered: ['تم التوصيل', 'pill-green'],
  canceled: ['ملغي', 'pill-terra'],
  returned: ['مرتجع', 'pill-terra'],
}

export const PARTNER_STATUS: Record<string, [string, Pill]> = {
  pending: ['قيد المراجعة', 'pill-sand'],
  approved: ['معتمد', 'pill-green'],
  rejected: ['مرفوض', 'pill-terra'],
  suspended: ['موقوف', 'pill-gray'],
}

// Payment records are for monitoring/documentation only — a single "recorded" state.
export const WITHDRAW_STATUS: Record<string, [string, Pill]> = {
  recorded: ['مسجّل', 'pill-green'],
  pending: ['مسجّل', 'pill-green'],
}

export const SUBSCRIPTION_STATUS: Record<string, [string, Pill]> = {
  active: ['فعّال', 'pill-green'],
  expired: ['منتهٍ', 'pill-sand'],
  canceled: ['ملغي', 'pill-terra'],
}

export const NOTIFICATION_STATUS: Record<string, [string, Pill]> = {
  draft: ['مسوّدة', 'pill-sand'],
  sent: ['تم الإرسال', 'pill-green'],
}

export const VEHICLE: Record<string, string> = {
  motorcycle: 'دراجة نارية',
  car: 'سيارة',
  bicycle: 'دراجة هوائية',
  van: 'فان',
}

export const PAYMENT: Record<string, string> = {
  cash: 'نقداً',
  card: 'بطاقة',
  wallet: 'محفظة',
}

export const WITHDRAW_METHOD: Record<string, string> = {
  bank: 'حوالة بنكية',
  wallet: 'محفظة إلكترونية',
  cash: 'نقداً',
}

export const AUDIENCE: Record<string, string> = {
  all: 'الجميع',
  customers: 'الزبائن',
  drivers: 'السائقون',
  merchants: 'التجّار',
}

export const BANNER_POSITION: Record<string, string> = {
  home_top: 'أعلى الرئيسية',
  home_middle: 'وسط الرئيسية',
  offers: 'صفحة العروض',
}

export const REQUESTER: Record<string, string> = {
  driver: 'سائق',
  merchant: 'تاجر',
}

/** Turns an enum map into `[{ value, label }]` for a <select>. */
export function options(map: Record<string, string | [string, string]>) {
  return Object.entries(map).map(([value, label]) => ({
    value,
    label: Array.isArray(label) ? label[0] : label,
  }))
}
