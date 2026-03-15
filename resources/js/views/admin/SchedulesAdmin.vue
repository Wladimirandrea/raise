<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

// ─── SCHEDULES ────────────────────────────────────────────
const days = ref([])
const loading = ref(false)
const saving = ref(null)
const error = ref('')
const success = ref('')

const dayColors = [
  'border-purple-500/30 bg-purple-500/5',
  'border-blue-500/30 bg-blue-500/5',
  'border-blue-500/30 bg-blue-500/5',
  'border-blue-500/30 bg-blue-500/5',
  'border-blue-500/30 bg-blue-500/5',
  'border-blue-500/30 bg-blue-500/5',
  'border-purple-500/30 bg-purple-500/5',
]

const forms = ref({
  0: { start_time: '08:00', end_time: '17:00', is_active: false },
  1: { start_time: '08:00', end_time: '17:00', is_active: false },
  2: { start_time: '08:00', end_time: '17:00', is_active: false },
  3: { start_time: '08:00', end_time: '17:00', is_active: false },
  4: { start_time: '08:00', end_time: '17:00', is_active: false },
  5: { start_time: '08:00', end_time: '17:00', is_active: false },
  6: { start_time: '08:00', end_time: '17:00', is_active: false },
})

const fetchSchedules = async () => {
  loading.value = true
  try {
    const res = await axios.get('/admin/schedules')
    days.value = res.data
    res.data.forEach(day => {
      forms.value[day.day_of_week] = {
        start_time: day.start_time ? day.start_time.slice(0, 5) : '08:00',
        end_time:   day.end_time   ? day.end_time.slice(0, 5)   : '17:00',
        is_active:  day.is_active  ?? false,
      }
    })
  } catch (err) {
    error.value = t('schedules.error_load')
  } finally {
    loading.value = false
  }
}

const saveDay = async (dayOfWeek) => {
  saving.value = dayOfWeek
  error.value = ''
  success.value = ''
  try {
    await axios.post('/admin/schedules/upsert', {
      day_of_week: dayOfWeek,
      ...forms.value[dayOfWeek],
    })
    success.value = t('schedules.saved_ok', { day: t(`schedules.days.${dayOfWeek}`) })
    await fetchSchedules()
    setTimeout(() => success.value = '', 3000)
  } catch (err) {
    error.value = err.response?.data?.message || t('schedules.error_save')
  } finally {
    saving.value = null
  }
}

const toggleDay = async (day) => {
  if (!day.id) {
    forms.value[day.day_of_week].is_active = !forms.value[day.day_of_week].is_active
    return
  }
  try {
    await axios.patch(`/admin/schedules/${day.id}/toggle`)
    await fetchSchedules()
  } catch (err) {
    error.value = t('schedules.error_toggle')
  }
}

// ─── DAYS OFF ─────────────────────────────────────────────
const daysOff = ref([])
const loadingDaysOff = ref(false)
const savingDayOff = ref(false)
const dayOffError = ref('')
const dayOffSuccess = ref('')

const dayOffForm = ref({
  date: '',
  start_time: '',
  end_time: '',
  reason: '',
})

const fetchDaysOff = async () => {
  loadingDaysOff.value = true
  try {
    const { data } = await axios.get('/admin/days-off')
    daysOff.value = data
  } catch (e) {
    console.error(e)
  } finally {
    loadingDaysOff.value = false
  }
}

const saveDayOff = async () => {
  dayOffError.value = ''
  dayOffSuccess.value = ''
  if (!dayOffForm.value.date || !dayOffForm.value.start_time || !dayOffForm.value.end_time) {
    dayOffError.value = 'Completa la fecha y el rango de horas.'
    return
  }
  savingDayOff.value = true
  try {
    await axios.post('/admin/days-off', dayOffForm.value)
    dayOffSuccess.value = '✅ Bloqueo guardado correctamente.'
    dayOffForm.value = { date: '', start_time: '', end_time: '', reason: '' }
    await fetchDaysOff()
    setTimeout(() => dayOffSuccess.value = '', 3000)
  } catch (e) {
    dayOffError.value = e.response?.data?.message || 'Error al guardar el bloqueo.'
  } finally {
    savingDayOff.value = false
  }
}

