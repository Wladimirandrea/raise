<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'

const { t }   = useI18n()
const auth    = useAuthStore()
const loading = ref(true)

const stats                = ref({ total: 0, pending: 0, confirmed: 0, completed: 0, cancelled: 0, clients: 0 })
const todayAppointments    = ref([])
const upcomingAppointments = ref([])

const loadDashboard = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/case-manager/dashboard')
    stats.value                = data.stats
    todayAppointments.value    = data.today_appointments
    upcomingAppointments.value = data.upcoming_appointments
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

const formatTime = (time) => time?.slice(0, 5) || ''

const formatDate = (date) => {
  if (!date) return ''
  const clean = String(date).slice(0, 10)
  const [y, m, d] = clean.split('-')
  return new Date(Number(y), Number(m) - 1, Number(d)).toLocaleDateString('es-ES', {
    weekday: 'short', day: '2-digit', month: 'short'
  })
}

const statusStyle = (status) => ({
  pending:   { bg: '#fef3c7', color: '#92400e' },
  confirmed: { bg: '#dbeafe', color: '#1e40af' },
  completed: { bg: '#d1fae5', color: '#065f46' },
  cancelled: { bg: '#fee2e2', color: '#991b1b' },
  no_show:   { bg: '#f1f5f9', color: '#475569' },
}[status] || { bg: '#f1f5f9', color: '#475569' })

onMounted(loadDashboard)
</script>

