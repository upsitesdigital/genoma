/**
 * Chamada à REST do framework (namespace framework/v1) a partir de um store da
 * Interactivity API. Configuração (apiBase, nonce, ajaxUrl) vem do PHP:
 * Bootstrap::interactivity() → wp_interactivity_config('upwork', …).
 *
 *   import { api } from 'upwork/api'
 *   const data = yield api('/blog?page=2')            // dentro de uma action generator
 *   const data = await api('/home/12')                 // em código async comum
 *
 * Nonce vencido (página aberta há muito tempo ou servida de cache) → renova e repete uma vez.
 */
import { getConfig } from '@wordpress/interactivity'

const config = () => getConfig('upwork') ?? {}

let nonce = ''
let renewing = null

export class ApiError extends Error {
  constructor(message, status, data) {
    super(message)
    this.status = status
    this.data = data
  }
}

/** Pede um nonce novo (admin-ajax reconhece o usuário pelo cookie). Uma renovação por vez. */
function renewNonce() {
  renewing ??= fetch(`${config().ajaxUrl}?action=upwork_rest_nonce`, { credentials: 'same-origin', cache: 'no-store' })
    .then((r) => (r.ok ? r.json() : null))
    .then((d) => (nonce = d?.nonce || nonce))
    .finally(() => (renewing = null))
  return renewing
}

/**
 * URL da rota: a query do `path` vai em searchParams — com permalinks simples o apiBase já é
 * `…/?rest_route=/framework/v1`, e um segundo `?` entraria no valor do rest_route (404).
 */
function routeUrl(path) {
  const [route, query] = String(path).replace(/^\//, '').split(/\?(.*)/s)
  const url = new URL(`${config().apiBase}/${route}`, location.href)
  new URLSearchParams(query ?? '').forEach((value, key) => url.searchParams.append(key, value))
  return url
}

export async function api(path, init = {}, retried = false) {
  const headers = new Headers(init.headers)
  headers.set('X-WP-Nonce', nonce || config().nonce || '')
  if (init.body && !headers.has('Content-Type')) headers.set('Content-Type', 'application/json')

  const res = await fetch(routeUrl(path), {
    credentials: 'same-origin',
    ...init,
    headers,
  })

  const data = await res.json().catch(() => null)
  if (!res.ok) {
    if (!retried && res.status === 403 && data?.code === 'rest_cookie_invalid_nonce') {
      await renewNonce()
      return api(path, init, true)
    }
    throw new ApiError(data?.message ?? res.statusText, res.status, data)
  }
  return data
}
