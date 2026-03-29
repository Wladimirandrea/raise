<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const loading = ref(true)
const clients = ref([])
const search  = ref('')

const loadClients = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/case-manager/clients')
    clients.value = data.clients
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

const filteredClients = () => {
  if (!search.value) return clients.value
  return clients.value.filter(c =>
    c.name.toLowerCase().includes(search.value.toLowerCase()) ||
    c.email.toLowerCase().includes(search.value.toLowerCase())
  )
}

const formatDate = (date) => {
  if (!date) return '—'
  const clean = String(date).slice(0, 10)
  const [y, m, d] = clean.split('-')
  return new Date(Number(y), Number(m) - 1, Number(d)).toLocaleDateString('es-ES', {
    day: '2-digit', month: 'short', year: 'numeric'
  })
}

onMounted(loadClients)
</script>

<template>
  <div class="mis-clientes">

    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">{{ t('cm.clients.title') }}</h1>
        <p class="page-subtitle">{{ t('cm.clients.subtitle') }}</p>
      </div>
      <span class="badge-total">{{ clients.length }} {{ t('cm.clients.total') }}</span>
    </div>

    <!-- Search -->
    <div class="search-bar">
      <span class="search-icon">🔍</span>
      <input
        v-model="search"
        type="text"
        :placeholder="t('cm.clients.search')"
        class="search-input"
      />
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
    </div>

    <!-- Empty -->
    <div v-else-if="clients.length === 0" class="empty-state">
      <div class="empty-icon">👥</div>
      <p>{{ t('cm.clients.empty') }}</p>
    </div>

    <!-- Grid -->
    <div v-else class="clients-grid">
      <div
        v-for="client in filteredClients()"
        :key="client.id"
        class="client-card"
      >
        <div class="client-card-header">
          <img
            :src="client.avatar ? `/storage/${client.avatar}` : '/storage/avatars/default.png'"
            class="client-avatar"
            :alt="client.name"
          />
          <div class="client-info">
            <h3 class="client-name">{{ client.name }}</h3>
            <p class="client-email">{{ client.email }}</p>
            <p class="client-phone">{{ client.phone || '—' }}</p>
          </div>
        </div>

        <div class="client-stats">
          <div class="client-stat">
            <span class="stat-value">{{ client.total_appointments || 0 }}</span>
            <span class="stat-label">{{ t('cm.clients.total_appts') }}</span>
          </div>
          <div class="client-stat">
            <span class="stat-value pending">{{ client.pending_appointments || 0 }}</span>
            <span class="stat-label">{{ t('appointments.status.pending') }}</span>
          </div>
        </div>

        <!-- Próxima cita -->
        <div v-if="client.appointments?.length > 0" class="next-appt">
          <span class="next-appt-label">📅 {{ t('cm.clients.next_appt') }}</span>
          <span class="next-appt-date">
            {{ formatDate(client.appointments[0].appointment_date) }}
            · {{ client.appointments[0].start_time?.slice(0, 5) }}
          </span>
        </div>
        <div v-else class="next-appt no-appt">
          <span>{{ t('cm.clients.no_next_appt') }}</span>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.mis-clientes { padding: 24px; max-width: 1400px; margin: 0 auto; font-family: 'Segoe UI', sans-serif; }
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.page-title { font-size: 1.6rem; font-weight: 700; color: #1e293b; margin: 0; }
.page-subtitle { font-size: 0.88rem; color: #64748b; margin: 4px 0 0; }
.badge-total { background: #f0fdfa; color: #0d9488; border-radius: 20px; padding: 4px 14px; font-size: 0.85rem; font-weight: 600; border: 1px solid #ccfbf1; }
.search-bar { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 16px; display: flex; align-items: center; gap: 10px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.05); }
.search-icon { font-size: 1rem; }
.search-input { border: none; outline: none; flex: 1; font-size: 0.9rem; color: #1e293b; background: transparent; }
.clients-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
.client-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,.06); overflow: hidden; transition: transform .2s, box-shadow .2s; }
.client-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.1); }
.client-card-header { display: flex; align-items: center; gap: 12px; padding: 16px; border-bottom: 1px solid #f1f5f9; }
.client-avatar { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; flex-shrink: 0; }
.client-info { flex: 1; min-width: 0; }
.client-name { font-size: 0.95rem; font-weight: 700; color: #1e293b; margin: 0 0 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.client-email { font-size: 0.75rem; color: #64748b; margin: 0 0 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.client-phone { font-size: 0.75rem; color: #94a3b8; margin: 0; }
.client-stats { display: flex; padding: 12px 16px; gap: 20px; border-bottom: 1px solid #f1f5f9; }
.client-stat { display: flex; flex-direction: column; align-items: center; flex: 1; }
.stat-value { font-size: 1.4rem; font-weight: 700; color: #0d9488; }
.stat-value.pending { color: #f59e0b; }
.stat-label { font-size: 0.7rem; color: #64748b; text-align: center; }
.next-appt { padding: 10px 16px; display: flex; align-items: center; justify-content: space-between; font-size: 0.78rem; }
.next-appt-label { color: #64748b; font-weight: 600; }
.next-appt-date { color: #0d9488; font-weight: 600; }
.no-appt { color: #94a3b8; justify-content: center; }
.loading-state { display: flex; justify-content: center; padding: 60px; }
.spinner { width: 36px; height: 36px; border: 3px solid #e2e8f0; border-top-color: #0d9488; border-radius: 50%; animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.empty-state { display: flex; flex-direction: column; align-items: center; padding: 60px; color: #94a3b8; gap: 12px; }
.empty-icon { font-size: 3rem; }
.empty-state p { font-size: 0.9rem; }
</style>