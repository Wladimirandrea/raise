<script setup>
import { ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const items = [
  { name: 'admin-dashboard',    path: '/admin/dashboard',     icon: '🏠',    label: 'Dashboard'     },
  { name: 'admin-users',        path: '/admin/users',         icon: '👥',    label: 'Usuarios'      },
  { name: 'admin-appointments', path: '/admin/appointments',  icon: '📅',    label: 'Turnos'        },
  { name: 'admin-case-managers',path: '/admin/case-managers', icon: '🧑‍💼', label: 'Case Managers' },
  { name: 'admin-settings',     path: '/admin/settings',      icon: '⚙️',    label: 'Config'        },
]

const activeIndex = ref(0)

// Sincroniza el índice activo con la ruta actual
watch(() => route.name, (name) => {
  const idx = items.findIndex(i => i.name === name)
  if (idx !== -1) activeIndex.value = idx
}, { immediate: true })

const navigate = (index) => {
  activeIndex.value = index
  router.push(items[index].path)
}

// Calcula la posición del indicador
const indicatorLeft = (index) => {
  const itemWidth = 100 / items.length
  return `calc(${itemWidth * index + itemWidth / 2}% - 45px)`
}
</script>

<template>
  <!-- Solo visible en mobile -->
  <div class="fixed bottom-0 left-0 right-0 z-50 lg:hidden px-4 pb-4">

    <!-- SVG Gooey filter -->
    <svg class="hidden" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <filter id="goo-mobile">
          <feGaussianBlur in="SourceGraphic" stdDeviation="10" result="blur" />
          <feColorMatrix in="blur" mode="matrix"
            values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 18 -7"
            result="goo" />
          <feBlend in="SourceGraphic" in2="goo" />
        </filter>
      </defs>
    </svg>

    <div class="nav-wrapper">
      <!-- Capa de fondo con gooey -->
      <div class="nav-bg">
        <!-- Indicador burbuja -->
        <div
          class="nav-indicator"
          :style="{ left: indicatorLeft(activeIndex) }"
        ></div>
      </div>

      <!-- Items -->
      <ul class="nav-items">
        <li
          v-for="(item, index) in items"
          :key="item.name"
          class="nav-item"
          @click="navigate(index)"
        >
          <div :class="['nav-link', { active: activeIndex === index }]">
            <span class="nav-icon">{{ item.icon }}</span>
            <span class="nav-label">{{ item.label }}</span>
          </div>
        </li>
      </ul>
    </div>

  </div>
</template>

<style scoped>
.nav-wrapper {
  position: relative;
  height: 70px;
  border-radius: 20px;
  overflow: visible;
}

/* Fondo con gooey */
.nav-bg {
  position: absolute;
  inset: 0;
  background-color: #1e40af;
  border-radius: 20px;
  filter: url('#goo-mobile');
  z-index: 0;
}

/* Burbuja indicadora */
.nav-indicator {
  position: absolute;
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background-color: #1e40af;
  top: -28px;
  transition: left 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  z-index: -1;
}

/* Items encima del fondo */
.nav-items {
  position: absolute;
  inset: 0;
  display: flex;
  list-style: none;
  z-index: 1;
  border-radius: 20px;
  overflow: hidden;
}

.nav-item {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.nav-link {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  color: #93c5fd;
  transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275), color 0.3s;
  gap: 2px;
}

.nav-link.active {
  color: #ffffff;
  transform: translateY(-22px);
}

.nav-icon {
  font-size: 22px;
  line-height: 1;
}

.nav-label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.5px;
  opacity: 0;
  transition: opacity 0.3s;
  white-space: nowrap;
}

.nav-link.active .nav-label {
  opacity: 1;
}

/* Asegura que no haya scroll en el body cuando el nav está visible */
@media (max-width: 767px) {
  :global(body) {
    padding-bottom: 90px;
  }
}
</style>