<template>
  <div class="dashboard-cm">

    <!-- Header -->
    <div class="dashboard-header">
      <div>
        <h1 class="dashboard-title">{{ t('cm.dashboard.title') }} 👋</h1>
        <p class="dashboard-subtitle">{{ t('cm.dashboard.subtitle', { name: auth.userName }) }}</p>
      </div>
      <div class="dashboard-date">
        {{ new Date().toLocaleDateString('es-ES', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' }) }}
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
    </div>

    <template v-else>

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card" style="border-left-color: #0d9488">
          <div class="stat-icon">📅</div>
          <div class="stat-info">
            <span class="stat-value">{{ stats.total }}</span>
            <span class="stat-label">{{ t('cm.dashboard.total_appointments') }}</span>
          </div>
        </div>
        <div class="stat-card" style="border-left-color: #f59e0b">
          <div class="stat-icon">⏳</div>
          <div class="stat-info">
            <span class="stat-value">{{ stats.pending }}</span>
            <span class="stat-label">{{ t('appointments.status.pending') }}</span>
          </div>
        </div>
        <div class="stat-card" style="border-left-color: #3b82f6">
          <div class="stat-icon">✅</div>
          <div class="stat-info">
            <span class="stat-value">{{ stats.confirmed }}</span>
            <span class="stat-label">{{ t('appointments.status.confirmed') }}</span>
          </div>
        </div>
        <div class="stat-card" style="border-left-color: #10b981">
          <div class="stat-icon">🎉</div>
          <div class="stat-info">
            <span class="stat-value">{{ stats.completed }}</span>
            <span class="stat-label">{{ t('appointments.status.completed') }}</span>
          </div>
        </div>
        <div class="stat-card" style="border-left-color: #8b5cf6">
          <div class="stat-icon">👥</div>
          <div class="stat-info">
            <span class="stat-value">{{ stats.clients }}</span>
            <span class="stat-label">{{ t('cm.dashboard.my_clients') }}</span>
          </div>
        </div>
      </div>

      <!-- Hoy + Próximas -->
      <div class="content-grid">

        <!-- Citas de hoy -->
        <div class="section-card">
          <div class="section-header">
            <h2 class="section-title">📅 {{ t('cm.dashboard.today') }}</h2>
            <span class="badge-count">{{ todayAppointments.length }}</span>
          </div>
          <div v-if="todayAppointments.length === 0" class="empty-state">
            <div class="empty-icon">🎉</div>
            <p>{{ t('cm.dashboard.no_today') }}</p>
          </div>
          <div v-else class="appointments-list">
            <div v-for="appt in todayAppointments" :key="appt.id" class="appt-item">
              <div class="appt-time">
                <span class="time-start">{{ formatTime(appt.start_time) }}</span>
                <span class="time-sep">—</span>
                <span class="time-end">{{ formatTime(appt.end_time) }}</span>
              </div>
              <div class="appt-info">
                <p class="appt-title">{{ appt.title }}</p>
                <p class="appt-client">
                  <img :src="appt.client?.avatar ? `/storage/${appt.client.avatar}` : '/storage/avatars/default.png'" class="client-avatar-xs" />
                  {{ appt.client?.name }}
                </p>
              </div>
              <span class="appt-status" :style="{ background: statusStyle(appt.status).bg, color: statusStyle(appt.status).color }">
                {{ t('appointments.status.' + appt.status) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Próximas citas -->
        <div class="section-card">
          <div class="section-header">
            <h2 class="section-title">🔜 {{ t('cm.dashboard.upcoming') }}</h2>
            <span class="badge-count">{{ upcomingAppointments.length }}</span>
          </div>
          <div v-if="upcomingAppointments.length === 0" class="empty-state">
            <div class="empty-icon">📭</div>
            <p>{{ t('cm.dashboard.no_upcoming') }}</p>
          </div>
          <div v-else class="appointments-list">
            <div v-for="appt in upcomingAppointments" :key="appt.id" class="appt-item">
              <div class="appt-date-col">
                <span class="appt-date">{{ formatDate(appt.appointment_date) }}</span>
                <span class="appt-time-small">{{ formatTime(appt.start_time) }}</span>
              </div>
              <div class="appt-info">
                <p class="appt-title">{{ appt.title }}</p>
                <p class="appt-client">
                  <img :src="appt.client?.avatar ? `/storage/${appt.client.avatar}` : '/storage/avatars/default.png'" class="client-avatar-xs" />
                  {{ appt.client?.name }}
                </p>
              </div>
              <span class="appt-status" :style="{ background: statusStyle(appt.status).bg, color: statusStyle(appt.status).color }">
                {{ t('appointments.status.' + appt.status) }}
              </span>
            </div>
          </div>
        </div>

      </div>
    </template>
  </div>
</template>

<style scoped>
.dashboard-cm { padding: 24px; max-width: 1400px; margin: 0 auto; font-family: 'Segoe UI', sans-serif; }
.dashboard-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
.dashboard-title { font-size: 1.6rem; font-weight: 700; color: #1e293b; margin: 0; }
.dashboard-subtitle { font-size: 0.88rem; color: #64748b; margin: 4px 0 0; }
.dashboard-date { font-size: 0.85rem; color: #64748b; background: #f0fdfa; padding: 8px 14px; border-radius: 8px; border: 1px solid #ccfbf1; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 24px; }
.stat-card { background: #fff; border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 14px; box-shadow: 0 1px 3px rgba(0,0,0,.06); border: 1px solid #e2e8f0; border-left: 4px solid; transition: transform .2s; }
.stat-card:hover { transform: translateY(-2px); }
.stat-icon { font-size: 1.8rem; }
.stat-info { display: flex; flex-direction: column; }
.stat-value { font-size: 1.6rem; font-weight: 700; color: #1e293b; line-height: 1; }
.stat-label { font-size: 0.75rem; color: #64748b; margin-top: 2px; }
.content-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media (max-width: 900px) { .content-grid { grid-template-columns: 1fr; } }
.section-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,.06); overflow: hidden; }
.section-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #f1f5f9; }
.section-title { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0; }
.badge-count { background: #f0fdfa; color: #0d9488; border-radius: 20px; padding: 2px 10px; font-size: 0.78rem; font-weight: 600; }
.appointments-list { padding: 8px 0; }
.appt-item { display: flex; align-items: center; gap: 12px; padding: 12px 20px; border-bottom: 1px solid #f8fafc; transition: background .15s; }
.appt-item:last-child { border-bottom: none; }
.appt-item:hover { background: #f8fafc; }
.appt-time { display: flex; flex-direction: column; align-items: center; min-width: 52px; }
.time-start { font-size: 0.9rem; font-weight: 700; color: #0f766e; }
.time-sep { font-size: 0.65rem; color: #94a3b8; }
.time-end { font-size: 0.75rem; color: #64748b; }
.appt-date-col { display: flex; flex-direction: column; min-width: 70px; }
.appt-date { font-size: 0.78rem; font-weight: 600; color: #0f766e; text-transform: capitalize; }
.appt-time-small { font-size: 0.72rem; color: #64748b; }
.appt-info { flex: 1; min-width: 0; }
.appt-title { font-size: 0.88rem; font-weight: 600; color: #1e293b; margin: 0 0 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.appt-client { display: flex; align-items: center; gap: 5px; font-size: 0.78rem; color: #64748b; margin: 0; }
.client-avatar-xs { width: 18px; height: 18px; border-radius: 50%; object-fit: cover; }
.appt-status { padding: 3px 8px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; white-space: nowrap; flex-shrink: 0; }
.empty-state { display: flex; flex-direction: column; align-items: center; padding: 32px; color: #94a3b8; gap: 8px; }
.empty-icon { font-size: 2rem; }
.empty-state p { font-size: 0.85rem; margin: 0; }
.loading-state { display: flex; justify-content: center; padding: 60px; }
.spinner { width: 36px; height: 36px; border: 3px solid #e2e8f0; border-top-color: #0d9488; border-radius: 50%; animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>