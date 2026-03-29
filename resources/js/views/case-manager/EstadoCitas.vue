<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const loading = ref(true)
const appointments = ref([])
const selectedAppt = ref(null)
const saving = ref(false)
const editStatus = ref('')
const saveError = ref('')
const saveSuccess = ref('')

const statusOptions = computed(() => [
  { value: 'confirmed', icon: '✅', color: '#3b82f6', label: locale.value === 'en' ? 'Confirmed' : 'Confirmada' },
  { value: 'completed', icon: '🎉', color: '#10b981', label: locale.value === 'en' ? 'Completed' : 'Completada' },
  { value: 'cancelled', icon: '❌', color: '#ef4444', label: locale.value === 'en' ? 'Cancelled' : 'Cancelada' },
  { value: 'no_show', icon: '👻', color: '#6b7280', label: locale.value === 'en' ? 'No show' : 'No asistió' },
])

const statusConfig = computed(() => ({
  pending: { bg: '#fef3c7', color: '#92400e', border: '#fde68a', icon: '⏳', label: locale.value === 'en' ? 'Pending' : 'Pendientes' },
  confirmed: { bg: '#dbeafe', color: '#1e40af', border: '#93c5fd', icon: '✅', label: locale.value === 'en' ? 'Confirmed' : 'Confirmadas' },
  completed: { bg: '#d1fae5', color: '#065f46', border: '#6ee7b7', icon: '🎉', label: locale.value === 'en' ? 'Completed' : 'Completadas' },
  cancelled: { bg: '#fee2e2', color: '#991b1b', border: '#fca5a5', icon: '❌', label: locale.value === 'en' ? 'Cancelled' : 'Canceladas' },
}))

const pending = computed(() => appointments.value.filter(a => a.status === 'pending'))
const confirmed = computed(() => appointments.value.filter(a => a.status === 'confirmed'))
const completed = computed(() => appointments.value.filter(a => a.status === 'completed'))
const cancelled = computed(() => appointments.value.filter(a => a.status === 'cancelled'))

const sections = computed(() => [
  { key: 'pending', list: pending.value },
  { key: 'confirmed', list: confirmed.value },
  { key: 'completed', list: completed.value },
  { key: 'cancelled', list: cancelled.value },
])

