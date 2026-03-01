<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

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

onMounted(fetchSchedules)
</script>

<template>
  <div class="p-4 md:p-6 max-w-full mx-auto">

    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl md:text-3xl font-bold text-gray-800">{{ t('schedules.title') }}</h1>
      <p class="text-sm text-gray-500 mt-1">{{ t('schedules.subtitle') }}</p>
    </div>

    <!-- Alerts -->
    <div v-if="error" class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
      {{ error }}
    </div>
    <div v-if="success" class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
      ✅ {{ success }}
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <!-- Layout 2 columnas: cards + resumen -->
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
          <!-- Header del día -->
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-3">
              <span class="text-2xl">
                {{ [0,6].includes(day.day_of_week) ? '🏖️' : '💼' }}
              </span>
              <div>
                <h3 class="font-semibold text-gray-800">{{ t(`schedules.days.${day.day_of_week}`) }}</h3>
                <span class="text-xs text-gray-400">
                  {{ [0,6].includes(day.day_of_week) ? t('schedules.weekend') : t('schedules.workday') }}
                </span>
              </div>
            </div>

            <!-- Toggle -->
            <button
              @click="toggleDay(day)"
              :class="[
                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200',
                forms[day.day_of_week]?.is_active ? 'bg-blue-600' : 'bg-gray-300'
              ]"
            >
              <span
                :class="[
                  'inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform duration-200',
                  forms[day.day_of_week]?.is_active ? 'translate-x-6' : 'translate-x-1'
                ]"
              ></span>
            </button>
          </div>

          <!-- Inputs de horario -->
          <div class="grid grid-cols-2 gap-2 mb-3">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('schedules.start_time') }}</label>
              <input
                v-model="forms[day.day_of_week].start_time"
                type="time"
                :disabled="!forms[day.day_of_week]?.is_active"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-400"
              >
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('schedules.end_time') }}</label>
              <input
                v-model="forms[day.day_of_week].end_time"
                type="time"
                :disabled="!forms[day.day_of_week]?.is_active"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 disabled:text-gray-400"
              >
            </div>
          </div>

          <!-- Botón guardar -->
          <button
            @click="saveDay(day.day_of_week)"
            :disabled="saving === day.day_of_week"
            class="w-full py-2 px-4 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="saving === day.day_of_week">{{ t('schedules.saving') }}</span>
            <span v-else>{{ t('schedules.save_day', { day: t(`schedules.days.${day.day_of_week}`) }) }}</span>
          </button>
        </div>
      </div>

      <!-- Resumen (columna derecha) -->
      <div class="xl:w-72 shrink-0">
        <div class="sticky top-6 p-5 bg-white rounded-xl border border-gray-200 shadow-sm">
          <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
            {{ t('schedules.summary') }}
          </h3>
          <div class="space-y-3">
            <div
              v-for="day in days.filter(d => forms[d.day_of_week]?.is_active)"
              :key="day.day_of_week"
              class="flex items-center justify-between text-sm py-2 border-b border-gray-100 last:border-0"
            >
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                <span class="font-medium text-gray-700">{{ t(`schedules.days.${day.day_of_week}`) }}</span>
              </div>
              <span class="text-gray-500 text-xs">
                {{ forms[day.day_of_week]?.start_time }} — {{ forms[day.day_of_week]?.end_time }}
              </span>
            </div>
            <p v-if="!days.some(d => forms[d.day_of_week]?.is_active)" class="text-gray-400 text-sm text-center py-4">
              {{ t('schedules.no_active') }}
            </p>
          </div>

          <!-- Contador -->
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500 text-center">
              {{ t('schedules.active_count', { count: days.filter(d => forms[d.day_of_week]?.is_active).length }) }}
            </p>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>