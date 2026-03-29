<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useI18n } from 'vue-i18n'

const { locale } = useI18n()
const router = useRouter()

// ─── State ────────────────────────────────────────────────
const loading       = ref(false)
const saving        = ref(false)
const loadingSlots  = ref(false)
const clients       = ref([])
const slots         = ref([])
const error         = ref('')
const success       = ref('')

const form = ref({
  client_id:        '',
  title:            '',
  appointment_date: '',
  start_time:       '',
  notes:            '',
})

const errors = ref({
  client_id:        '',
  title:            '',
  appointment_date: '',
  start_time:       '',
})

// ─── Computed ─────────────────────────────────────────────
const minDate = computed(() => {
  const today = new Date()
  return today.toISOString().split('T')[0]
})

const isFormValid = computed(() =>
  form.value.client_id &&
  form.value.title &&
  form.value.appointment_date &&
  form.value.start_time
)

// ─── Load clients ─────────────────────────────────────────
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

// ─── Load slots when date changes ─────────────────────────
const onDateChange = async () => {
  form.value.start_time = ''
  slots.value = []
  if (!form.value.appointment_date) return

  loadingSlots.value = true
  try {
    const { data } = await axios.get('/case-manager/available-slots', {
      params: { date: form.value.appointment_date }
    })
    slots.value = data.available ? data.slots : []
    if (!data.available) {
      errors.value.appointment_date = locale.value === 'en'
        ? 'No availability for this day.'
        : 'No hay disponibilidad para este día.'
    } else {
      errors.value.appointment_date = ''
    }
  } catch (e) {
    console.error(e)
  } finally {
    loadingSlots.value = false
  }
}

// ─── Validate ─────────────────────────────────────────────
const validate = () => {
  let valid = true
  errors.value = { client_id: '', title: '', appointment_date: '', start_time: '' }

  if (!form.value.client_id) {
    errors.value.client_id = locale.value === 'en' ? 'Select a client.' : 'Selecciona un cliente.'
    valid = false
  }
  if (!form.value.title) {
    errors.value.title = locale.value === 'en' ? 'Enter a reason.' : 'Ingresa un motivo.'
    valid = false
  }
  if (!form.value.appointment_date) {
    errors.value.appointment_date = locale.value === 'en' ? 'Select a date.' : 'Selecciona una fecha.'
    valid = false
  }
  if (!form.value.start_time) {
    errors.value.start_time = locale.value === 'en' ? 'Select a time slot.' : 'Selecciona un horario.'
    valid = false
  }
  return valid
}

// ─── Submit ───────────────────────────────────────────────
const submit = async () => {
  if (!validate()) return
  saving.value = true
  error.value  = ''
  success.value = ''

  try {
    await axios.post('/case-manager/appointments', {
      client_id:        form.value.client_id,
      title:            form.value.title,
      appointment_date: form.value.appointment_date,
      start_time:       form.value.start_time,
      notes:            form.value.notes,
    })

    success.value = locale.value === 'en'
      ? '✅ Appointment created successfully.'
      : '✅ Cita creada correctamente.'

    window.dispatchEvent(new Event('appointment:created'))

    setTimeout(() => router.push('/case-manager/estado-citas'), 1800)
  } catch (e) {
    error.value = e.response?.data?.message ||
      (locale.value === 'en' ? 'Error creating appointment.' : 'Error al crear la cita.')
  } finally {
    saving.value = false
  }
}

const resetForm = () => {
  form.value  = { client_id: '', title: '', appointment_date: '', start_time: '', notes: '' }
  errors.value = { client_id: '', title: '', appointment_date: '', start_time: '' }
  slots.value  = []
  error.value  = ''
  success.value = ''
}

onMounted(loadClients)
</script>

