<template>
  <div class="appointments-page">

    <!-- ─── Header ──────────────────────────────────────────── -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Turnos / Citas</h1>
        <p class="page-subtitle">Gestiona las citas entre clientes y case managers</p>
      </div>
      <button class="btn-primary" @click="openCreatePanel">
        <span>＋</span> Nueva Cita
      </button>
    </div>

    <!-- ─── Filtros ──────────────────────────────────────────── -->
    <div class="filters-bar">
      <div class="filter-group">
        <label>Case Manager</label>
        <select v-model="filters.case_manager_id" @change="loadAppointments">
          <option value="">Todos</option>
          <option v-for="cm in caseManagers" :key="cm.id" :value="cm.id">{{ cm.name }}</option>
        </select>
      </div>
      <div class="filter-group">
        <label>Cliente</label>
        <select v-model="filters.client_id" @change="loadAppointments">
          <option value="">Todos</option>
          <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>
      <div class="filter-group">
        <label>Estado</label>
        <select v-model="filters.status" @change="loadAppointments">
          <option value="">Todos</option>
          <option value="pending">Pendiente</option>
          <option value="confirmed">Confirmada</option>
          <option value="cancelled">Cancelada</option>
          <option value="completed">Completada</option>
          <option value="no_show">No se presentó</option>
        </select>
      </div>
      <button class="btn-clear" @click="clearFilters">Limpiar filtros</button>
    </div>

    <!-- ─── Layout principal: Calendario + Panel lateral ─────── -->
    <div class="main-layout" :class="{ 'panel-open': showPanel }">

      <!-- Calendario -->
      <div class="calendar-col">
        <div class="calendar-card">
          <FullCalendar :options="calendarOptions" ref="calendarRef" />
        </div>
      </div>

      <!-- Panel lateral formulario -->
      <Transition name="slide-panel">
        <div v-if="showPanel" class="form-panel">
          <div class="panel-header">
            <div class="panel-title-group">
              <div class="panel-icon">📅</div>
              <h3>{{ isEditing ? 'Editar Cita' : 'Nueva Cita' }}</h3>
            </div>
            <button class="panel-close" @click="closePanel">✕</button>
          </div>

          <div class="panel-body">
            <form @submit.prevent="submitForm">

              <div class="form-group">
                <label>Case Manager *</label>
                <select v-model="form.case_manager_id" required>
                  <option value="">Seleccionar...</option>
                  <option v-for="cm in caseManagers" :key="cm.id" :value="cm.id">{{ cm.name }}</option>
                </select>
              </div>

              <div class="form-group">
                <label>Cliente *</label>
                <select v-model="form.client_id" required>
                  <option value="">Seleccionar...</option>
                  <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
              </div>

              <div class="form-group">
                <label>Motivo de la cita *</label>
                <input
                  v-model="form.title"
                  type="text"
                  placeholder="Ej: Evaluación inicial..."
                  required
                />
              </div>

              <div class="form-group">
                <label>Fecha *</label>
                <input v-model="form.appointment_date" type="date" required />
              </div>

              <div class="form-row-2">
                <div class="form-group">
                  <label>Hora inicio *</label>
                  <input v-model="form.start_time" type="time" required />
                </div>
                <div class="form-group">
                  <label>Hora fin *</label>
                  <input v-model="form.end_time" type="time" required />
                </div>
              </div>

              <div class="form-group">
                <label>Notas (opcional)</label>
                <textarea v-model="form.notes" rows="3" placeholder="Observaciones adicionales..."></textarea>
              </div>

              <!-- Estado solo al editar -->
              <div v-if="isEditing" class="form-group">
                <label>Estado</label>
                <div class="status-pills">
                  <button
                    v-for="opt in statusOptions"
                    type="button"
                    :key="opt.value"
                    class="status-pill"
                    :style="form.status === opt.value
                      ? { background: opt.color, borderColor: opt.color, color: '#fff' }
                      : { borderColor: opt.color, color: opt.color }"
                    @click="form.status = opt.value"
                  >
                    {{ opt.icon }} {{ opt.label }}
                  </button>
                </div>
              </div>

              <div v-if="formError" class="form-error">{{ formError }}</div>

              <div class="panel-actions">
                <button type="button" class="btn-secondary" @click="closePanel">Cancelar</button>
                <button type="submit" class="btn-primary" :disabled="submitting">
                  {{ submitting ? 'Guardando...' : (isEditing ? 'Guardar cambios' : 'Agendar cita') }}
                </button>
              </div>

            </form>
          </div>
        </div>
      </Transition>

    </div>

    <!-- ─── Tabla Lista ──────────────────────────────────────── -->
    <div class="table-card">
      <div class="table-header">
        <h2>Lista de Citas</h2>
        <span class="badge-count">{{ appointments.length }} citas</span>
      </div>

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <span>Cargando citas...</span>
      </div>

      <div v-else-if="appointments.length === 0" class="empty-state">
        <div class="empty-icon">📅</div>
        <p>No hay citas con los filtros seleccionados.</p>
      </div>

      <table v-else class="appointments-table">
        <thead>
          <tr>
            <th>Motivo</th>
            <th>Fecha</th>
            <th>Hora</th>
            <th>Case Manager</th>
            <th>Cliente</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="appt in appointments" :key="appt.id" :class="{ 'row-selected': selectedAppointment?.id === appt.id }">
            <td class="title-cell">{{ appt.title }}</td>
            <td>{{ formatDate(appt.appointment_date) }}</td>
            <td>{{ appt.start_time }} - {{ appt.end_time }}</td>
            <td>
              <div class="person-cell">
                <img :src="'/storage/' + appt.case_manager.avatar" class="avatar-sm" />
                <span>{{ appt.case_manager.name }}</span>
              </div>
            </td>
            <td>
              <div class="person-cell">
                <img :src="'/storage/' + appt.client.avatar" class="avatar-sm" />
                <span>{{ appt.client.name }}</span>
              </div>
            </td>
            <td>
              <span class="status-badge" :class="'status-' + appt.status">
                {{ statusLabel(appt.status) }}
              </span>
            </td>
            <td>
              <div class="action-buttons">
                <button class="btn-icon-action" @click="openEditPanel(appt)" title="Editar">✏️</button>
                <button class="btn-icon-action danger" @click="confirmDelete(appt)" title="Eliminar">🗑️</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ─── Modal Confirmar Eliminar ─────────────────────────── -->
    <Transition name="fade">
      <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
        <div class="modal-box">
          <div class="modal-header">
            <h3>Eliminar Cita</h3>
            <button class="modal-close" @click="showDeleteModal = false">✕</button>
          </div>
          <p class="delete-message">
            ¿Estás seguro que deseas eliminar la cita <strong>{{ selectedAppointment?.title }}</strong>?
            Se notificará por email al case manager y al cliente.
          </p>
          <div class="modal-footer">
            <button class="btn-secondary" @click="showDeleteModal = false">Cancelar</button>
            <button class="btn-danger" @click="deleteAppointment" :disabled="submitting">
              {{ submitting ? 'Eliminando...' : 'Sí, eliminar' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

  </div>
</template>

<script>
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'
import esLocale from '@fullcalendar/core/locales/es'
import axios from 'axios'

export default {
  name: 'AppointmentsAdmin',
  components: { FullCalendar },

  data() {
    return {
      appointments: [],
      caseManagers: [],
      clients: [],
      loading: false,

      filters: { case_manager_id: '', client_id: '', status: '' },

      showPanel: false,
      isEditing: false,
      selectedAppointment: null,
      submitting: false,
      formError: '',
      showDeleteModal: false,

      form: {
        case_manager_id: '',
        client_id: '',
        title: '',
        appointment_date: '',
        start_time: '08:00',
        end_time: '09:00',
        status: 'pending',
        notes: '',
      },

      statusOptions: [
        { value: 'pending',   label: 'Pendiente',      icon: '⏳', color: '#f59e0b' },
        { value: 'confirmed', label: 'Confirmada',      icon: '✅', color: '#3b82f6' },
        { value: 'completed', label: 'Completada',      icon: '🎉', color: '#10b981' },
        { value: 'cancelled', label: 'Cancelada',       icon: '❌', color: '#ef4444' },
        { value: 'no_show',   label: 'No se presentó', icon: '👻', color: '#6b7280' },
      ],

      calendarOptions: {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        locale: esLocale,
        initialView: 'dayGridMonth',
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,timeGridDay',
        },
        events: [],
        eventClick: this.handleEventClick,
        dateClick: this.handleDateClick,
        eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        height: 'auto',
        dayMaxEvents: 3,
      },
    }
  },

  async mounted() {
    await this.loadFormData()
    await this.loadAppointments()
  },

  methods: {
    async loadFormData() {
      try {
        const { data } = await axios.get('/api/admin/appointments/form-data')
        this.caseManagers = data.case_managers
        this.clients = data.clients
      } catch (e) { console.error(e) }
    },

    async loadAppointments() {
      this.loading = true
      try {
        const { data } = await axios.get('/api/admin/appointments', { params: this.filters })
        this.appointments = data.appointments
        this.calendarOptions = { ...this.calendarOptions, events: data.events }
      } catch (e) { console.error(e) }
      finally { this.loading = false }
    },

    clearFilters() {
      this.filters = { case_manager_id: '', client_id: '', status: '' }
      this.loadAppointments()
    },

    // ─── Panel ────────────────────────────────────────────────
    openCreatePanel(date = null) {
      this.isEditing = false
      this.formError = ''
      this.selectedAppointment = null
      this.form = {
        case_manager_id: '', client_id: '', title: '',
        appointment_date: date || '',
        start_time: '08:00', end_time: '09:00',
        status: 'pending', notes: '',
      }
      this.showPanel = true
    },

    openEditPanel(appt) {
      this.isEditing = true
      this.formError = ''
      this.selectedAppointment = appt
      this.form = {
        case_manager_id: appt.case_manager_id,
        client_id: appt.client_id,
        title: appt.title,
        appointment_date: appt.appointment_date,
        start_time: appt.start_time,
        end_time: appt.end_time,
        status: appt.status,
        notes: appt.notes || '',
      }
      this.showPanel = true
    },

    closePanel() {
      this.showPanel = false
      this.formError = ''
      this.selectedAppointment = null
    },

    // ─── CRUD ─────────────────────────────────────────────────
    async submitForm() {
      this.submitting = true
      this.formError = ''
      try {
        if (this.isEditing) {
          await axios.put(`/api/admin/appointments/${this.selectedAppointment.id}`, this.form)
        } else {
          await axios.post('/api/admin/appointments', this.form)
        }
        this.closePanel()
        await this.loadAppointments()
      } catch (e) {
        this.formError = e.response?.data?.message || 'Error al guardar la cita.'
      } finally {
        this.submitting = false
      }
    },

    confirmDelete(appt) {
      this.selectedAppointment = appt
      this.showDeleteModal = true
    },

    async deleteAppointment() {
      this.submitting = true
      try {
        await axios.delete(`/api/admin/appointments/${this.selectedAppointment.id}`)
        this.showDeleteModal = false
        this.closePanel()
        await this.loadAppointments()
      } catch (e) { console.error(e) }
      finally { this.submitting = false }
    },

    // ─── Calendar ─────────────────────────────────────────────
    handleEventClick({ event }) {
      const appt = this.appointments.find(a => a.id == event.id)
      if (appt) this.openEditPanel(appt)
    },

    handleDateClick({ dateStr }) {
      this.openCreatePanel(dateStr)
    },

    // ─── Helpers ──────────────────────────────────────────────
    formatDate(date) {
      if (!date) return ''
      return new Date(date + 'T00:00:00').toLocaleDateString('es-ES', {
        day: '2-digit', month: 'short', year: 'numeric'
      })
    },

    statusLabel(status) {
      return this.statusOptions.find(s => s.value === status)?.label || status
    },
  },
}
</script>

<style scoped>
.appointments-page {
  padding: 24px;
  max-width: 1500px;
  margin: 0 auto;
  font-family: 'Segoe UI', sans-serif;
  color: #1e293b;
}

/* ─── Header ──────────────────────────────────────────────── */
.page-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 20px;
}
.page-title { font-size: 1.75rem; font-weight: 700; margin: 0; color: #1e293b; }
.page-subtitle { color: #64748b; margin: 4px 0 0; font-size: 0.88rem; }

/* ─── Botones ─────────────────────────────────────────────── */
.btn-primary {
  background: #3b82f6; color: #fff; border: none;
  border-radius: 8px; padding: 10px 20px;
  font-size: 0.9rem; font-weight: 600; cursor: pointer;
  display: flex; align-items: center; gap: 6px;
  transition: background .2s;
}
.btn-primary:hover { background: #1e40af; }
.btn-primary:disabled { opacity: .6; cursor: not-allowed; }

.btn-secondary {
  background: #fff; color: #1e293b;
  border: 1px solid #e2e8f0; border-radius: 8px;
  padding: 10px 20px; font-size: 0.9rem; cursor: pointer;
  transition: background .2s;
}
.btn-secondary:hover { background: #f8fafc; }

.btn-danger {
  background: #ef4444; color: #fff; border: none;
  border-radius: 8px; padding: 10px 20px;
  font-size: 0.9rem; font-weight: 600; cursor: pointer;
}
.btn-danger:hover { background: #dc2626; }
.btn-danger:disabled { opacity: .6; }

.btn-clear {
  background: transparent; border: 1px dashed #e2e8f0;
  border-radius: 8px; padding: 8px 16px;
  color: #64748b; cursor: pointer; font-size: 0.85rem;
  align-self: flex-end; transition: all .2s;
}
.btn-clear:hover { border-color: #94a3b8; color: #1e293b; }

/* ─── Filtros ─────────────────────────────────────────────── */
.filters-bar {
  background: #fff; border-radius: 12px; border: 1px solid #e2e8f0;
  padding: 16px 20px; display: flex; gap: 16px;
  align-items: flex-end; flex-wrap: wrap; margin-bottom: 20px;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
.filter-group { display: flex; flex-direction: column; gap: 6px; flex: 1; min-width: 160px; }
.filter-group label { font-size: 0.78rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .5px; }
.filter-group select {
  border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px;
  font-size: 0.9rem; color: #1e293b; background: #fff; outline: none; transition: border-color .2s;
}
.filter-group select:focus { border-color: #3b82f6; }

/* ─── Layout principal ────────────────────────────────────── */
.main-layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: 20px;
  margin-bottom: 24px;
  transition: grid-template-columns .35s ease;
}
.main-layout.panel-open {
  grid-template-columns: 1fr 360px;
}

/* ─── Calendario ──────────────────────────────────────────── */
.calendar-col { min-width: 0; }
.calendar-card {
  background: #fff; border-radius: 12px; border: 1px solid #e2e8f0;
  padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
:deep(.fc-toolbar-title) { font-size: 1.1rem !important; font-weight: 700; color: #1e293b; }
:deep(.fc-button) {
  background: #3b82f6 !important; border-color: #3b82f6 !important;
  border-radius: 6px !important; font-size: 0.8rem !important; padding: 6px 12px !important;
}
:deep(.fc-button:hover) { background: #1e40af !important; border-color: #1e40af !important; }
:deep(.fc-button-active) { background: #1e40af !important; border-color: #1e40af !important; }
:deep(.fc-daygrid-event) { border-radius: 4px !important; font-size: 0.78rem !important; }
:deep(.fc-col-header-cell) { background: #f8fafc; font-size: 0.82rem; font-weight: 600; }
:deep(.fc-day-today) { background: #eff6ff !important; }

/* ─── Panel lateral ───────────────────────────────────────── */
.form-panel {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 20px rgba(0,0,0,.08);
  display: flex;
  flex-direction: column;
  height: fit-content;
  position: sticky;
  top: 20px;
  overflow: hidden;
}

.panel-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 18px 20px;
  background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
  color: #fff;
}
.panel-title-group { display: flex; align-items: center; gap: 10px; }
.panel-icon {
  background: rgba(255,255,255,.2); border-radius: 8px;
  width: 34px; height: 34px;
  display: flex; align-items: center; justify-content: center; font-size: 1rem;
}
.panel-header h3 { font-size: 1rem; font-weight: 700; margin: 0; }
.panel-close {
  background: rgba(255,255,255,.15); border: none; color: #fff;
  border-radius: 6px; width: 28px; height: 28px; font-size: 0.85rem;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  transition: background .2s;
}
.panel-close:hover { background: rgba(255,255,255,.3); }

.panel-body { padding: 20px; overflow-y: auto; max-height: calc(100vh - 200px); }

/* ─── Form ────────────────────────────────────────────────── */
.form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; }
.form-group label { font-size: 0.78rem; font-weight: 600; color: #64748b; }
.form-group input,
.form-group select,
.form-group textarea {
  border: 1px solid #e2e8f0; border-radius: 8px; padding: 9px 12px;
  font-size: 0.88rem; color: #1e293b; font-family: inherit; outline: none;
  transition: border-color .2s, box-shadow .2s;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59,130,246,.1);
}
.form-row-2 { display: flex; gap: 12px; }
.form-row-2 .form-group { flex: 1; }

.form-error {
  background: #fee2e2; color: #991b1b; padding: 10px 14px;
  border-radius: 8px; font-size: 0.82rem; margin-bottom: 14px;
}

/* ─── Status pills ────────────────────────────────────────── */
.status-pills { display: flex; flex-wrap: wrap; gap: 6px; }
.status-pill {
  border: 2px solid; border-radius: 20px; padding: 4px 10px;
  font-size: 0.76rem; font-weight: 600; cursor: pointer;
  background: transparent; transition: all .2s;
}
.status-pill:hover { opacity: .8; }

/* ─── Panel actions ───────────────────────────────────────── */
.panel-actions {
  display: flex; gap: 10px; padding-top: 12px;
  border-top: 1px solid #f1f5f9; margin-top: 4px;
}
.panel-actions .btn-primary,
.panel-actions .btn-secondary { flex: 1; justify-content: center; }

/* ─── Tabla ───────────────────────────────────────────────── */
.table-card {
  background: #fff; border-radius: 12px; border: 1px solid #e2e8f0;
  box-shadow: 0 1px 3px rgba(0,0,0,.06); overflow: hidden;
}
.table-header {
  display: flex; align-items: center; gap: 12px;
  padding: 18px 20px; border-bottom: 1px solid #e2e8f0;
}
.table-header h2 { font-size: 1rem; font-weight: 700; margin: 0; }
.badge-count {
  background: #eff6ff; color: #3b82f6; border-radius: 20px;
  padding: 2px 10px; font-size: 0.78rem; font-weight: 600;
}
.appointments-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.appointments-table th {
  background: #f8fafc; padding: 12px 16px; text-align: left;
  font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .5px; color: #64748b; border-bottom: 1px solid #e2e8f0;
}
.appointments-table td { padding: 13px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.appointments-table tr:last-child td { border-bottom: none; }
.appointments-table tr:hover td { background: #fafbff; }
.row-selected td { background: #eff6ff !important; }
.title-cell { font-weight: 600; }
.person-cell { display: flex; align-items: center; gap: 8px; }
.avatar-sm { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; }

.status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.74rem; font-weight: 600; }
.status-pending   { background: #fef3c7; color: #92400e; }
.status-confirmed { background: #dbeafe; color: #1e40af; }
.status-completed { background: #d1fae5; color: #065f46; }
.status-cancelled { background: #fee2e2; color: #991b1b; }
.status-no_show   { background: #f1f5f9; color: #475569; }

.action-buttons { display: flex; gap: 6px; }
.btn-icon-action {
  background: none; border: 1px solid #e2e8f0; border-radius: 6px;
  padding: 5px 8px; font-size: 0.85rem; cursor: pointer; transition: all .2s;
}
.btn-icon-action:hover { background: #f1f5f9; transform: scale(1.05); }
.btn-icon-action.danger:hover { background: #fee2e2; border-color: #fca5a5; }

/* ─── Loading / Empty ─────────────────────────────────────── */
.loading-state, .empty-state {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center; gap: 12px; padding: 48px; color: #64748b;
}
.spinner {
  width: 32px; height: 32px; border: 3px solid #e2e8f0;
  border-top-color: #3b82f6; border-radius: 50%;
  animation: spin .7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.empty-icon { font-size: 2.5rem; }

/* ─── Modal eliminar ──────────────────────────────────────── */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(15,23,42,.5);
  backdrop-filter: blur(2px); display: flex; align-items: center;
  justify-content: center; z-index: 9999; padding: 20px;
}
.modal-box {
  background: #fff; border-radius: 16px; width: 100%; max-width: 420px;
  box-shadow: 0 20px 60px rgba(0,0,0,.15); overflow: hidden;
}
.modal-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 18px 24px; border-bottom: 1px solid #e2e8f0; background: #f8fafc;
}
.modal-header h3 { font-size: 1rem; font-weight: 700; margin: 0; }
.modal-close {
  background: none; border: none; font-size: 1rem; color: #64748b;
  cursor: pointer; padding: 4px 8px; border-radius: 4px; transition: all .2s;
}
.modal-close:hover { background: #e2e8f0; }
.delete-message { padding: 20px 24px; color: #64748b; font-size: 0.9rem; line-height: 1.6; margin: 0; }
.delete-message strong { color: #1e293b; }
.modal-footer { display: flex; justify-content: flex-end; gap: 12px; padding: 0 24px 20px; }

/* ─── Transitions ─────────────────────────────────────────── */
.slide-panel-enter-active { transition: all .3s ease; }
.slide-panel-leave-active { transition: all .25s ease; }
.slide-panel-enter-from   { opacity: 0; transform: translateX(30px); }
.slide-panel-leave-to     { opacity: 0; transform: translateX(30px); }

.fade-enter-active, .fade-leave-active { transition: opacity .25s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>