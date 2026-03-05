<template>
  <div class="appointments-page">

    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('appointments.title') }}</h1>
        <p class="page-subtitle">{{ $t('appointments.subtitle') }}</p>
      </div>
      <button class="btn-primary" @click="openCreatePanel">
        <span>+</span> {{ $t('appointments.new') }}
      </button>
    </div>

    <!-- Filtros -->
    <div class="filters-bar">
      <div class="filter-group">
        <label>{{ $t('appointments.fields.case_manager') }}</label>
        <select v-model="filters.case_manager_id" @change="loadAppointments">
          <option value="">{{ $t('appointments.filters.all_managers') }}</option>
          <option v-for="cm in caseManagers" :key="cm.id" :value="cm.id">{{ cm.name }}</option>
        </select>
      </div>
      <div class="filter-group">
        <label>{{ $t('appointments.fields.status') }}</label>
        <select v-model="filters.status" @change="loadAppointments">
          <option value="">{{ $t('appointments.filters.all_status') }}</option>
          <option value="pending">{{ $t('appointments.status.pending') }}</option>
          <option value="confirmed">{{ $t('appointments.status.confirmed') }}</option>
          <option value="completed">{{ $t('appointments.status.completed') }}</option>
          <option value="cancelled">{{ $t('appointments.status.cancelled') }}</option>
          <option value="no_show">{{ $t('appointments.status.no_show') }}</option>
        </select>
      </div>
      <button class="btn-clear" @click="clearFilters">{{ $t('appointments.filters.clear') }}</button>
    </div>

    <!-- Layout: Calendario + Panel -->
    <div class="main-layout" :class="{ 'panel-open': showPanel }">

      <!-- FullCalendar -->
      <div class="calendar-col">
        <div class="calendar-card">
          <FullCalendar :options="calendarOptions" ref="calendarRef" />
        </div>
      </div>

      <!-- Panel lateral -->
      <Transition name="slide-panel">
        <div v-if="showPanel" class="form-panel">

          <div class="panel-header">
            <div class="panel-title-group">
              <div class="panel-icon">📅</div>
              <h3>{{ isEditing ? $t('appointments.edit') : $t('appointments.new') }}</h3>
            </div>
            <button class="panel-close" @click="closePanel">✕</button>
          </div>

          <div class="panel-body">

            <!-- ═══ CREAR: 5 pasos ═══ -->
            <template v-if="!isEditing">

              <!-- Paso 1: Case Manager -->
              <div class="step" :class="{ completed: form.case_manager_id }">
                <div class="step-label">
                  <span class="step-number">1</span>
                  {{ $t('appointments.fields.case_manager') }}
                </div>
                <select v-model="form.case_manager_id" @change="onCaseManagerChange" class="step-select">
                  <option value="">{{ $t('appointments.placeholders.select') }}</option>
                  <option v-for="cm in caseManagers" :key="cm.id" :value="cm.id">{{ cm.name }}</option>
                </select>
              </div>

              <!-- Paso 2: Cliente -->
              <Transition name="fade-step">
                <div v-if="form.case_manager_id" class="step" :class="{ completed: form.client_id }">
                  <div class="step-label">
                    <span class="step-number">2</span>
                    {{ $t('appointments.fields.client') }}
                  </div>
                  <div v-if="loadingClients" class="step-loading">
                    <div class="spinner-sm"></div> Cargando...
                  </div>
                  <div v-else-if="clients.length === 0" class="step-empty">
                    Sin clientes asignados a este case manager.
                  </div>
                  <div v-else class="clients-grid">
                    <div
                      v-for="client in clients"
                      :key="client.id"
                      class="client-chip"
                      :class="{ selected: form.client_id === client.id }"
                      @click="form.client_id = client.id"
                    >
                      <img :src="'/storage/' + client.avatar" class="chip-avatar" />
                      <span>{{ client.name }}</span>
                    </div>
                  </div>
                </div>
              </Transition>

              <!-- Paso 3: Fecha -->
              <Transition name="fade-step">
                <div v-if="form.client_id" class="step" :class="{ completed: form.appointment_date }">
                  <div class="step-label">
                    <span class="step-number">3</span>
                    {{ $t('appointments.fields.date') }}
                  </div>
                  <div class="mini-calendar">
                    <div class="mini-cal-header">
                      <button class="cal-nav" @click="prevMonth">&#8249;</button>
                      <span class="cal-month-title">{{ calendarMonthTitle }}</span>
                      <button class="cal-nav" @click="nextMonth">&#8250;</button>
                    </div>
                    <div class="mini-cal-weekdays">
                      <span v-for="d in weekDays" :key="d">{{ d }}</span>
                    </div>
                    <div class="mini-cal-days">
                      <span
                        v-for="day in calendarDays"
                        :key="day.key"
                        class="cal-day"
                        :class="{
                          today: day.isToday,
                          selected: day.dateStr === form.appointment_date,
                          disabled: day.disabled,
                          available: day.available,
                        }"
                        @click="day.available && selectDate(day.dateStr)"
                      >{{ day.day }}</span>
                    </div>
                  </div>
                </div>
              </Transition>

              <!-- Paso 4: Slots -->
              <Transition name="fade-step">
                <div v-if="form.appointment_date" class="step" :class="{ completed: form.start_time }">
                  <div class="step-label">
                    <span class="step-number">4</span>
                    Horario disponible
                  </div>
                  <div v-if="loadingSlots" class="step-loading">
                    <div class="spinner-sm"></div> Cargando slots...
                  </div>
                  <div v-else-if="!slotsData.available" class="step-empty">
                    {{ slotsData.message || 'No hay horario para ese día.' }}
                  </div>
                  <div v-else-if="slotsData.slots.length === 0" class="step-empty">
                    No hay slots disponibles ese día.
                  </div>
                  <div v-else class="slots-grid">
                    <button
                      v-for="slot in slotsData.slots"
                      :key="slot.start"
                      class="slot-btn"
                      :class="{ selected: form.start_time === slot.start }"
                      @click="form.start_time = slot.start"
                    >{{ slot.label }}</button>
                  </div>
                </div>
              </Transition>

              <!-- Paso 5: Motivo + confirmar -->
              <Transition name="fade-step">
                <div v-if="form.start_time" class="step">
                  <div class="step-label">
                    <span class="step-number">5</span>
                    {{ $t('appointments.fields.title') }}
                  </div>
                  <input
                    v-model="form.title"
                    type="text"
                    class="step-input"
                    :placeholder="$t('appointments.placeholders.title')"
                  />
                  <textarea
                    v-model="form.notes"
                    class="step-textarea"
                    rows="2"
                    :placeholder="$t('appointments.placeholders.notes')"
                  ></textarea>

                  <!-- Resumen de la cita -->
                  <div class="appointment-summary" v-if="form.title">
                    <div class="summary-row">
                      <span>📅</span>
                      <span>{{ formatDate(form.appointment_date) }} · {{ form.start_time }} – {{ endTimePreview }}</span>
                    </div>
                    <div class="summary-row">
                      <span>👤</span>
                      <span>{{ selectedCMName }} → {{ selectedClientName }}</span>
                    </div>
                  </div>

                  <div v-if="formError" class="form-error">{{ formError }}</div>

                  <div class="panel-actions">
                    <button type="button" class="btn-secondary" @click="closePanel">
                      {{ $t('appointments.actions.cancel') }}
                    </button>
                    <button class="btn-primary" :disabled="submitting || !form.title" @click="submitForm">
                      {{ submitting ? $t('appointments.actions.saving') : $t('appointments.actions.save') }}
                    </button>
                  </div>
                </div>
              </Transition>

            </template>

            <!-- ═══ EDITAR cita existente ═══ -->
            <template v-else>

              <div class="edit-summary">
                <div class="edit-row">
                  <span class="edit-label">{{ $t('appointments.fields.case_manager') }}</span>
                  <span>{{ selectedAppointment?.case_manager?.name }}</span>
                </div>
                <div class="edit-row">
                  <span class="edit-label">{{ $t('appointments.fields.client') }}</span>
                  <span>{{ selectedAppointment?.client?.name }}</span>
                </div>
                <div class="edit-row">
                  <span class="edit-label">{{ $t('appointments.fields.date') }}</span>
                  <span>{{ formatDate(selectedAppointment?.appointment_date) }}</span>
                </div>
                <div class="edit-row">
                  <span class="edit-label">Horario</span>
                  <span>{{ selectedAppointment?.start_time?.slice(0,5) }} – {{ selectedAppointment?.end_time?.slice(0,5) }}</span>
                </div>
              </div>

              <div class="form-group">
                <label>{{ $t('appointments.fields.title') }}</label>
                <input v-model="form.title" type="text" class="step-input" />
              </div>

              <div class="form-group">
                <label>{{ $t('appointments.fields.status') }}</label>
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
                  >{{ opt.icon }} {{ $t('appointments.status.' + opt.value) }}</button>
                </div>
              </div>

              <div class="form-group">
                <label>{{ $t('appointments.fields.notes') }}</label>
                <textarea v-model="form.notes" class="step-textarea" rows="3"
                  :placeholder="$t('appointments.placeholders.notes')"></textarea>
              </div>

              <div v-if="formError" class="form-error">{{ formError }}</div>

              <div class="panel-actions">
                <button class="btn-danger-outline" @click="confirmDelete(selectedAppointment)">
                  🗑️ {{ $t('appointments.actions.delete') }}
                </button>
                <button class="btn-primary" :disabled="submitting" @click="submitForm">
                  {{ submitting ? $t('appointments.actions.saving') : $t('appointments.actions.update') }}
                </button>
              </div>

            </template>

          </div>
        </div>
      </Transition>

    </div>

    <!-- Tabla lista -->
    <div class="table-card">
      <div class="table-header">
        <h2>{{ $t('appointments.list_title') }}</h2>
        <span class="badge-count">{{ appointments.length }} {{ $t('appointments.list_title').toLowerCase() }}</span>
      </div>

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <span>{{ $t('appointments.loading') }}</span>
      </div>

      <div v-else-if="appointments.length === 0" class="empty-state">
        <div class="empty-icon">📅</div>
        <p>{{ $t('appointments.empty') }}</p>
      </div>

      <table v-else class="appointments-table">
        <thead>
          <tr>
            <th>{{ $t('appointments.fields.title') }}</th>
            <th>{{ $t('appointments.fields.date') }}</th>
            <th>Horario</th>
            <th>{{ $t('appointments.fields.case_manager') }}</th>
            <th>{{ $t('appointments.fields.client') }}</th>
            <th>{{ $t('appointments.fields.status') }}</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="appt in appointments"
            :key="appt.id"
            :class="{ 'row-selected': selectedAppointment?.id === appt.id }"
          >
            <td class="title-cell">{{ appt.title }}</td>
            <td>{{ formatDate(appt.appointment_date) }}</td>
            <td class="time-cell">{{ appt.start_time.slice(0,5) }} – {{ appt.end_time.slice(0,5) }}</td>
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
                {{ $t('appointments.status.' + appt.status) }}
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

    <!-- Modal eliminar -->
    <Transition name="fade">
      <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
        <div class="modal-box">
          <div class="modal-header">
            <h3>Eliminar Cita</h3>
            <button class="modal-close" @click="showDeleteModal = false">✕</button>
          </div>
          <p class="delete-message">
            {{ $t('appointments.actions.confirm_delete', { title: selectedAppointment?.title }) }}
          </p>
          <div class="modal-footer">
            <button class="btn-secondary" @click="showDeleteModal = false">{{ $t('appointments.actions.cancel') }}</button>
            <button class="btn-danger" @click="deleteAppointment" :disabled="submitting">
              {{ submitting ? $t('appointments.actions.deleting') : $t('appointments.actions.yes_delete') }}
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
import { useAuthStore } from '@/stores/auth.js'

