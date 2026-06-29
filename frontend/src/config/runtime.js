/** 运行时环境：本地开发 / Cloudflare 根路径 / phpstudy 子目录 */

export const BASE_PATH = import.meta.env.BASE_URL || '/'

export const IS_DEV = import.meta.env.DEV

/** Cloudflare 或本地 dev（根路径 + /api 代理） */
export const IS_ROOT_DEPLOY = !IS_DEV && BASE_PATH === '/'

/** 本地 Vite 开发服务器 */
export const IS_VITE_DEV = IS_DEV

export const SITE_ORIGIN = (import.meta.env.VITE_SITE_ORIGIN || '').replace(/\/$/, '')

/**
 * API 根路径
 * - 开发：/api（Vite 代理）
 * - Cloudflare：/api（Worker 代理）
 * - phpstudy：/publicidad/api/index.php
 */
export function resolveApiBase() {
  const envBase = import.meta.env.VITE_API_BASE
  if (envBase) {
    // 开发环境禁止跨域直连，统一走代理
    if (IS_VITE_DEV && /^https?:\/\//i.test(envBase)) {
      return '/api'
    }
    return envBase.replace(/\/$/, '')
  }
  if (IS_VITE_DEV) return '/api'
  return BASE_PATH === '/' ? '/api' : '/publicidad/api/index.php'
}

export const API_BASE = resolveApiBase()

/** 图片/Logo 在 Cloudflare 根路径部署时走线上源站 */
export function resolveUploadBase() {
  if (IS_VITE_DEV) return '/img'
  if (IS_ROOT_DEPLOY && SITE_ORIGIN) return `${SITE_ORIGIN}/publicidad/uploads`
  return '/publicidad/uploads'
}

export function resolveLogoBase() {
  if (IS_VITE_DEV) return '/logo'
  if (IS_ROOT_DEPLOY && SITE_ORIGIN) return `${SITE_ORIGIN}/publicidad/logo`
  return '/publicidad/logo'
}

/** 存库用的相对路径（始终与 phpstudy 线上一致） */
export const UPLOAD_STORE_PATH = '/publicidad/uploads'
export const LOGO_STORE_PATH = '/publicidad/logo'