const deleteDayOff = async (id) => {
  try {
    await axios.delete(`/admin/days-off/${id}`)
    await fetchDaysOff()
  } catch (e) {
    dayOffError.value = 'Error al eliminar.'
  }
}

const formatDate = (date) => {
  if (!date) return ''
  const clean = String(date).slice(0, 10)
  const [y, m, d] = clean.split('-')
  return new Date(Number(y), Number(m) - 1, Number(d)).toLocaleDateString('es-ES', {
    weekday: 'short', day: '2-digit', month: 'short', year: 'numeric'
  })
}

onMounted(() => {
  fetchSchedules()
  fetchDaysOff()
})
</script>

<template>
  <div class="p-4 md:p-6 max-w-full mx-auto">

    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl md:text-3xl font-bold text-gray-800">{{ t('schedules.title') }}</h1>
      <p class="text-sm text-gray-500 mt-1">{{ t('schedules.subtitle') }}</p>
    </div>

    <!-- Alerts schedules -->
    <div v-if="error" class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ error }}</div>
    <div v-if="success" class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">✅ {{ success }}</div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <!-- Layout horarios -->
    <div v-else class="flex flex-col xl:flex-row gap-6">

      <!-- Cards de días -->
      <div class="flex-1 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="day in days"
          :key="day.day_of_week"
          :class="[
            'rounded-xl border-2 p-3 transition-all duration-200',
            forms[day.day_of_week]?.is_active
              ? dayColors[day.day_of_week]
              : 'border-gray-200 bg-gray-50 opacity-60'
          ]"
        >
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-3">
              <span class="text-2xl">{{ [0,6].includes(day.day_of_week) ? '🏖️' : '💼' }}</span>
              <div>
                <h3 class="font-semibold text-gray-800">{{ t(`schedules.days.${day.day_of_week}`) }}</h3>
                <span class="text-xs text-gray-400">
                  {{ [0,6].includes(day.day_of_week) ? t('schedules.weekend') : t('schedules.workday') }}
                </span>
              </div>
            </div>
            <button
              @click="toggleDay(day)"
              :class="[
                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200',
                forms[day.day_of_week]?.is_active ? 'bg-blue-600' : 'bg-gray-300'
              ]"
            >
              <span :class="['inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform duration-200', forms[day.day_of_week]?.is_active ? 'translate-x-6' : 'translate-x-1']"></span>
            </button>
          </div>

          <div class="grid grid-cols-2 gap-2 mb-3">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('schedules.start_time') }}</label>
              <input v-model="forms[day.day_of_week].start_time" type="time" :disabled="!forms[day.day_of_week]?.is_active"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-400">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('schedules.end_time') }}</label>
              <input v-model="forms[day.day_of_week].end_time" type="time" :disabled="!forms[day.day_of_week]?.is_active"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-400">
            </div>
          </div>

          <button @click="saveDay(day.day_of_week)" :disabled="saving === day.day_of_week"
            class="w-full py-2 px-4 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
            <span v-if="saving === day.day_of_week">{{ t('schedules.saving') }}</span>
            <span v-else>{{ t('schedules.save_day', { day: t(`schedules.days.${day.day_of_week}`) }) }}</span>
          </button>
        </div>
      </div>

      <!-- Resumen -->
      <div class="xl:w-72 shrink-0">
        <div class="sticky top-6 p-5 bg-white rounded-xl border border-gray-200 shadow-sm">
          <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">{{ t('schedules.summary') }}</h3>
          <div class="space-y-3">
            <div v-for="day in days.filter(d => forms[d.day_of_week]?.is_active)" :key="day.day_of_week"
              class="flex items-center justify-between text-sm py-2 border-b border-gray-100 last:border-0">
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                <span class="font-medium text-gray-700">{{ t(`schedules.days.${day.day_of_week}`) }}</span>
              </div>
              <span class="text-gray-500 text-xs">{{ forms[day.day_of_week]?.start_time }} — {{ forms[day.day_of_week]?.end_time }}</span>
            </div>
            <p v-if="!days.some(d => forms[d.day_of_week]?.is_active)" class="text-gray-400 text-sm text-center py-4">
              {{ t('schedules.no_active') }}
            </p>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500 text-center">
              {{ t('schedules.active_count', { count: days.filter(d => forms[d.day_of_week]?.is_active).length }) }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ SECCIÓN DÍAS OFF ═══════════════════════════════ -->
    <div class="mt-10">
      <div class="mb-4">
        <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
          🚫 Días / Horarios Bloqueados
        </h2>
        <p class="text-sm text-gray-500 mt-1">Bloquea rangos de horas específicos para que no aparezcan slots disponibles en esas fechas.</p>
      </div>

      <!-- Alerts days off -->
      <div v-if="dayOffError" class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ dayOffError }}</div>
      <div v-if="dayOffSuccess" class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ dayOffSuccess }}</div>

      <div class="flex flex-col xl:flex-row gap-6">

        <!-- Formulario nuevo bloqueo -->
        <div class="xl:w-96 shrink-0">
          <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">➕ Nuevo bloqueo</h3>

            <div class="space-y-4">
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📅 Fecha</label>
                <input v-model="dayOffForm.date" type="date"
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400">
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">⏰ Desde</label>
                  <input v-model="dayOffForm.start_time" type="time"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400">
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">⏰ Hasta</label>
                  <input v-model="dayOffForm.end_time" type="time"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400">
                </div>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📝 Motivo (opcional)</label>
                <input v-model="dayOffForm.reason" type="text" placeholder="Ej: Reunión de equipo, Feriado..."
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400">
              </div>

              <!-- Preview -->
              <div v-if="dayOffForm.date && dayOffForm.start_time && dayOffForm.end_time"
                class="bg-orange-50 border border-orange-200 rounded-lg p-3 text-sm text-orange-800">
                🚫 <strong>{{ formatDate(dayOffForm.date) }}</strong>
                de <strong>{{ dayOffForm.start_time }}</strong> a <strong>{{ dayOffForm.end_time }}</strong>
                <span v-if="dayOffForm.reason"> · {{ dayOffForm.reason }}</span>
              </div>

              <button @click="saveDayOff" :disabled="savingDayOff"
                class="w-full py-2 px-4 bg-orange-500 text-white text-sm font-medium rounded-lg hover:bg-orange-600 transition disabled:opacity-50 disabled:cursor-not-allowed">
                {{ savingDayOff ? 'Guardando...' : '🚫 Agregar bloqueo' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Lista de bloqueos -->
        <div class="flex-1">
          <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
              <h3 class="text-sm font-semibold text-gray-700">📋 Bloqueos registrados</h3>
              <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-full">{{ daysOff.length }} bloqueo(s)</span>
            </div>

            <div v-if="loadingDaysOff" class="flex justify-center py-8">
              <div class="w-6 h-6 border-2 border-orange-400 border-t-transparent rounded-full animate-spin"></div>
            </div>

            <div v-else-if="daysOff.length === 0" class="text-center py-10 text-gray-400">
              <div class="text-3xl mb-2">✅</div>
              <p class="text-sm">No hay bloqueos registrados</p>
            </div>

            <div v-else class="divide-y divide-gray-100">
              <div v-for="off in daysOff" :key="off.id"
                class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-lg bg-orange-100 flex items-center justify-center text-orange-600 font-bold text-sm shrink-0">
                    🚫
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-800">{{ formatDate(off.date) }}</p>
                    <p class="text-xs text-orange-600 font-medium">
                      {{ off.start_time?.slice(0,5) }} – {{ off.end_time?.slice(0,5) }}
                      <span v-if="off.reason" class="text-gray-400 font-normal"> · {{ off.reason }}</span>
                    </p>
                  </div>
                </div>
                <button @click="deleteDayOff(off.id)"
                  class="text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg p-2 transition text-sm">
                  🗑️
                </button>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

  </div>
</template>