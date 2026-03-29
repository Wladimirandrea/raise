<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'

const route   = useRoute()
const { t }   = useI18n()
const auth    = useAuthStore()

const navItems = computed(() => [
  { to: '/case-manager/dashboard', icon: '🏠', label: t('cm.nav.dashboard') },
  { to: '/case-manager/citas',     icon: '📅', label: t('cm.nav.appointments') },
  { to: '/case-manager/clientes',  icon: '👥', label: t('cm.nav.clients') },
  { to: '/case-manager/perfil',    icon: '👤', label: t('cm.nav.profile') },
])

const isActive = (path) => route.path === path
</script>

<template>
  <aside class="cm-sidebar">

    <!-- Logo / Brand -->
    <div class="cm-sidebar-header">
      <div class="cm-logo">
        <span class="cm-logo-icon">🩺</span>
        <div class="cm-logo-text">
          <span class="cm-logo-title">Case Manager</span>
          <span class="cm-logo-sub">{{ auth.userName }}</span>
        </div>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="cm-nav">
      <router-link
        v-for="item in navItems"
        :key="item.to"
        :to="item.to"
        class="cm-nav-item"
        :class="{ active: isActive(item.to) }"
      >
        <span class="cm-nav-icon">{{ item.icon }}</span>
        <span class="cm-nav-label">{{ item.label }}</span>
        <span v-if="isActive(item.to)" class="cm-nav-indicator"></span>
      </router-link>
    </nav>

    <!-- Footer -->
    <div class="cm-sidebar-footer">
      <div class="cm-user-info">
        <img
          :src="auth.user?.avatar ? `/storage/${auth.user.avatar}` : '/storage/avatars/default.png'"
          class="cm-avatar"
          alt="avatar"
        />
        <div class="cm-user-details">
          <span class="cm-user-name">{{ auth.userName }}</span>
          <span class="cm-user-role">Case Manager</span>
        </div>
      </div>
    </div>

  </aside>
</template>

<style scoped>
.cm-sidebar {
  width: 240px;
  min-height: 100vh;
  background: linear-gradient(180deg, #0f766e 0%, #115e59 50%, #134e4a 100%);
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
}

/* Header */
.cm-sidebar-header {
  padding: 24px 16px 20px;
  border-bottom: 1px solid rgba(255,255,255,.1);
}
.cm-logo {
  display: flex;
  align-items: center;
  gap: 10px;
}
.cm-logo-icon {
  font-size: 1.6rem;
  background: rgba(255,255,255,.15);
  border-radius: 10px;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.cm-logo-text {
  display: flex;
  flex-direction: column;
}
.cm-logo-title {
  font-size: 0.9rem;
  font-weight: 700;
  color: #ffffff;
  line-height: 1.2;
}
.cm-logo-sub {
  font-size: 0.72rem;
  color: #99f6e4;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 150px;
}

/* Nav */
.cm-nav {
  flex: 1;
  padding: 16px 10px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.cm-nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 10px;
  text-decoration: none;
  color: #ccfbf1;
  font-size: 0.88rem;
  font-weight: 500;
  transition: all .2s;
  position: relative;
}
.cm-nav-item:hover {
  background: rgba(255,255,255,.1);
  color: #ffffff;
}
.cm-nav-item.active {
  background: rgba(255,255,255,.18);
  color: #ffffff;
  font-weight: 700;
}
.cm-nav-icon {
  font-size: 1.1rem;
  width: 24px;
  text-align: center;
  flex-shrink: 0;
}
.cm-nav-label {
  flex: 1;
}
.cm-nav-indicator {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #5eead4;
  flex-shrink: 0;
}

/* Footer */
.cm-sidebar-footer {
  padding: 16px;
  border-top: 1px solid rgba(255,255,255,.1);
}
.cm-user-info {
  display: flex;
  align-items: center;
  gap: 10px;
}
.cm-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid rgba(255,255,255,.3);
  flex-shrink: 0;
}
.cm-user-details {
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.cm-user-name {
  font-size: 0.82rem;
  font-weight: 600;
  color: #ffffff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.cm-user-role {
  font-size: 0.7rem;
  color: #99f6e4;
}
</style>