<template>
  <div class="nueva-cita-page">

    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          📅 {{ locale === 'en' ? 'New Appointment' : 'Nueva Cita' }}
        </h1>
        <p class="page-subtitle">
          {{ locale === 'en' ? 'Schedule an appointment with one of your clients' : 'Agenda una cita con uno de tus clientes' }}
        </p>
      </div>
      <button class="btn-back" @click="router.back()">
        ← {{ locale === 'en' ? 'Back' : 'Volver' }}
      </button>
    </div>

    <!-- Card formulario -->
    <div class="form-card">

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
      </div>

      <form v-else @submit.prevent="submit" class="form-grid">

        <!-- Cliente -->
        <div class="form-group">
          <label class="form-label">
            👤 {{ locale === 'en' ? 'Client' : 'Cliente' }}
            <span class="required">*</span>
          </label>
          <select v-model="form.client_id" class="form-select" :class="{ 'has-error': errors.client_id }">
            <option value="">
              {{ locale === 'en' ? '— Select a client —' : '— Selecciona un cliente —' }}
            </option>
            <option v-for="c in clients" :key="c.id" :value="c.id">
              {{ c.name }}
            </option>
          </select>
          <span v-if="errors.client_id" class="field-error">{{ errors.client_id }}</span>
          <span v-if="clients.length === 0 && !loading" class="field-hint">
            {{ locale === 'en' ? 'You have no assigned clients yet.' : 'Aún no tienes clientes asignados.' }}
          </span>
        </div>

        <!-- Motivo -->
        <div class="form-group">
          <label class="form-label">
            📋 {{ locale === 'en' ? 'Reason / Title' : 'Motivo / Título' }}
            <span class="required">*</span>
          </label>
          <input
            v-model="form.title"
            type="text"
            class="form-input"
            :class="{ 'has-error': errors.title }"
            :placeholder="locale === 'en' ? 'e.g. Follow-up consultation' : 'Ej. Consulta de seguimiento'"
            maxlength="255"
          />
          <span v-if="errors.title" class="field-error">{{ errors.title }}</span>
        </div>

        <!-- Fecha -->
        <div class="form-group">
          <label class="form-label">
            📅 {{ locale === 'en' ? 'Date' : 'Fecha' }}
            <span class="required">*</span>
          </label>
          <input
            v-model="form.appointment_date"
            type="date"
            class="form-input"
            :class="{ 'has-error': errors.appointment_date }"
            :min="minDate"
            @change="onDateChange"
          />
          <span v-if="errors.appointment_date" class="field-error">{{ errors.appointment_date }}</span>
        </div>

        <!-- Slots de horario -->
        <div class="form-group">
          <label class="form-label">
            ⏰ {{ locale === 'en' ? 'Time Slot' : 'Horario' }}
            <span class="required">*</span>
          </label>

          <div v-if="loadingSlots" class="slots-loading">
            <div class="spinner-sm"></div>
            <span>{{ locale === 'en' ? 'Loading slots...' : 'Cargando horarios...' }}</span>
          </div>

          <div v-else-if="!form.appointment_date" class="slots-hint">
            {{ locale === 'en' ? 'Select a date first.' : 'Primero selecciona una fecha.' }}
          </div>

          <div v-else-if="slots.length === 0 && form.appointment_date" class="slots-empty">
            {{ locale === 'en' ? 'No available slots for this day.' : 'No hay horarios disponibles para este día.' }}
          </div>

          <div v-else class="slots-grid">
            <button
              v-for="slot in slots"
              :key="slot.start"
              type="button"
              class="slot-btn"
              :class="{ selected: form.start_time === slot.start }"
              @click="form.start_time = slot.start"
            >
              {{ slot.label }}
            </button>
          </div>
          <span v-if="errors.start_time" class="field-error">{{ errors.start_time }}</span>
        </div>

        <!-- Notas -->
        <div class="form-group full-width">
          <label class="form-label">
            📝 {{ locale === 'en' ? 'Notes' : 'Notas' }}
            <span class="optional">({{ locale === 'en' ? 'optional' : 'opcional' }})</span>
          </label>
          <textarea
            v-model="form.notes"
            class="form-textarea"
            :placeholder="locale === 'en' ? 'Additional notes or observations...' : 'Notas adicionales u observaciones...'"
            rows="3"
          ></textarea>
        </div>

        <!-- Alerts -->
        <div v-if="success" class="alert-success full-width">{{ success }}</div>
        <div v-if="error"   class="alert-error full-width">{{ error }}</div>

        <!-- Acciones -->
        <div class="form-actions full-width">
          <button type="button" class="btn-cancel" @click="resetForm">
            {{ locale === 'en' ? 'Clear' : 'Limpiar' }}
          </button>
          <button type="submit" class="btn-submit" :disabled="saving || !isFormValid">
            <span v-if="saving">{{ locale === 'en' ? 'Saving...' : 'Guardando...' }}</span>
            <span v-else>📅 {{ locale === 'en' ? 'Create Appointment' : 'Crear Cita' }}</span>
          </button>
        </div>

      </form>
    </div>

  </div>
