<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import esLocale from '@fullcalendar/core/locales/es'
import enLocale from '@fullcalendar/core/locales/en-gb'
import axios from 'axios'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()
const loading      = ref(true)
const appointments = ref([])
const selectedAppt = ref(null)
const filters      = ref({ status: '' })
const saving       = ref(false)
const editStatus   = ref('')
const saveError    = ref('')
const saveSuccess  = ref('')

const calendarOptions = ref({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  locale: esLocale,
  initialView: 'dayGridMonth',
  headerToolbar: {
    left:   'prev,next today',
    center: 'title',
    right:  'dayGridMonth,timeGridWeek,timeGridDay',
  },
  events: [],
  eventClick: handleEventClick,
  eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
  height: 'auto',
  dayMaxEvents: 3,
})

function handleEventClick({ event }) {
  const appt = appointments.value.find(a => a.id == event.id)
  if (appt) {
    selectedAppt.value = appt
    editStatus.value   = appt.status
    saveError.value    = ''
    saveSuccess.value  = ''
  }
}

const statusOptions = [
  { value: 'confirmed', icon: '✅', color: '#3b82f6' },
  { value: 'completed', icon: '🎉', color: '#10b981' },
  { value: 'cancelled', icon: '❌', color: '#ef4444' },
  { value: 'no_show',   icon: '👻', color: '#6b7280' },
]

const statusStyle = (status) => ({
  pending:   { bg: '#fef3c7', color: '#92400e' },
  confirmed: { bg: '#dbeafe', color: '#1e40af' },
  completed: { bg: '#d1fae5', color: '#065f46' },
  cancelled: { bg: '#fee2e2', color: '#991b1b' },
  no_show:   { bg: '#f1f5f9', color: '#475569' },
}[status] || { bg: '#f1f5f9', color: '#475569' })

const formatDate = (date) => {
  if (!date) return ''
  const clean = String(date).slice(0, 10)
  const [y, m, d] = clean.split('-')
  return new Date(Number(y), Number(m) - 1, Number(d)).toLocaleDateString('es-ES', {
    weekday: 'long', day: '2-digit', month: 'long', year: 'numeric'
  })
}

