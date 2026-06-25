import { boot } from './env'

export class ApiError extends Error {
  constructor(
    public readonly body: unknown,
    public readonly status: number
  ) {
    super(`API error ${status}`)
  }
}

let nonceRefreshPromise: Promise<void> | null = null

async function refreshNonce(): Promise<void> {
  const res = await fetch(`${boot.apiBase}/nonce`)
  if (res.ok) {
    const json = await res.json() as { nonce: string }
    boot.nonce = json.nonce
  }
}

export async function api<T>(path: string, init: RequestInit = {}): Promise<T> {
  const res = await fetch(`${boot.apiBase}${path}`, {
    ...init,
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': boot.nonce,
      ...init.headers,
    },
  })

  // Nonce expirado — renova e tenta uma vez
  if (res.status === 401) {
    if (!nonceRefreshPromise) {
      nonceRefreshPromise = refreshNonce().finally(() => { nonceRefreshPromise = null })
    }
    await nonceRefreshPromise

    const retry = await fetch(`${boot.apiBase}${path}`, {
      ...init,
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': boot.nonce,
        ...init.headers,
      },
    })

    if (!retry.ok) throw new ApiError(await retry.json(), retry.status)
    return retry.json() as Promise<T>
  }

  if (!res.ok) throw new ApiError(await res.json(), res.status)

  return res.json() as Promise<T>
}
