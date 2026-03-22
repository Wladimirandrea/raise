<script setup>
import { onMounted, onUnmounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'
import { Howl, Howler } from 'howler'
import Navbar from '@/components/Navbar.vue'
import AdminSidebar from '@/components/AdminSidebar.vue'
import { useNotificationsStore } from '@/stores/notifications'
import BottomNav from '@/components/BottomNav.vue'

const auth = useAuthStore()
const toast = useToast()
const notifications = useNotificationsStore()
const { t, locale } = useI18n()

let adminChannel       = null
let caseManagerChannel = null
let notificationSound  = null

const initSound = () => {
  if (!notificationSound) {
    notificationSound = new Howl({
      src: ['/sounds/notification.mp3'],
      volume: 1.0,
      preload: true,
      html5: false,
      onload: () => console.log('✅ Sonido cargado'),
      onloaderror: (id, err) => console.error('❌ Error cargando sonido:', err),
    })
  }
}

// ─── Helper fecha ─────────────────────────────────────────
function formatDate(date) {
  if (!date) return ''
  const clean = String(date).slice(0, 10)
  const [y, m, d] = clean.split('-')
  const loc = locale.value === 'en' ? 'en-GB' : 'es-ES'
  return new Date(Number(y), Number(m) - 1, Number(d)).toLocaleDateString(loc, {
    day: '2-digit', month: 'short', year: 'numeric'
  })
}

// ─── Canal Admin ──────────────────────────────────────────
const subscribeAdminChannel = () => {
  if (!window.Echo) {
    setTimeout(subscribeAdminChannel, 1000)
    return
  }
  if (adminChannel) return

  adminChannel = window.Echo.private('admin.notifications')
    .listen('.user.registered', (event) => {
      console.log('✅ Nuevo registro:', event)
      notifications.addUser({
        id: event.id, name: event.name, email: event.email, time: event.time,
      })
      toast.success(t('notifications.new_user', { name: event.name, email: event.email }), {
        position: 'top-right', timeout: 8000,
      })
      notificationSound?.play()
    })
    .listen('.appointment.created', (data) => {
      console.log('✅ Nueva cita (admin):', data)
      const time = data.start_time?.slice(0, 5)
      const date = formatDate(data.appointment_date)
      notifications.addAppointment({
        id:     data.id,
        title:  data.title,
        date:   data.appointment_date,
        time:   time,
        client: data.client?.name,
      })
      toast.info(
        `📅 ${t('appointments.new')}: ${data.title} · ${data.client?.name} · ${date} ${time}`,
        { position: 'top-right', timeout: 8000 }
      )
      notificationSound?.play()
    })
    .error((error) => console.error('❌ Error en canal admin.notifications:', error))
}

const unsubscribeAdminChannel = () => {
  if (adminChannel && window.Echo) {
    window.Echo.leave('admin.notifications')
    adminChannel = null
  }
}

// ─── Canal Case Manager ───────────────────────────────────
const subscribeCaseManagerChannel = () => {
  if (!window.Echo) {
    setTimeout(subscribeCaseManagerChannel, 1000)
    return
  }
  if (caseManagerChannel) return
  if (!auth.user?.id) return

  caseManagerChannel = window.Echo.private('case-manager.' + auth.user.id)
    .listen('.appointment.created', (data) => {
      console.log('✅ Nueva cita para case manager:', data)
      const time = data.start_time?.slice(0, 5)
      const date = formatDate(data.appointment_date)
      notifications.addAppointment({
        id:     data.id,
        title:  data.title,
        date:   data.appointment_date,
        time:   time,
        client: data.client?.name,
      })
      toast.success(
        `📅 ${t('appointments.new')}: ${data.title}\n${t('appointments.fields.client')}: ${data.client?.name} · ${date} ${time}`,
        { position: 'top-right', timeout: 8000 }
      )
      notificationSound?.play()
    })
    .error((error) => console.error('❌ Error en canal case-manager:', error))
}

const unsubscribeCaseManagerChannel = () => {
  if (caseManagerChannel && window.Echo) {
    window.Echo.leave('case-manager.' + auth.user?.id)
    caseManagerChannel = null
  }
}

// ─── Watchers ─────────────────────────────────────────────
watch(
  () => auth.isAuthenticated && auth.isAdmin,
  (isAdminAndAuth) => {
    if (isAdminAndAuth) subscribeAdminChannel()
    else unsubscribeAdminChannel()
  },
  { immediate: true }
)

watch(
  () => auth.isAuthenticated && auth.isCaseManager,
  (isCMAndAuth) => {
    if (isCMAndAuth) subscribeCaseManagerChannel()
    else unsubscribeCaseManagerChannel()
  },
  { immediate: true }
)

onMounted(() => {
  initSound()
  const unlock = () => {
    Howler.ctx?.resume()
    document.removeEventListener('click', unlock)
  }
  document.addEventListener('click', unlock, { once: true })
})

onUnmounted(() => {
  unsubscribeAdminChannel()
  unsubscribeCaseManagerChannel()
  notificationSound?.unload()
})
</script>

<template>
  <div class="min-h-screen flex flex-col bg-gray-50">
    <Navbar v-if="auth.isAuthenticated" />

    <div class="flex flex-1">
      <AdminSidebar v-if="auth.isAdmin" class="hidden lg:flex" />

      <main class="flex-grow flex flex-col min-h-0">
        <router-view />
      </main>
    </div>

    <BottomNav v-if="auth.isAdmin" />
  </div>
</template>