const loadAppointments = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/case-manager/appointments', { params: filters.value })
    appointments.value = data.appointments
    calendarOptions.value = {
      ...calendarOptions.value,
      events: data.events,
      locale: locale.value === 'en' ? enLocale : esLocale,
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

const updateStatus = async () => {
  if (!selectedAppt.value || editStatus.value === selectedAppt.value.status) return
  saving.value     = true
  saveError.value  = ''
  saveSuccess.value = ''
  try {
    await axios.patch(`/case-manager/appointments/${selectedAppt.value.id}/status`, {
      status: editStatus.value
    })
    selectedAppt.value.status = editStatus.value
    saveSuccess.value = '✅ Estado actualizado correctamente.'
    await loadAppointments()
    setTimeout(() => saveSuccess.value = '', 3000)
  } catch (e) {
    saveError.value = e.response?.data?.message || 'Error al actualizar el estado.'
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadAppointments()
  window.addEventListener('appointment:created', loadAppointments)
})

onBeforeUnmount(() => {
  window.removeEventListener('appointment:created', loadAppointments)
})
</script>

<template>
  <div class="mis-citas">

    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">{{ t('cm.nav.appointments') }}</h1>
        <p class="page-subtitle">{{ t('appointments.subtitle') }}</p>
      </div>
      <div class="filter-group">
        <select v-model="filters.status" @change="loadAppointments" class="filter-select">
          <option value="">{{ t('appointments.filters.all_status') }}</option>
          <option value="pending">{{ t('appointments.status.pending') }}</option>
          <option value="confirmed">{{ t('appointments.status.confirmed') }}</option>
          <option value="completed">{{ t('appointments.status.completed') }}</option>
          <option value="cancelled">{{ t('appointments.status.cancelled') }}</option>
          <option value="no_show">{{ t('appointments.status.no_show') }}</option>
        </select>
      </div>
    </div>

    <!-- Layout -->
    <div class="citas-layout" :class="{ 'detail-open': selectedAppt }">

      <!-- Calendario -->
      <div class="calendar-col">
        <div class="calendar-card">
          <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
          </div>
          <FullCalendar v-else :options="calendarOptions" />
        </div>
      </div>

      <!-- Panel detalle + edición -->
      <Transition name="slide-panel">
        <div v-if="selectedAppt" class="detail-panel">
          <div class="detail-header">
            <h3>📅 {{ t('appointments.edit') }}</h3>
            <button class="detail-close" @click="selectedAppt = null">✕</button>
          </div>

          <div class="detail-body">

            <!-- Info de la cita -->
            <div class="detail-row">
              <span class="detail-label">{{ t('appointments.fields.title') }}</span>
              <span class="detail-value">{{ selectedAppt.title }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">{{ t('appointments.fields.client') }}</span>
              <div class="detail-client">
                <img
                  :src="selectedAppt.client?.avatar ? `/storage/${selectedAppt.client.avatar}` : '/storage/avatars/default.png'"
                  class="detail-avatar"
                />
                <span>{{ selectedAppt.client?.name }}</span>
              </div>
            </div>
            <div class="detail-row">
              <span class="detail-label">{{ t('appointments.fields.date') }}</span>
              <span class="detail-value">{{ formatDate(selectedAppt.appointment_date) }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">{{ t('appointments.fields.start_time') }}</span>
              <span class="detail-value">
                {{ selectedAppt.start_time?.slice(0,5) }} – {{ selectedAppt.end_time?.slice(0,5) }}
              </span>
            </div>
            <div v-if="selectedAppt.notes" class="detail-row">
              <span class="detail-label">{{ t('appointments.fields.notes') }}</span>
              <span class="detail-value notes">{{ selectedAppt.notes }}</span>
            </div>

            <!-- Editar estado -->
            <div class="status-edit-section">
              <span class="detail-label">{{ t('appointments.fields.status') }}</span>

              <!-- Estado actual -->
              <span
                class="status-badge current-status"
                :style="{ background: statusStyle(selectedAppt.status).bg, color: statusStyle(selectedAppt.status).color }"
              >
                {{ t('appointments.status.' + selectedAppt.status) }}
              </span>

              <!-- Pills de estado -->
              <div class="status-pills">
                <button
                  v-for="opt in statusOptions"
                  :key="opt.value"
                  class="status-pill"
                  :class="{ selected: editStatus === opt.value }"
                  :style="editStatus === opt.value
                    ? { background: opt.color, borderColor: opt.color, color: '#fff' }
                    : { borderColor: opt.color, color: opt.color }"
                  @click="editStatus = opt.value"
                >
                  {{ opt.icon }} {{ t('appointments.status.' + opt.value) }}
                </button>
              </div>

              <!-- Alerts -->
              <div v-if="saveSuccess" class="alert-success">{{ saveSuccess }}</div>
              <div v-if="saveError" class="alert-error">{{ saveError }}</div>

              <!-- Botón guardar -->
              <button
                class="btn-save"
                :disabled="saving || editStatus === selectedAppt.status"
                @click="updateStatus"
              >
                {{ saving ? t('users.saving') : t('appointments.actions.update') }}
              </button>
            </div>

          </div>
        </div>
      </Transition>

    </div>
  </div>
</template>

<style scoped>
.mis-citas { padding: 24px; max-width: 1400px; margin: 0 auto; font-family: 'Segoe UI', sans-serif; }
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.page-title { font-size: 1.6rem; font-weight: 700; color: #1e293b; margin: 0; }
.page-subtitle { font-size: 0.88rem; color: #64748b; margin: 4px 0 0; }
.filter-select { border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; font-size: 0.9rem; outline: none; }
.filter-select:focus { border-color: #0d9488; }

.citas-layout { display: grid; grid-template-columns: 1fr; gap: 20px; transition: grid-template-columns .3s; }
.citas-layout.detail-open { grid-template-columns: 1fr 340px; }
.calendar-col { min-width: 0; }
.calendar-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
:deep(.fc-button) { background: #0d9488 !important; border-color: #0d9488 !important; border-radius: 6px !important; font-size: 0.8rem !important; }
:deep(.fc-button:hover) { background: #0f766e !important; border-color: #0f766e !important; }
:deep(.fc-button-active) { background: #0f766e !important; border-color: #0f766e !important; }
:deep(.fc-day-today) { background: #f0fdfa !important; }
:deep(.fc-daygrid-event) { border-radius: 4px !important; font-size: 0.78rem !important; }

.detail-panel { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,.08); height: fit-content; position: sticky; top: 20px; overflow: hidden; }
.detail-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; background: linear-gradient(135deg, #0f766e, #0d9488); color: #fff; }
.detail-header h3 { font-size: 1rem; font-weight: 700; margin: 0; }
.detail-close { background: rgba(255,255,255,.15); border: none; color: #fff; border-radius: 6px; width: 28px; height: 28px; cursor: pointer; font-size: 1rem; }
.detail-body { padding: 16px; display: flex; flex-direction: column; gap: 14px; }
.detail-row { display: flex; flex-direction: column; gap: 4px; }
.detail-label { font-size: 0.72rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; }
.detail-value { font-size: 0.9rem; color: #1e293b; font-weight: 500; }
.detail-value.notes { font-size: 0.85rem; color: #64748b; line-height: 1.5; }
.detail-client { display: flex; align-items: center; gap: 8px; }
.detail-avatar { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; }

/* Status edit */
.status-edit-section { display: flex; flex-direction: column; gap: 10px; padding-top: 14px; border-top: 1px solid #f1f5f9; }
.status-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; width: fit-content; }
.current-status { font-size: 0.82rem; }
.status-pills { display: flex; flex-wrap: wrap; gap: 6px; }
.status-pill { border: 2px solid; border-radius: 20px; padding: 5px 10px; font-size: 0.76rem; font-weight: 600; cursor: pointer; background: transparent; transition: all .2s; }
.status-pill:hover { opacity: .8; }
.status-pill.selected { font-weight: 700; }

.btn-save { background: #0d9488; color: #fff; border: none; border-radius: 8px; padding: 10px; font-size: 0.88rem; font-weight: 600; cursor: pointer; transition: background .2s; width: 100%; }
.btn-save:hover { background: #0f766e; }
.btn-save:disabled { opacity: .5; cursor: not-allowed; }

.alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 8px 12px; border-radius: 8px; font-size: 0.82rem; }
.alert-error { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 8px 12px; border-radius: 8px; font-size: 0.82rem; }

.loading-state { display: flex; justify-content: center; padding: 60px; }
.spinner { width: 36px; height: 36px; border: 3px solid #e2e8f0; border-top-color: #0d9488; border-radius: 50%; animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.slide-panel-enter-active { transition: all .3s ease; }
.slide-panel-leave-active { transition: all .25s ease; }
.slide-panel-enter-from, .slide-panel-leave-to { opacity: 0; transform: translateX(30px); }
</style>