</template>

<style scoped>
.nueva-cita-page { padding: 24px; max-width: 780px; margin: 0 auto; font-family: 'Segoe UI', sans-serif; }

/* Header */
.page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 24px; gap: 12px; flex-wrap: wrap; }
.page-title { font-size: 1.6rem; font-weight: 700; color: #1e293b; margin: 0; }
.page-subtitle { font-size: 0.88rem; color: #64748b; margin: 4px 0 0; }
.btn-back { background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; border-radius: 8px; padding: 8px 16px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all .15s; white-space: nowrap; }
.btn-back:hover { background: #f1f5f9; color: #1e293b; }

/* Card */
.form-card { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,.06); padding: 32px; }

/* Grid */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.full-width { grid-column: 1 / -1; }

/* Labels */
.form-label { font-size: 0.82rem; font-weight: 700; color: #374151; }
.required { color: #ef4444; margin-left: 2px; }
.optional { color: #94a3b8; font-weight: 400; margin-left: 4px; }

/* Inputs */
.form-input,
.form-select,
.form-textarea {
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  padding: 10px 12px;
  font-size: 0.9rem;
  color: #1e293b;
  outline: none;
  transition: border-color .15s;
  font-family: inherit;
  background: #fff;
}
.form-input:focus,
.form-select:focus,
.form-textarea:focus { border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,.1); }
.form-input.has-error,
.form-select.has-error { border-color: #ef4444; }
.form-textarea { resize: vertical; min-height: 80px; }

/* Errors / hints */
.field-error { font-size: 0.75rem; color: #ef4444; }
.field-hint  { font-size: 0.75rem; color: #94a3b8; }

/* Slots */
.slots-grid { display: flex; flex-wrap: wrap; gap: 8px; }
.slot-btn {
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  padding: 7px 14px;
  font-size: 0.82rem;
  font-weight: 600;
  color: #475569;
  background: #f8fafc;
  cursor: pointer;
  transition: all .15s;
}
.slot-btn:hover { border-color: #0d9488; color: #0d9488; background: #f0fdfa; }
.slot-btn.selected { background: #0d9488; border-color: #0d9488; color: #fff; }
.slots-loading { display: flex; align-items: center; gap: 8px; color: #64748b; font-size: 0.85rem; }
.slots-hint  { font-size: 0.85rem; color: #94a3b8; }
.slots-empty { font-size: 0.85rem; color: #ef4444; }

/* Actions */
.form-actions { display: flex; justify-content: flex-end; gap: 12px; padding-top: 8px; border-top: 1px solid #f1f5f9; }
.btn-cancel { background: #f8fafc; border: 1px solid #e2e8f0; color: #64748b; border-radius: 8px; padding: 10px 20px; font-size: 0.88rem; font-weight: 600; cursor: pointer; transition: all .15s; }
.btn-cancel:hover { background: #f1f5f9; }
.btn-submit { background: #0d9488; color: #fff; border: none; border-radius: 8px; padding: 10px 24px; font-size: 0.88rem; font-weight: 600; cursor: pointer; transition: background .15s; }
.btn-submit:hover:not(:disabled) { background: #0f766e; }
.btn-submit:disabled { opacity: .5; cursor: not-allowed; }

/* Alerts */
.alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 10px 14px; border-radius: 8px; font-size: 0.85rem; }
.alert-error   { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 10px 14px; border-radius: 8px; font-size: 0.85rem; }

/* Loading */
.loading-state { display: flex; justify-content: center; padding: 60px; }
.spinner    { width: 36px; height: 36px; border: 3px solid #e2e8f0; border-top-color: #0d9488; border-radius: 50%; animation: spin .7s linear infinite; }
.spinner-sm { width: 18px; height: 18px; border: 2px solid #e2e8f0; border-top-color: #0d9488; border-radius: 50%; animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Responsive */
@media (max-width: 600px) {
  .form-grid { grid-template-columns: 1fr; }
  .form-card { padding: 20px; }
}
</style>