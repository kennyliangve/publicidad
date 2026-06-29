/** 委内瑞拉电话：0412-0000000（11 位，02XX 固话 / 04XX 手机） */

export const PHONE_PLACEHOLDER = '0412-0000000'
export const PHONE_HINT = '格式：0412-0000000（11 位，可含一个连字符）'

/** 输入时格式化为 XXXX-XXXXXXX */
export function formatPhoneAsYouType(value) {
  let digits = String(value ?? '').replace(/\D/g, '')
  if (digits.length > 11) {
    digits = digits.slice(0, 11)
  }
  if (digits.length <= 4) {
    return digits
  }
  return `${digits.slice(0, 4)}-${digits.slice(4)}`
}

/** 规范化为 0412-0000000，无效返回 null */
export function normalizeVenezuelaPhone(input) {
  const raw = String(input ?? '').trim()
  if (!raw) return null

  let compact = raw.replace(/\s+/g, '')

  if (/^\+?58/.test(compact)) {
    let digits = compact.replace(/\D/g, '')
    if (digits.startsWith('58')) {
      digits = digits.slice(2)
    }
    if (digits.length === 10 && (digits[0] === '4' || digits[0] === '2')) {
      digits = `0${digits}`
    }
    compact = digits
  } else {
    compact = compact.replace(/\D/g, '')
  }

  if (compact.length !== 11 || !/^0[24]\d{9}$/.test(compact)) {
    return null
  }

  return `${compact.slice(0, 4)}-${compact.slice(4)}`
}

export function isValidVenezuelaPhone(input) {
  return normalizeVenezuelaPhone(input) !== null
}

export function validatePhoneMessage(input, label = '电话号码') {
  if (!String(input ?? '').trim()) {
    return `请填写${label}`
  }
  if (!isValidVenezuelaPhone(input)) {
    return `${label}格式不正确，请使用委内瑞拉格式，如：0412-0000000`
  }
  return null
}

/** 脱敏显示 */
export function maskVenezuelaPhone(phone) {
  const normalized = normalizeVenezuelaPhone(phone)
  if (!normalized) {
    const digits = String(phone ?? '').replace(/\D/g, '')
    if (digits.length < 7) return phone || ''
    return `${digits.slice(0, 4)}-***${digits.slice(-3)}`
  }
  return `${normalized.slice(0, 5)}***${normalized.slice(-3)}`
}

/** 登录账号：邮箱 lowercase，手机号规范化 */
export function normalizeLoginAccount(account) {
  const value = String(account ?? '').trim()
  if (!value) return ''
  if (value.includes('@')) {
    return value.toLowerCase()
  }
  return normalizeVenezuelaPhone(value) || value
}

/** WhatsApp 国际号码（委内瑞拉 58 + 10 位） */
export function toWhatsAppPhone(input) {
  const normalized = normalizeVenezuelaPhone(input)
  if (normalized) {
    return `58${normalized.replace(/\D/g, '').slice(1)}`
  }

  let digits = String(input ?? '').replace(/\D/g, '')
  if (digits.startsWith('58') && digits.length === 12) return digits
  if (digits.length === 11 && digits.startsWith('0')) return `58${digits.slice(1)}`
  if (digits.length === 10 && /^[24]/.test(digits)) return `58${digits}`

  return null
}

/** 生成 WhatsApp 跳转链接 */
export function buildWhatsAppUrl(phone, text = '') {
  const waPhone = toWhatsAppPhone(phone)
  if (!waPhone) return null
  const base = `https://wa.me/${waPhone}`
  if (!text) return base
  return `${base}?text=${encodeURIComponent(text)}`
}

/** 委内瑞拉当地日期 YYYY-MM-DD（America/Caracas，供 date 输入框） */
export function getVenezuelaTodayIsoDate() {
  return new Date().toLocaleDateString('en-CA', { timeZone: 'America/Caracas' })
}