export default {
  name: 'AppointmentsAdmin',
  components: { FullCalendar },

  data() {
    return {
      appointments: [],
      caseManagers: [],
      clients: [],
      loading: false,
      loadingClients: false,
      loadingSlots: false,

      filters: { case_manager_id: '', status: '' },

      showPanel: false,
      isEditing: false,
      selectedAppointment: null,
      submitting: false,
      formError: '',
      showDeleteModal: false,

      // Mini calendario
      currentMonth: new Date(),
      activeDays: [],
      slotsData: { available: false, slots: [], booked: [] },

      form: {
        case_manager_id: '',
        client_id: '',
        appointment_date: '',
        start_time: '',
        title: '',
        notes: '',
        status: 'pending',
      },

      statusOptions: [
        { value: 'pending',   icon: '⏳', color: '#f59e0b' },
        { value: 'confirmed', icon: '✅', color: '#3b82f6' },
        { value: 'completed', icon: '🎉', color: '#10b981' },
        { value: 'cancelled', icon: '❌', color: '#ef4444' },
        { value: 'no_show',   icon: '👻', color: '#6b7280' },
      ],

      weekDays: ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'],

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
        eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        height: 'auto',
        dayMaxEvents: 3,
      },
    }
  },

  computed: {
    calendarMonthTitle() {
      const months = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                      'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre']
      return months[this.currentMonth.getMonth()] + ' ' + this.currentMonth.getFullYear()
    },

    calendarDays() {
      const year  = this.currentMonth.getFullYear()
      const month = this.currentMonth.getMonth()
      const today = new Date()
      today.setHours(0, 0, 0, 0)

      const days     = []
      const firstDow = new Date(year, month, 1).getDay()
      const lastDay  = new Date(year, month + 1, 0).getDate()

      // blancos antes del primer día
      for (let i = 0; i < firstDow; i++) {
        days.push({ key: 'b' + i, date: null, day: '', dateStr: '', isToday: false, disabled: true, available: false })
      }

      for (let d = 1; d <= lastDay; d++) {
        const date    = new Date(year, month, d)
        const dateStr = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0')
        const isPast  = date < today
        const isAct   = this.activeDays.includes(date.getDay())

        days.push({
          key:       dateStr,
          date:      date,
          day:       d,
          dateStr:   dateStr,
          isToday:   date.getTime() === today.getTime(),
          disabled:  isPast || !isAct,
          available: !isPast && isAct,
        })
      }

      return days
    },

    endTimePreview() {
      if (!this.form.start_time) return ''
      const [h, m] = this.form.start_time.split(':').map(Number)
      const total  = h * 60 + m + 30
      return String(Math.floor(total / 60)).padStart(2, '0') + ':' + String(total % 60).padStart(2, '0')
    },

    selectedCMName() {
      return this.caseManagers.find(cm => cm.id === this.form.case_manager_id)?.name || ''
    },

    selectedClientName() {
      return this.clients.find(c => c.id === this.form.client_id)?.name || ''
    },
  },

  async mounted() {
    await this.loadFormData()
    await this.loadAppointments()
    await this.loadActiveDays()
    this.subscribeToNotifications()
  },

  beforeUnmount() {
    const authStore = useAuthStore()
    if (window.Echo && authStore.user?.id) {
      window.Echo.leave('case-manager.' + authStore.user.id)
    }
  },

  methods: {

    // ─── Cargar datos ─────────────────────────────────────────
    async loadFormData() {
      try {
        const { data } = await axios.get('/admin/appointments/form-data')
        this.caseManagers = data.case_managers
      } catch (e) { console.error(e) }
    },

    async loadAppointments() {
      this.loading = true
      try {
        const { data } = await axios.get('/admin/appointments', { params: this.filters })
        this.appointments = data.appointments
        this.calendarOptions = { ...this.calendarOptions, events: data.events }
      } catch (e) { console.error(e) }
      finally { this.loading = false }
    },

    async loadActiveDays() {
      try {
        const { data } = await axios.get('/admin/schedules')
        this.activeDays = data
          .filter(s => s.is_active)
          .map(s => Number(s.day_of_week))
      } catch (e) { console.error(e) }
    },

    clearFilters() {
      this.filters = { case_manager_id: '', status: '' }
      this.loadAppointments()
    },

    // ─── Panel ────────────────────────────────────────────────
    openCreatePanel() {
      this.isEditing = false
      this.formError = ''
      this.selectedAppointment = null
      this.clients = []
      this.slotsData = { available: false, slots: [], booked: [] }
      this.form = { case_manager_id: '', client_id: '', appointment_date: '', start_time: '', title: '', notes: '', status: 'pending' }
      this.currentMonth = new Date()
      this.showPanel = true
    },

    openEditPanel(appt) {
      this.isEditing = true
      this.formError = ''
      this.selectedAppointment = appt
      this.form = {
        case_manager_id: appt.case_manager_id,
        client_id:        appt.client_id,
        appointment_date: appt.appointment_date,
        start_time:       appt.start_time?.slice(0, 5),
        title:            appt.title,
        notes:            appt.notes || '',
        status:           appt.status,
      }
      this.showPanel = true
    },

    closePanel() {
      this.showPanel = false
      this.formError = ''
      this.selectedAppointment = null
    },

    // ─── Pasos del formulario ─────────────────────────────────
    async onCaseManagerChange() {
      this.form.client_id = ''
      this.form.appointment_date = ''
      this.form.start_time = ''
      this.clients = []
      this.slotsData = { available: false, slots: [], booked: [] }
      if (!this.form.case_manager_id) return

      this.loadingClients = true
      try {
        const { data } = await axios.get('/admin/appointments/clients-by-manager', {
          params: { case_manager_id: this.form.case_manager_id }
        })
        this.clients = data.clients
      } catch (e) { console.error(e) }
      finally { this.loadingClients = false }
    },

    async selectDate(dateStr) {
      this.form.appointment_date = dateStr
      this.form.start_time = ''
      this.slotsData = { available: false, slots: [], booked: [] }
      this.loadingSlots = true
      try {
        const { data } = await axios.get('/admin/appointments/available-slots', {
          params: { case_manager_id: this.form.case_manager_id, date: dateStr }
        })
        this.slotsData = data
      } catch (e) { console.error(e) }
      finally { this.loadingSlots = false }
    },

    prevMonth() {
      const d = new Date(this.currentMonth)
      d.setMonth(d.getMonth() - 1)
      this.currentMonth = d
    },

    nextMonth() {
      const d = new Date(this.currentMonth)
      d.setMonth(d.getMonth() + 1)
      this.currentMonth = d
    },

    // ─── CRUD ─────────────────────────────────────────────────
    async submitForm() {
      this.submitting = true
      this.formError = ''
      try {
        if (this.isEditing) {
          await axios.put('/admin/appointments/' + this.selectedAppointment.id, {
            title:  this.form.title,
            status: this.form.status,
            notes:  this.form.notes,
          })
        } else {
          await axios.post('/admin/appointments', {
            case_manager_id:  this.form.case_manager_id,
            client_id:        this.form.client_id,
            title:            this.form.title,
            appointment_date: this.form.appointment_date,
            start_time:       this.form.start_time,
            notes:            this.form.notes,
          })
        }
        this.closePanel()
        await this.loadAppointments()
      } catch (e) {
        this.formError = e.response?.data?.message || this.$t('appointments.error_save')
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
        await axios.delete('/admin/appointments/' + this.selectedAppointment.id)
        this.showDeleteModal = false
        this.closePanel()
        await this.loadAppointments()
      } catch (e) { console.error(e) }
      finally { this.submitting = false }
    },

    // ─── Notificaciones WebSocket ─────────────────────────────
    subscribeToNotifications() {
      const authStore = useAuthStore()
      if (!window.Echo || !authStore.user?.id) return

      window.Echo.private('case-manager.' + authStore.user.id)
        .listen('.appointment.created', (data) => {
          this.playNotificationSound()
          this.showNotificationToast(data)
          this.loadAppointments()
        })
    },

    playNotificationSound() {
      const audio = new Audio('/sounds/notification.mp3')
      audio.volume = 0.7
      audio.play().catch(() => {})
    },

    showNotificationToast(data) {
      const date = this.formatDate(data.appointment_date)
      const time = data.start_time?.slice(0, 5)
      this.$toast.success(
        `📅 Nueva cita: ${data.title}\nCliente: ${data.client?.name}\n${date} · ${time}`,
        { timeout: 8000, position: 'top-right' }
      )
    },

    handleEventClick({ event }) {
      const appt = this.appointments.find(a => a.id == event.id)
      if (appt) this.openEditPanel(appt)
    },

    formatDate(date) {
      if (!date) return ''
      return new Date(date + 'T00:00:00').toLocaleDateString('es-ES', {
        day: '2-digit', month: 'short', year: 'numeric'
      })
    },
  },
}
</script>

