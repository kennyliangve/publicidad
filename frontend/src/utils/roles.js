/** 用户角色：0=普通 4=VIP 1=审核员 2=管理员 3=超级管理员 */
export const ROLES = {
  NORMAL: 0,
  MODERATOR: 1,
  ADMIN: 2,
  SUPER: 3,
  VIP: 4,
}

export const ROLE_OPTIONS = [
  { value: ROLES.NORMAL, label: '普通用户' },
  { value: ROLES.VIP, label: 'VIP用户' },
  { value: ROLES.MODERATOR, label: '审核员' },
  { value: ROLES.ADMIN, label: '管理员' },
  { value: ROLES.SUPER, label: '超级管理员' },
]

export function normalizeRole(role) {
  const n = Number(role)
  if (Number.isNaN(n)) return ROLES.NORMAL
  if (n === ROLES.VIP) return ROLES.VIP
  return Math.max(ROLES.NORMAL, Math.min(ROLES.SUPER, n))
}

export function roleLevel(role) {
  return normalizeRole(role)
}

export function roleLabel(role) {
  return ROLE_OPTIONS.find(o => o.value === normalizeRole(role))?.label ?? '普通用户'
}

export function isStaff(role) {
  const n = Number(role)
  return n >= ROLES.MODERATOR && n <= ROLES.SUPER
}

export function isVip(role) {
  return Number(role) === ROLES.VIP
}

export function isVipExpired(vipExpiresAt) {
  if (!vipExpiresAt) return false
  return new Date(vipExpiresAt).getTime() < Date.now()
}

export function isActiveVip(user) {
  if (!user || !isVip(user.role)) return false
  return !isVipExpired(user.vip_expires_at)
}

export function isAdmin(role) {
  return roleLevel(role) >= ROLES.ADMIN && isStaff(role)
}

export function isSuperAdmin(role) {
  return roleLevel(role) >= ROLES.SUPER
}

/** 后台模块最低角色要求 */
export const ADMIN_MODULE_MIN_ROLE = {
  dashboard: ROLES.MODERATOR,
  posts: ROLES.MODERATOR,
  categories: ROLES.ADMIN,
  users: ROLES.ADMIN,
  regions: ROLES.ADMIN,
  settings: ROLES.ADMIN,
  vip: ROLES.ADMIN,
  'vip-plans': ROLES.ADMIN,
}

export function canAccessAdminModule(role, module) {
  if (!isStaff(role)) return false
  const min = ADMIN_MODULE_MIN_ROLE[module]
  if (min === undefined) return false
  return roleLevel(role) >= min
}

/** 当前操作者能分配的角色选项 */
export function assignableRoles(actorRole) {
  const level = roleLevel(actorRole)
  if (!isStaff(actorRole) || level < ROLES.ADMIN) return []

  const maxStaff = level === ROLES.SUPER ? ROLES.SUPER : ROLES.ADMIN
  return ROLE_OPTIONS.filter((o) =>
    o.value === ROLES.NORMAL
    || o.value === ROLES.VIP
    || (o.value >= ROLES.MODERATOR && o.value <= maxStaff)
  )
}

export function canUploadPostImages(role, user = null) {
  if (isStaff(role)) return true
  if (!isVip(role)) return false
  if (!user) return true
  return isActiveVip(user)
}

export function hasEnabledVipPlans(vipUpgrade) {
  if (!vipUpgrade?.enabled) return false
  const plans = vipUpgrade.plans
  if (Array.isArray(plans) && plans.length > 0) return true
  return Number(vipUpgrade.amount || 0) > 0
}

export function canModifyUser(actorRole, targetRole) {
  const a = roleLevel(actorRole)
  if (!isStaff(actorRole) || a < ROLES.ADMIN) return false

  const t = normalizeRole(targetRole)
  if (t === ROLES.VIP || t === ROLES.NORMAL) return true
  return roleLevel(t) <= a
}
