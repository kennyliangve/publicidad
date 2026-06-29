/**
 * Cloudflare Worker：/api 代理到 PHP 后端，其余请求走静态资源
 */
export default {
  async fetch(request, env) {
    const url = new URL(request.url)

    if (url.pathname.startsWith('/api')) {
      return proxyApi(request, env)
    }

    return env.ASSETS.fetch(request)
  },
}

async function proxyApi(request, env) {
  const apiOrigin = env.API_ORIGIN || 'https://www.vecino.com.ve/publicidad/api/index.php'

  if (request.method === 'OPTIONS') {
    return new Response(null, { status: 204, headers: corsHeaders() })
  }

  const url = new URL(request.url)
  const subPath = url.pathname.replace(/^\/api\/?/, '')
  const target = `${apiOrigin.replace(/\/$/, '')}/${subPath}${url.search}`

  const headers = new Headers(request.headers)
  headers.delete('host')

  const init = {
    method: request.method,
    headers,
    redirect: 'follow',
  }

  if (request.method !== 'GET' && request.method !== 'HEAD') {
    init.body = request.body
  }

  const response = await fetch(target, init)
  const outHeaders = new Headers(response.headers)
  Object.entries(corsHeaders()).forEach(([k, v]) => outHeaders.set(k, v))

  return new Response(response.body, {
    status: response.status,
    headers: outHeaders,
  })
}

function corsHeaders() {
  return {
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
    'Access-Control-Allow-Headers': 'Content-Type, Authorization',
  }
}