<style scoped>
.appointments-page { padding: 24px; max-width: 1500px; margin: 0 auto; font-family: 'Segoe UI', sans-serif; color: #1e293b; }

/* Header */
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.page-title { font-size: 1.75rem; font-weight: 700; margin: 0; }
.page-subtitle { color: #64748b; margin: 4px 0 0; font-size: 0.88rem; }

/* Botones */
.btn-primary { background: #3b82f6; color: #fff; border: none; border-radius: 8px; padding: 10px 20px; font-size: 0.9rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: background .2s; }
.btn-primary:hover { background: #1e40af; }
.btn-primary:disabled { opacity: .6; cursor: not-allowed; }
.btn-secondary { background: #fff; color: #1e293b; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 20px; font-size: 0.9rem; cursor: pointer; }
.btn-secondary:hover { background: #f8fafc; }
.btn-danger { background: #ef4444; color: #fff; border: none; border-radius: 8px; padding: 10px 20px; font-size: 0.9rem; font-weight: 600; cursor: pointer; }
.btn-danger:hover { background: #dc2626; }
.btn-danger:disabled { opacity: .6; }
.btn-danger-outline { background: none; border: 1px solid #fca5a5; color: #ef4444; border-radius: 8px; padding: 10px 16px; font-size: 0.85rem; cursor: pointer; transition: all .2s; }
.btn-danger-outline:hover { background: #fee2e2; }
.btn-clear { background: transparent; border: 1px dashed #e2e8f0; border-radius: 8px; padding: 8px 16px; color: #64748b; cursor: pointer; font-size: 0.85rem; align-self: flex-end; }
.btn-clear:hover { border-color: #94a3b8; color: #1e293b; }

/* Filtros */
.filters-bar { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 16px 20px; display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
.filter-group { display: flex; flex-direction: column; gap: 6px; flex: 1; min-width: 160px; }
.filter-group label { font-size: 0.78rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .5px; }
.filter-group select { border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; font-size: 0.9rem; color: #1e293b; background: #fff; outline: none; }
.filter-group select:focus { border-color: #3b82f6; }

/* Layout */
.main-layout { display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 24px; transition: grid-template-columns .35s ease; }
.main-layout.panel-open { grid-template-columns: 1fr 380px; }

/* FullCalendar */
.calendar-col { min-width: 0; }
.calendar-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
:deep(.fc-button) { background: #3b82f6 !important; border-color: #3b82f6 !important; border-radius: 6px !important; font-size: 0.8rem !important; }
:deep(.fc-button:hover) { background: #1e40af !important; border-color: #1e40af !important; }
:deep(.fc-button-active) { background: #1e40af !important; border-color: #1e40af !important; }
:deep(.fc-daygrid-event) { border-radius: 4px !important; font-size: 0.78rem !important; }
:deep(.fc-day-today) { background: #eff6ff !important; }

/* Panel lateral */
.form-panel { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,.08); display: flex; flex-direction: column; height: fit-content; position: sticky; top: 20px; overflow: hidden; }
.panel-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); color: #fff; }
.panel-title-group { display: flex; align-items: center; gap: 10px; }
.panel-icon { background: rgba(255,255,255,.2); border-radius: 8px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
.panel-header h3 { font-size: 1rem; font-weight: 700; margin: 0; }
.panel-close { background: rgba(255,255,255,.15); border: none; color: #fff; border-radius: 6px; width: 28px; height: 28px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.panel-close:hover { background: rgba(255,255,255,.3); }
.panel-body { padding: 16px; overflow-y: auto; max-height: calc(100vh - 180px); }

/* Steps */
.step { margin-bottom: 12px; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fafbff; }
.step.completed { border-color: #bfdbfe; background: #eff6ff; }
.step-label { display: flex; align-items: center; gap: 8px; font-size: 0.78rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 10px; }
.step-number { background: #3b82f6; color: #fff; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; font-weight: 700; flex-shrink: 0; }
.step.completed .step-number { background: #10b981; }
.step-select, .step-input { width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; font-size: 0.88rem; color: #1e293b; background: #fff; outline: none; box-sizing: border-box; }
.step-select:focus, .step-input:focus { border-color: #3b82f6; }
.step-textarea { width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; font-size: 0.88rem; color: #1e293b; background: #fff; outline: none; box-sizing: border-box; resize: vertical; font-family: inherit; margin-top: 8px; }
.step-textarea:focus { border-color: #3b82f6; }
.step-loading { display: flex; align-items: center; gap: 8px; color: #64748b; font-size: 0.85rem; }
.step-empty { color: #94a3b8; font-size: 0.85rem; text-align: center; padding: 8px 0; }

/* Clients chips */
.clients-grid { display: flex; flex-wrap: wrap; gap: 6px; }
.client-chip { display: flex; align-items: center; gap: 6px; padding: 5px 10px; border-radius: 20px; border: 2px solid #e2e8f0; cursor: pointer; font-size: 0.82rem; font-weight: 600; color: #475569; transition: all .15s; background: #fff; }
.client-chip:hover { border-color: #93c5fd; color: #1e40af; }
.client-chip.selected { border-color: #3b82f6; background: #eff6ff; color: #1e40af; }
.chip-avatar { width: 22px; height: 22px; border-radius: 50%; object-fit: cover; }

/* Mini calendario */
.mini-calendar { background: #fff; border-radius: 8px; }
.mini-cal-header { display: flex; align-items: center; justify-content: space-between; padding: 6px 4px; margin-bottom: 4px; }
.cal-month-title { font-size: 0.85rem; font-weight: 700; color: #1e293b; }
.cal-nav { background: none; border: none; font-size: 1.2rem; color: #64748b; cursor: pointer; padding: 2px 6px; border-radius: 4px; line-height: 1; }
.cal-nav:hover { background: #f1f5f9; }
.mini-cal-weekdays { display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; margin-bottom: 2px; }
.mini-cal-weekdays span { font-size: 0.68rem; font-weight: 700; color: #94a3b8; padding: 2px 0; }
.mini-cal-days { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
.cal-day { aspect-ratio: 1; display: flex; align-items: center; justify-content: center; font-size: 0.76rem; border-radius: 6px; cursor: default; color: #cbd5e1; }
.cal-day.disabled { color: #e2e8f0; cursor: not-allowed; }
.cal-day.available { color: #1e293b; cursor: pointer; font-weight: 500; }
.cal-day.available:hover { background: #eff6ff; color: #3b82f6; }
.cal-day.today { font-weight: 700; color: #3b82f6; }
.cal-day.selected { background: #3b82f6 !important; color: #fff !important; font-weight: 700; }

/* Slots */
.slots-grid { display: flex; flex-wrap: wrap; gap: 6px; }
.slot-btn { padding: 5px 10px; border-radius: 6px; font-size: 0.78rem; font-weight: 600; border: 2px solid #e2e8f0; background: #fff; cursor: pointer; color: #475569; transition: all .15s; }
.slot-btn:hover { border-color: #93c5fd; color: #1e40af; background: #eff6ff; }
.slot-btn.selected { background: #3b82f6; border-color: #3b82f6; color: #fff; }

/* Resumen */
.appointment-summary { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px 12px; margin: 10px 0; display: flex; flex-direction: column; gap: 4px; }
.summary-row { display: flex; align-items: center; gap: 8px; font-size: 0.82rem; color: #166534; }

/* Edit summary */
.edit-summary { background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; padding: 12px; margin-bottom: 14px; display: flex; flex-direction: column; gap: 8px; }
.edit-row { display: flex; justify-content: space-between; font-size: 0.85rem; }
.edit-label { color: #64748b; font-weight: 600; }

/* Form group */
.form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; }
.form-group label { font-size: 0.78rem; font-weight: 600; color: #64748b; }
.status-pills { display: flex; flex-wrap: wrap; gap: 6px; }
.status-pill { border: 2px solid; border-radius: 20px; padding: 4px 10px; font-size: 0.76rem; font-weight: 600; cursor: pointer; background: transparent; transition: all .2s; }

/* Error */
.form-error { background: #fee2e2; color: #991b1b; padding: 10px 14px; border-radius: 8px; font-size: 0.82rem; margin-bottom: 10px; }

/* Panel actions */
.panel-actions { display: flex; gap: 10px; padding-top: 10px; border-top: 1px solid #f1f5f9; margin-top: 8px; }
.panel-actions .btn-primary, .panel-actions .btn-secondary { flex: 1; justify-content: center; }

/* Spinner */
.spinner-sm { width: 16px; height: 16px; border: 2px solid #e2e8f0; border-top-color: #3b82f6; border-radius: 50%; animation: spin .7s linear infinite; flex-shrink: 0; }

/* Tabla */
.table-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,.06); overflow: hidden; }
.table-header { display: flex; align-items: center; gap: 12px; padding: 18px 20px; border-bottom: 1px solid #e2e8f0; }
.table-header h2 { font-size: 1rem; font-weight: 700; margin: 0; }
.badge-count { background: #eff6ff; color: #3b82f6; border-radius: 20px; padding: 2px 10px; font-size: 0.78rem; font-weight: 600; }
.appointments-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.appointments-table th { background: #f8fafc; padding: 12px 16px; text-align: left; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #64748b; border-bottom: 1px solid #e2e8f0; }
.appointments-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.appointments-table tr:last-child td { border-bottom: none; }
.appointments-table tr:hover td { background: #fafbff; }
.row-selected td { background: #eff6ff !important; }
.title-cell { font-weight: 600; }
.time-cell { font-family: monospace; font-size: 0.85rem; }
.person-cell { display: flex; align-items: center; gap: 8px; }
.avatar-sm { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 2px solid #e2e8f0; }
.status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.74rem; font-weight: 600; }
.status-pending   { background: #fef3c7; color: #92400e; }
.status-confirmed { background: #dbeafe; color: #1e40af; }
.status-completed { background: #d1fae5; color: #065f46; }
.status-cancelled { background: #fee2e2; color: #991b1b; }
.status-no_show   { background: #f1f5f9; color: #475569; }
.action-buttons { display: flex; gap: 6px; }
.btn-icon-action { background: none; border: 1px solid #e2e8f0; border-radius: 6px; padding: 5px 8px; font-size: 0.85rem; cursor: pointer; transition: all .2s; }
.btn-icon-action:hover { background: #f1f5f9; }
.btn-icon-action.danger:hover { background: #fee2e2; border-color: #fca5a5; }

/* Loading / empty */
.loading-state, .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; padding: 48px; color: #64748b; }
.spinner { width: 32px; height: 32px; border: 3px solid #e2e8f0; border-top-color: #3b82f6; border-radius: 50%; animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.empty-icon { font-size: 2.5rem; }

/* Modal */
.modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,.5); backdrop-filter: blur(2px); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 20px; }
.modal-box { background: #fff; border-radius: 16px; width: 100%; max-width: 420px; box-shadow: 0 20px 60px rgba(0,0,0,.15); overflow: hidden; }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 18px 24px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; }
.modal-header h3 { font-size: 1rem; font-weight: 700; margin: 0; }
.modal-close { background: none; border: none; font-size: 1rem; color: #64748b; cursor: pointer; padding: 4px 8px; border-radius: 4px; }
.modal-close:hover { background: #e2e8f0; }
.delete-message { padding: 20px 24px; color: #64748b; font-size: 0.9rem; line-height: 1.6; margin: 0; }
.modal-footer { display: flex; justify-content: flex-end; gap: 12px; padding: 0 24px 20px; }

/* Transitions */
.slide-panel-enter-active { transition: all .3s ease; }
.slide-panel-leave-active { transition: all .25s ease; }
.slide-panel-enter-from, .slide-panel-leave-to { opacity: 0; transform: translateX(30px); }
.fade-enter-active, .fade-leave-active { transition: opacity .25s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.fade-step-enter-active { transition: all .25s ease; }
.fade-step-leave-active { transition: all .2s ease; }
.fade-step-enter-from { opacity: 0; transform: translateY(-8px); }
.fade-step-leave-to { opacity: 0; }
</style>