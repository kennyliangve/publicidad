<template>
  <footer v-if="siteStore.hasContact || siteStore.siteDescription" class="site-footer hide-mobile">
    <div class="container footer-inner">
      <div class="footer-brand">
        <strong>{{ siteStore.siteName }}</strong>
        <p v-if="siteStore.siteDescription">{{ siteStore.siteDescription }}</p>
      </div>
      <div v-if="siteStore.hasContact" class="footer-contact">
        <span v-if="siteStore.contactPhone" class="contact-item">
          <AppIcon name="phone" :size="14" />
          <a :href="`tel:${siteStore.contactPhone}`">{{ siteStore.contactPhone }}</a>
        </span>
        <span v-if="siteStore.contactEmail" class="contact-item">
          <AppIcon name="mail" :size="14" />
          <a :href="`mailto:${siteStore.contactEmail}`">{{ siteStore.contactEmail }}</a>
        </span>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { useSiteStore } from '@/stores/site'
import AppIcon from '@/components/AppIcon.vue'

const siteStore = useSiteStore()
</script>

<style scoped>
.site-footer {
  margin-top: 32px;
  padding: 24px 0 32px;
  background: var(--white);
  border-top: 1px solid var(--border);
}

.footer-inner {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 24px;
}

.footer-brand strong {
  font-size: 15px;
  color: var(--black);
}

.footer-brand p {
  margin-top: 6px;
  font-size: 13px;
  color: var(--text-muted);
  max-width: 480px;
  line-height: 1.5;
}

.footer-contact {
  display: flex;
  flex-direction: column;
  gap: 8px;
  font-size: 13px;
}

.contact-item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--text-secondary);
}

.contact-item a {
  color: inherit;
}

.contact-item a:hover {
  color: var(--black);
}
</style>
