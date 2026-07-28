export type Toast = { id: number; text: string; kind: 'ok' | 'err' }

let seq = 0

export function useToast() {
  const toasts = useState<Toast[]>('toasts', () => [])

  function push(text: string, kind: Toast['kind']) {
    const id = ++seq
    toasts.value = [...toasts.value, { id, text, kind }]
    setTimeout(() => dismiss(id), 3800)
  }

  function dismiss(id: number) {
    toasts.value = toasts.value.filter(t => t.id !== id)
  }

  return {
    toasts,
    dismiss,
    success: (text: string) => push(text, 'ok'),
    error: (text: string) => push(text, 'err'),
  }
}
