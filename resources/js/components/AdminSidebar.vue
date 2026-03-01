<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const isOpen = ref(true)

const toggle = () => {
  isOpen.value = !isOpen.value
}

const menuItems = [
  {
    label: 'sidebar.dashboard',
    icon: '🏠',
    to: '/admin/dashboard'
  },
  {
    label: 'sidebar.users',
    icon: '👥',
    to: '/admin/users'
  },
  {
    label: 'sidebar.schedules',
    icon: '🗓️',
    to: '/admin/schedules'
  },




  {
    label: 'sidebar.appointments',
    icon: '📅',
    to: '/admin/appointments'
  },
  {
    label: 'sidebar.casemanagers',
    icon: '🧑‍💼',
    to: '/admin/case-managers'
  },
  {
    label: 'sidebar.reports',
    icon: '📊',
    to: '/admin/reports'
  },
  {
    label: 'sidebar.settings',
    icon: '⚙️',
    to: '/admin/settings'
  },
]
</script>

<template>
  <aside :class="[
    'h-screen bg-gray-900 border-r border-gray-800 flex flex-col transition-all duration-300 ease-in-out',
    isOpen ? 'w-64' : 'w-16'
  ]">
    <!-- Botón toggle -->
    <div class="flex items-center justify-between px-4 py-4 border-b border-gray-800">
      <span v-if="isOpen" class="text-white font-semibold text-sm">Admin Panel</span>
      <button @click="toggle" class="p-1.5 rounded-md text-gray-400 hover:text-white hover:bg-gray-800 transition">
        <span class="text-lg">{{ isOpen ? '◀' : '▶' }}</span>
      </button>
    </div>

    <!-- Links -->
    <nav class="flex-1 py-4 overflow-y-auto">
      <router-link v-for="item in menuItems" :key="item.to" :to="item.to"
        class="flex items-center gap-3 px-4 py-3 text-gray-400 hover:text-white hover:bg-gray-800 transition-colors"
        active-class="bg-blue-600/20 text-blue-400 border-r-2 border-blue-500">
        <span class="text-xl flex-shrink-0">{{ item.icon }}</span>
        <span v-if="isOpen" class="text-sm font-medium whitespace-nowrap overflow-hidden transition-all duration-300">
          {{ t(item.label) }}
        </span>
      </router-link>
    </nav>

    <!-- Footer del sidebar -->
    <div v-if="isOpen" class="px-4 py-3 border-t border-gray-800">
      <p class="text-xs text-gray-600">Raise v1.0</p>
    </div>
  </aside>
</template>