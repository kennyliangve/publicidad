import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from '@/api'

const DEFAULTS = {
  site_name: '同城信息',
  site_description: '本地生活服务平台',
  site_logo: '',
  contact_email: '',
  contact_phone: '',
  posts_per_page: 20,
  allow_register: true,
  require_post_review: false,
  vip_upgrade: { enabled: false, plans: [] },
}

export const useSiteStore = defineStore('site', () => {
  const settings = ref({ ...DEFAULTS })
  const loaded = ref(false)
  let loadPromise = null

  const siteName = computed(() => settings.value.site_name || DEFAULTS.site_name)
  const siteDescription = computed(() => settings.value.site_description || DEFAULTS.site_description)
  const siteLogo = computed(() => settings.value.site_logo || '')
  const postsPerPage = computed(() => settings.value.posts_per_page || DEFAULTS.posts_per_page)
  const allowRegister = computed(() => settings.value.allow_register !== false)
  const requirePostReview = computed(() => !!settings.value.require_post_review)
  const vipUpgrade = computed(() => settings.value.vip_upgrade || DEFAULTS.vip_upgrade)
  const contactEmail = computed(() => settings.value.contact_email || '')
  const contactPhone = computed(() => settings.value.contact_phone || '')
  const hasContact = computed(() => !!(contactEmail.value || contactPhone.value))

  function applyDocumentMeta() {
    const desc = siteDescription.value
    if (desc) {
      let meta = document.querySelector('meta[name="description"]')
      if (!meta) {
        meta = document.createElement('meta')
        meta.setAttribute('name', 'description')
        document.head.appendChild(meta)
      }
      meta.setAttribute('content', desc)
    }
  }

  async function load() {
    if (loaded.value) return settings.value
    if (loadPromise) return loadPromise

    loadPromise = (async () => {
      try {
        const data = await api.getSiteSettings()
        settings.value = { ...DEFAULTS, ...data }
        applyDocumentMeta()
      } catch {
        settings.value = { ...DEFAULTS }
      } finally {
        loaded.value = true
        loadPromise = null
      }
      return settings.value
    })()

    return loadPromise
  }

  async function reload() {
    loaded.value = false
    return load()
  }

  return {
    settings,
    loaded,
    siteName,
    siteDescription,
    siteLogo,
    postsPerPage,
    allowRegister,
    requirePostReview,
    vipUpgrade,
    contactEmail,
    contactPhone,
    hasContact,
    load,
    reload,
  }
})
