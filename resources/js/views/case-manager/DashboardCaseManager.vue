<script setup>
import { onMounted, onBeforeUnmount } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'

const auth  = useAuthStore()
const toast = useToast()

function playNotificationSound() {
  const audio = new Audio('/sounds/notification.mp3')
  audio.volume = 0.7
  audio.play().catch(() => {})
}

function formatDate(date) {
  if (!date) return ''
  const clean = String(date).slice(0, 10)
  const [y, m, d] = clean.split('-')
  return new Date(Number(y), Number(m) - 1, Number(d)).toLocaleDateString('es-ES', {
    day: '2-digit', month: 'short', year: 'numeric'
  })
}

function subscribeToChannel() {
  if (!window.Echo || !auth.user?.id) {
    setTimeout(subscribeToChannel, 500)
    return
  }
  window.Echo.private('case-manager.' + auth.user.id)
    .listen('.appointment.created', (data) => {
      console.log('Nueva cita recibida:', data)
      playNotificationSound()
      const date = formatDate(data.appointment_date)
      const time = data.start_time?.slice(0, 5)
      toast.success(
        '📅 Nueva cita: ' + data.title + ' | Cliente: ' + data.client?.name + ' | ' + date + ' ' + time,
        { timeout: 8000 }
      )
    })
}

onMounted(() => {
  subscribeToChannel()
})

onBeforeUnmount(() => {
  if (window.Echo && auth.user?.id) {
    window.Echo.leave('case-manager.' + auth.user.id)
  }
})
</script>

<template>
  <div class="min-h-screen bg-teal-700 text-white p-8">
    <div class="max-w-7xl mx-auto">
      <h1 class="text-5xl font-bold mb-6 text-center">
        Dashboard Case Manager
      </h1>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="bg-teal-800 p-8 rounded-2xl shadow-xl">
          <h2 class="text-3xl font-semibold mb-4">Turnos de Hoy</h2>
          <p class="text-6xl font-bold">0</p>
          <p class="mt-2 text-teal-200">Próximo turno: 10:00 AM</p>
        </div>

        <div class="bg-teal-800 p-8 rounded-2xl shadow-xl">
          <h2 class="text-3xl font-semibold mb-4">Clientes Atendidos</h2>
          <p class="text-6xl font-bold">0</p>
          <p class="mt-2 text-teal-200">Esta semana</p>
        </div>
      </div>

      <p class="mt-12 text-center text-teal-200 text-xl">
        ¡Bienvenido de nuevo, {{ auth.userName }}! Hora de brillar
      </p>
    </div>
  </div>
</template>