import { boot } from './env'

export class ApiError extends Error {
  constructor(
    public readonly body: unknown,
    public readonly status: number
  ) {
    super(`API error ${status}`)
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

  if (!res.ok) throw new ApiError(await res.json(), res.status)

  return res.json() as Promise<T>
}
