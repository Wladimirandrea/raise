<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useNotificationsStore } from '@/stores/notifications'

const auth = useAuthStore()
const router = useRouter()
const { t, locale } = useI18n()
const notifications = useNotificationsStore()
const newUsersDropdownOpen = ref(false)

const handleLogout = () => {
  auth.logout()
  router.push('/')
}

const changeLanguage = (lang) => {
  locale.value = lang
  localStorage.setItem('locale', lang)
  languageDropdownOpen.value = false
}
const handleNewUsersClick = () => {
  newUsersDropdownOpen.value = !newUsersDropdownOpen.value
}

const clearNotifications = () => {
  notifications.reset()
  newUsersDropdownOpen.value = false
}

const currentFlag = computed(() => locale.value === 'en' ? '🇺🇸' : '🇲🇽')
const currentLang = computed(() => locale.value === 'en' ? 'EN' : 'ES')

const notificationCount = ref(4)
const newUserNotifications = ref(3)
const profileDropdownOpen = ref(false)
const languageDropdownOpen = ref(false)
</script>

<template>
  <header class="bg-gray-950 border-b border-gray-800">
    <div class="max-w-screen-2xl mx-auto px-4 lg:px-8">
      <div class="flex h-14 items-center justify-between">
        <!-- Logo / Brand -->
        <div class="flex items-center gap-3">
          <div class="h-8 w-8 rounded bg-blue-600 flex items-center justify-center text-white font-bold text-lg">
            B
          </div>
          <span class="text-xs text-gray-500 capitalize">
            {{ auth.userRoles[0]?.name || 'Invitado' }}
          </span>
        </div>

        <!-- Búsqueda centrada -->
        <div class="hidden md:flex flex-1 max-w-xl mx-8">
          <div class="relative w-full">
            <input type="text" :placeholder="t('nav.search')"
              class="w-full h-9 pl-10 pr-4 text-sm bg-gray-900 border border-gray-700 rounded-md text-gray-200 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500/30 transition">
            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-lg">
              🔍
            </div>
          </div>
        </div>

        <!-- Acciones derecha -->
        <div class="flex items-center gap-4 lg:gap-6">
          <!-- Notificaciones generales -->
          <button class="relative p-2 text-gray-400 hover:text-gray-200 focus:outline-none transition">
            <span class="text-xl">🔔</span>
            <span v-if="notificationCount > 0"
              class="absolute -top-1 -right-1 min-w-[18px] h-[18px] flex items-center justify-center text-[10px] font-bold text-white bg-red-600 rounded-full px-1.5">
              {{ notificationCount }}
            </span>
          </button>

          <!-- Nuevos usuarios -->
          <div class="relative">
            <button @click="handleNewUsersClick"
              class="relative p-2 text-gray-400 hover:text-gray-200 focus:outline-none transition">
              <span class="text-xl">👤</span>
              <span v-if="notifications.newUsersCount > 0"
                class="absolute -top-1 -right-1 min-w-[18px] h-[18px] flex items-center justify-center text-[10px] font-bold text-white bg-red-600 rounded-full px-1.5">
                {{ notifications.newUsersCount }}
              </span>
            </button>

            <!-- Dropdown -->
            <div v-if="newUsersDropdownOpen"
              class="absolute right-0 mt-2 w-72 bg-gray-900 border border-gray-800 rounded-lg shadow-2xl py-2 z-50">
              <div class="px-4 py-2 border-b border-gray-800 flex items-center justify-between">
                <span class="text-sm font-medium text-white">Nuevos usuarios</span>
                <button @click="clearNotifications" class="text-xs text-gray-400 hover:text-white transition">
                  Limpiar
                </button>
              </div>

              <div v-if="notifications.newUsers.length === 0" class="px-4 py-4 text-sm text-gray-500 text-center">
                Sin notificaciones
              </div>

              <div v-for="user in notifications.newUsers" :key="user.id"
                class="px-4 py-3 hover:bg-gray-800 transition flex items-center gap-3">
                <div
                  class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white text-sm font-bold shrink-0">
                  {{ user.name.charAt(0).toUpperCase() }}
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-white truncate">{{ user.name }}</p>
                  <p class="text-xs text-gray-400 truncate">{{ user.email }}</p>
                </div>
                <span class="text-xs text-gray-500 shrink-0">{{ user.time }}</span>
              </div>

              <div class="px-4 py-2 border-t border-gray-800 mt-1">
                <router-link to="/admin/users" @click="newUsersDropdownOpen = false"
                  class="text-xs text-blue-400 hover:text-blue-300 transition">
                  Ver todos los usuarios →
                </router-link>
              </div>
            </div>
          </div>

          <!-- Selector de idioma ✅ -->
          <div class="relative">
            <button @click="languageDropdownOpen = !languageDropdownOpen"
              class="flex items-center gap-2 px-2 py-1.5 rounded-md hover:bg-gray-800 transition">
              <span class="text-xl">{{ currentFlag }}</span>
              <span class="text-sm text-gray-300">{{ currentLang }}</span>
              <span class="text-gray-500 text-xs">▼</span>
            </button>

            <div v-if="languageDropdownOpen"
              class="absolute right-0 mt-2 w-40 bg-gray-900 border border-gray-800 rounded-lg shadow-2xl py-2 z-50 text-sm">
              <button @click="changeLanguage('en')"
                class="block w-full text-left px-4 py-2 text-gray-300 hover:bg-gray-800 transition"
                :class="{ 'text-blue-400': locale === 'en' }">
                English (EN) 🇺🇸
              </button>
              <button @click="changeLanguage('es')"
                class="block w-full text-left px-4 py-2 text-gray-300 hover:bg-gray-800 transition"
                :class="{ 'text-blue-400': locale === 'es' }">
                Español (ES) 🇲🇽
              </button>
            </div>
          </div>

          <!-- Avatar del usuario -->
          <div class="relative">
            <button @click="profileDropdownOpen = !profileDropdownOpen"
              class="flex items-center gap-3 focus:outline-none group">
              <div class="relative">
                <img :src="auth.user?.avatar ? `/storage/${auth.user.avatar}` : '/storage/avatars/default.png'"
                  alt="Avatar" class="w-8 h-8 rounded-full object-cover border border-gray-600 shadow-sm">
                <span
                  class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-emerald-500 rounded-full border-2 border-gray-950 shadow"></span>
              </div>

              <div class="hidden md:flex flex-col items-start">
                <span class="text-sm font-medium text-white group-hover:text-gray-200 transition">
                  {{ auth.userName || 'Usuario' }}
                </span>
                <span class="text-xs text-gray-500 capitalize">
                  {{ auth.userRoles[0]?.name || 'Invitado' }}
                </span>
              </div>

              <span class="text-gray-500 group-hover:text-gray-300 transition text-xs">▼</span>
            </button>

            <!-- Dropdown de perfil -->
            <div v-if="profileDropdownOpen"
              class="absolute right-0 mt-2 w-56 bg-gray-900 border border-gray-800 rounded-lg shadow-2xl py-1.5 z-50 text-sm">
              <div class="px-4 py-2 border-b border-gray-800">
                <p class="font-medium text-white">{{ auth.userName }}</p>
                <p class="text-xs text-gray-500">{{ auth.userEmail }}</p>
              </div>

              <router-link to="/profile"
                class="block px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                {{ t('nav.profile') }}
              </router-link>

              <router-link to="/settings"
                class="block px-4 py-2 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                {{ t('nav.settings') }}
              </router-link>

              <hr class="my-1 border-gray-800" />

              <button @click="handleLogout()"
                class="block w-full text-left px-4 py-2 text-red-400 hover:bg-gray-800 hover:text-red-300 transition">
                {{ t('nav.logout') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>