<template>
  <nav class="tab-bar hide-desktop" aria-label="主导航">
    <div class="tab-bar-inner">
      <router-link to="/" class="tab-item" :class="{ active: route.name === 'Home' }">
        <span class="tab-icon-wrap">
          <AppIcon name="home" :size="22" />
        </span>
        <span class="tab-label">首页</span>
      </router-link>

      <router-link to="/publish" class="tab-item tab-publish" :class="{ active: route.name === 'Publish' }">
        <span class="publish-fab" aria-hidden="true">
          <AppIcon name="plus" :size="22" />
        </span>
        <span class="tab-label">发布</span>
      </router-link>

      <router-link to="/user" class="tab-item" :class="{ active: isUserActive }">
        <span class="tab-icon-wrap">
          <AppIcon name="user" :size="22" />
        </span>
        <span class="tab-label">我的</span>
      </router-link>
    </div>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppIcon from '@/components/AppIcon.vue'

const route = useRoute()

const isUserActive = computed(() =>
  ['User', 'Upgrade', 'Login', 'Register'].includes(route.name)
)
</script>

<style scoped>
.tab-bar {
  display: none;
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 200;
  width: 100%;
  max-width: 100%;
  overflow-x: clip;
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-top: 1px solid rgba(0, 0, 0, 0.06);
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.06);
  padding-bottom: env(safe-area-inset-bottom, 0px);
  box-sizing: border-box;
}

.tab-bar-inner {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  align-items: flex-end;
  width: 100%;
  max-width: var(--max-width);
  min-width: 0;
  height: var(--tabbar-h);
  margin: 0 auto;
  padding: 0 16px;
  box-sizing: border-box;
}

.tab-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  gap: 3px;
  min-width: 0;
  padding-bottom: 6px;
  color: var(--text-muted);
  transition: color 0.2s;
  -webkit-tap-highlight-color: transparent;
}

.tab-icon-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 28px;
  border-radius: 14px;
  transition: background 0.2s, color 0.2s;
}

.tab-label {
  font-size: 10px;
  line-height: 1.2;
  font-weight: 500;
  letter-spacing: 0.02em;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.tab-item.active {
  color: var(--black);
}

.tab-item.active .tab-icon-wrap {
  background: var(--primary-light);
  color: var(--black);
}

.tab-item.active .tab-label {
  font-weight: 700;
}

.tab-publish {
  position: relative;
  padding-bottom: 4px;
}

.publish-fab {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  margin-top: -20px;
  margin-bottom: 2px;
  border-radius: 50%;
  background: var(--black);
  color: var(--primary);
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.28);
  border: 3px solid var(--white);
  transition: transform 0.15s, box-shadow 0.15s;
  flex-shrink: 0;
}

.tab-publish:active .publish-fab {
  transform: scale(0.94);
}

.tab-publish.active .publish-fab {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.35), 0 0 0 2px var(--primary);
}

.tab-publish.active .tab-label {
  font-weight: 700;
  color: var(--black);
}

@media (max-width: 768px) {
  .tab-bar {
    display: block;
  }
}

@media (max-width: 360px) {
  .tab-bar-inner {
    padding: 0 12px;
  }

  .tab-icon-wrap {
    width: 36px;
    height: 26px;
  }

  .publish-fab {
    width: 40px;
    height: 40px;
    margin-top: -18px;
    border-width: 2px;
  }

  .tab-label {
    font-size: 9px;
  }
}
</style>