const formatDate = (date) => {
  if (!date) return ''
  const clean = String(date).slice(0, 10)
  const [y, m, d] = clean.split('-')
  return new Date(Number(y), Number(m) - 1, Number(d)).toLocaleDateString(
    locale.value === 'en' ? 'en-US' : 'es-ES',
    { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' }
  )
}

const openDetail = (appt) => {
  selectedAppt.value = appt
  editStatus.value = appt.status
  saveError.value = ''
  saveSuccess.value = ''
}

const loadAppointments = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/case-manager/appointments')
    appointments.value = data.appointments
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

const updateStatus = async () => {
  if (!selectedAppt.value || editStatus.value === selectedAppt.value.status) return
  saving.value = true
  saveError.value = ''
  saveSuccess.value = ''
  try {
    await axios.patch(`/case-manager/appointments/${selectedAppt.value.id}/status`, {
      status: editStatus.value
    })
    selectedAppt.value.status = editStatus.value
    saveSuccess.value = '✅ Estado actualizado correctamente.'
    await loadAppointments()
    setTimeout(() => {
      saveSuccess.value = ''
      selectedAppt.value = null
    }, 1800)
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
  <div class="appointments-page">

    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">📋 {{ t('cm.nav.appointments') }}</h1>
        <p class="page-subtitle">{{ locale === 'en' ? 'Appointment management by status' : 'Gestión de citas por estado'
        }}</p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
    </div>

    <!-- Layout principal -->
    <div v-else class="main-layout" :class="{ 'panel-open': selectedAppt }">

      <!-- Tablas -->
      <div class="tables-col">

        <div v-for="section in sections" :key="section.key" class="section-block">
          <!-- Título de sección -->
          <div class="section-header" :style="{ borderLeftColor: statusConfig[section.key].color }">
            <span class="section-icon">{{ statusConfig[section.key].icon }}</span>
            <span class="section-title">{{ statusConfig[section.key].label }}</span>
            <span class="section-badge"
              :style="{ background: statusConfig[section.key].bg, color: statusConfig[section.key].color }">
              {{ section.list.length }}
            </span>
          </div>

          <!-- Tabla -->
          <div class="table-wrapper">
            <table v-if="section.list.length" class="appt-table">
              <thead>
                <tr>
                  <th>{{ locale === 'en' ? 'Client' : 'Cliente' }}</th>
                  <th>{{ locale === 'en' ? 'Reason' : 'Motivo' }}</th>
                  <th>{{ locale === 'en' ? 'Date' : 'Fecha' }}</th>
                  <th>{{ locale === 'en' ? 'Schedule' : 'Horario' }}</th>
                  <th>{{ locale === 'en' ? 'Notes' : 'Notas' }}</th>
                  <th>{{ locale === 'en' ? 'Actions' : 'Acciones' }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="appt in section.list" :key="appt.id" class="appt-row"
                  :class="{ 'row-selected': selectedAppt?.id === appt.id }">
                  <!-- Cliente -->
                  <td>
                    <div class="client-cell">
                      <img
                        :src="appt.client?.avatar ? `/storage/${appt.client.avatar}` : '/storage/avatars/default.png'"
                        class="client-avatar" alt="" />
                      <span class="client-name">{{ appt.client?.name }}</span>
                    </div>
                  </td>

                  <!-- Motivo -->
                  <td>
                    <span class="title-cell">{{ appt.title }}</span>
                  </td>

                  <!-- Fecha -->
                  <td>
                    <span class="date-cell">{{ formatDate(appt.appointment_date) }}</span>
                  </td>

                  <!-- Horario -->
                  <td>
                    <span class="time-badge">
                      {{ appt.start_time?.slice(0, 5) }} – {{ appt.end_time?.slice(0, 5) }}
                    </span>
                  </td>

                  <!-- Notas -->
                  <td>
                    <span class="notes-cell" :title="appt.notes">
                      {{ appt.notes ? appt.notes.slice(0, 40) + (appt.notes.length > 40 ? '…' : '') : '—' }}
                    </span>
                  </td>

                  <!-- Acciones -->
                  <td>
                    <button class="btn-edit" @click="openDetail(appt)"
                      :class="{ active: selectedAppt?.id === appt.id }">
                      ✏️ {{ locale === 'en' ? 'Edit' : 'Editar' }}
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- Estado vacío -->
            <div v-else class="empty-state">
              <span class="empty-icon">{{ statusConfig[section.key].icon }}</span>
              <<span>{{ locale === 'en' ? 'No ' + statusConfig[section.key].label.toLowerCase() + ' appointments' : 'No hay citas ' + statusConfig[section.key].label.toLowerCase() }}</span>
            </div>
          </div>
        </div>

      </div>

      <!-- Panel lateral de edición -->
      <Transition name="slide-panel">
        <div v-if="selectedAppt" class="detail-panel">
          <div class="detail-header">
            <h3>✏️ {{ locale === 'en' ? 'Edit status' : 'Editar estado' }}</h3>
            <button class="detail-close" @click="selectedAppt = null">✕</button>
          </div>

          <div class="detail-body">

            <div class="detail-row">
              <span class="detail-label">{{ locale === 'en' ? 'Client' : 'Cliente' }}</span>
              <div class="detail-client">
                <img
                  :src="selectedAppt.client?.avatar ? `/storage/${selectedAppt.client.avatar}` : '/storage/avatars/default.png'"
                  class="detail-avatar" />
                <span class="detail-value">{{ selectedAppt.client?.name }}</span>
              </div>
            </div>

            <div class="detail-row">
              <span class="detail-label">{{ locale === 'en' ? 'Reason' : 'Motivo' }}</span>
              <span class="detail-value">{{ selectedAppt.title }}</span>
            </div>

            <div class="detail-row">
              <span class="detail-label">{{ locale === 'en' ? 'Date' : 'Fecha' }}</span>
              <span class="detail-value">{{ formatDate(selectedAppt.appointment_date) }}</span>
            </div>

            <div class="detail-row">
              <span class="detail-label">{{ locale === 'en' ? 'Schedule' : 'Horario' }}</span>
              <span class="detail-value">
                {{ selectedAppt.start_time?.slice(0, 5) }} – {{ selectedAppt.end_time?.slice(0, 5) }}
              </span>
            </div>

            <div v-if="selectedAppt.notes" class="detail-row">
              <span class="detail-label">{{ locale === 'en' ? 'Notes' : 'Notas' }}</span>
              <span class="detail-value notes">{{ selectedAppt.notes }}</span>
            </div>

            <!-- Cambiar estado -->
            <div class="status-edit-section">
              <span class="detail-label">{{ locale === 'en' ? 'Current status' : 'Estado actual' }}</span>
              <span class="status-badge" :style="{
                background: statusConfig[selectedAppt.status]?.bg,
                color: statusConfig[selectedAppt.status]?.color
              }">
                {{ statusConfig[selectedAppt.status]?.icon }} {{ statusConfig[selectedAppt.status]?.label }}
              </span>

              <span class="detail-label" style="margin-top: 6px;">{{ locale === 'en' ? 'Change to' : 'Cambiar a'
                }}</span>
              <div class="status-pills">
                <button v-for="opt in statusOptions" :key="opt.value" class="status-pill"
                  :class="{ selected: editStatus === opt.value }" :style="editStatus === opt.value
                    ? { background: opt.color, borderColor: opt.color, color: '#fff' }
                    : { borderColor: opt.color, color: opt.color }" @click="editStatus = opt.value">
                  {{ opt.icon }} {{ opt.label }}
                </button>
              </div>

              <div v-if="saveSuccess" class="alert-success">{{ saveSuccess }}</div>
              <div v-if="saveError" class="alert-error">{{ saveError }}</div>

              <button class="btn-save" :disabled="saving || editStatus === selectedAppt.status" @click="updateStatus">
                {{ saving ? (locale === 'en' ? 'Saving…' : 'Guardando…') : (locale === 'en' ? 'Save change' : 'Guardar cambio') }}
              </button>
            </div>

          </div>
        </div>
      </Transition>

    </div>
  </div>
</template>

<style scoped>
.appointments-page {
  padding: 24px;
  max-width: 1400px;
  margin: 0 auto;
  font-family: 'Segoe UI', sans-serif;
}

/* Header */
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 12px;
}

.page-title {
  font-size: 1.6rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.page-subtitle {
  font-size: 0.88rem;
  color: #64748b;
  margin: 4px 0 0;
}

/* Loading */
.loading-state {
  display: flex;
  justify-content: center;
  padding: 80px;
}

.spinner {
  width: 36px;
  height: 36px;
  border: 3px solid #e2e8f0;
  border-top-color: #0d9488;
  border-radius: 50%;
  animation: spin .7s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Layout */
.main-layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: 20px;
  transition: grid-template-columns .3s;
}

.main-layout.panel-open {
  grid-template-columns: 1fr 340px;
  align-items: start;
}

.tables-col {
  display: flex;
  flex-direction: column;
  gap: 24px;
  min-width: 0;
}

/* Sección */
.section-block {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, .06);
}

.section-header {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 20px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  border-left: 4px solid;
}

.section-icon {
  font-size: 1.1rem;
}

.section-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1e293b;
}

.section-badge {
  margin-left: auto;
  padding: 2px 10px;
  border-radius: 20px;
  font-size: 0.78rem;
  font-weight: 700;
}

/* Tabla */
.table-wrapper {
  overflow-x: auto;
}

.appt-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.appt-table thead tr {
  background: #f8fafc;
}

.appt-table th {
  padding: 10px 16px;
  text-align: left;
  font-size: 0.72rem;
  font-weight: 700;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: .5px;
  border-bottom: 1px solid #e2e8f0;
  white-space: nowrap;
}

.appt-table td {
  padding: 12px 16px;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.appt-row {
  transition: background .15s;
}

.appt-row:hover {
  background: #f8fafc;
}

.appt-row:last-child td {
  border-bottom: none;
}

.appt-row.row-selected {
  background: #f0fdfa;
}

/* Celdas */
.client-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.client-avatar {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}

.client-name {
  font-weight: 600;
  color: #1e293b;
  white-space: nowrap;
}

.title-cell {
  color: #334155;
  font-weight: 500;
}

.date-cell {
  color: #475569;
  white-space: nowrap;
}

.time-badge {
  display: inline-block;
  background: #dbeafe;
  color: #1e40af;
  border-radius: 20px;
  padding: 3px 10px;
  font-size: 0.78rem;
  font-weight: 600;
  white-space: nowrap;
}

.notes-cell {
  color: #94a3b8;
  font-size: 0.82rem;
}

/* Botón editar */
.btn-edit {
  background: #f0fdfa;
  color: #0d9488;
  border: 1px solid #99f6e4;
  border-radius: 7px;
  padding: 5px 12px;
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
  transition: all .15s;
  white-space: nowrap;
}

.btn-edit:hover {
  background: #0d9488;
  color: #fff;
  border-color: #0d9488;
}

.btn-edit.active {
  background: #0d9488;
  color: #fff;
  border-color: #0d9488;
}

/* Estado vacío */
.empty-state {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 20px 20px;
  color: #94a3b8;
  font-size: 0.875rem;
}

.empty-icon {
  font-size: 1.2rem;
}

/* Panel lateral */
.detail-panel {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 20px rgba(0, 0, 0, .08);
  height: fit-content;
  position: sticky;
  top: 20px;
  overflow: hidden;
}

.detail-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  background: linear-gradient(135deg, #0f766e, #0d9488);
  color: #fff;
}

.detail-header h3 {
  font-size: 1rem;
  font-weight: 700;
  margin: 0;
}

.detail-close {
  background: rgba(255, 255, 255, .15);
  border: none;
  color: #fff;
  border-radius: 6px;
  width: 28px;
  height: 28px;
  cursor: pointer;
  font-size: 1rem;
  transition: background .15s;
}

.detail-close:hover {
  background: rgba(255, 255, 255, .3);
}

.detail-body {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.detail-row {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.detail-label {
  font-size: 0.72rem;
  font-weight: 700;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: .5px;
}

.detail-value {
  font-size: 0.9rem;
  color: #1e293b;
  font-weight: 500;
}

.detail-value.notes {
  font-size: 0.85rem;
  color: #64748b;
  line-height: 1.5;
}

.detail-client {
  display: flex;
  align-items: center;
  gap: 8px;
}

.detail-avatar {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  object-fit: cover;
}

/* Status edit */
.status-edit-section {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding-top: 14px;
  border-top: 1px solid #f1f5f9;
}

.status-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.82rem;
  font-weight: 600;
  width: fit-content;
}

.status-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.status-pill {
  border: 2px solid;
  border-radius: 20px;
  padding: 5px 10px;
  font-size: 0.76rem;
  font-weight: 600;
  cursor: pointer;
  background: transparent;
  transition: all .2s;
}

.status-pill:hover {
  opacity: .8;
}

.status-pill.selected {
  font-weight: 700;
}

.btn-save {
  background: #0d9488;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 10px;
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
  transition: background .2s;
  width: 100%;
  margin-top: 4px;
}

.btn-save:hover:not(:disabled) {
  background: #0f766e;
}

.btn-save:disabled {
  opacity: .5;
  cursor: not-allowed;
}

.alert-success {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #166534;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 0.82rem;
}

.alert-error {
  background: #fee2e2;
  border: 1px solid #fca5a5;
  color: #991b1b;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 0.82rem;
}

/* Transición panel */
.slide-panel-enter-active {
  transition: all .3s ease;
}

.slide-panel-leave-active {
  transition: all .25s ease;
}

.slide-panel-enter-from,
.slide-panel-leave-to {
  opacity: 0;
  transform: translateX(30px);
}

/* Responsive */
@media (max-width: 768px) {
  .main-layout.panel-open {
    grid-template-columns: 1fr;
  }

  .detail-panel {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    border-radius: 16px 16px 0 0;
    z-index: 50;
    max-height: 85vh;
    overflow-y: auto;
  }
}
</style>