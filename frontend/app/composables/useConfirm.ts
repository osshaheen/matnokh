export type ConfirmOptions = {
  title: string
  text?: string
  confirmText?: string
  danger?: boolean
}

type ConfirmState = {
  open: boolean
  options: ConfirmOptions
  resolve: ((ok: boolean) => void) | null
}

/**
 * Promise-based confirmation, rendered by <ConfirmHost /> in the layout:
 *   if (!(await confirm({ title: 'حذف السائق؟', danger: true }))) return
 */
export function useConfirm() {
  const state = useState<ConfirmState>('confirm', () => ({
    open: false,
    options: { title: '' },
    resolve: null,
  }))

  function confirm(options: ConfirmOptions): Promise<boolean> {
    // a second prompt while one is open cancels the first rather than losing it
    state.value.resolve?.(false)
    return new Promise((resolve) => {
      state.value = { open: true, options, resolve }
    })
  }

  function answer(ok: boolean) {
    state.value.resolve?.(ok)
    state.value = { ...state.value, open: false, resolve: null }
  }

  return { state, confirm, answer }
}
