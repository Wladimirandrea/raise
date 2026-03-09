<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const users = ref([])
const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  phone: '',
  role: 'client',
  avatar: null,
  avatarPreview: null
})
const editId = ref(null)
const loading = ref(false)
const error = ref('')
const showForm = ref(false)

// ─── AVATARES POR DEFECTO ─────────────────────────────────
const defaultAvatars = {
  admin:        '/storage/avatars/admin.png',
  case_manager: '/storage/avatars/casemanager.png',
  client:       '/storage/avatars/client.png',
}

const avatarPreviewSrc = computed(() => {
  if (form.value.avatarPreview) return form.value.avatarPreview
  return defaultAvatars[form.value.role] || '/storage/avatars/default.png'
})

watch(() => form.value.role, () => {
  if (!form.value.avatar) {
    form.value.avatarPreview = null
  }
})

// ─── MOBILE/TABLET DETECTION ──────────────────────────────
const isMobile = ref(window.innerWidth < 1280)
const handleResize = () => { isMobile.value = window.innerWidth < 1280 }

// ─── FILTROS DE ROL ───────────────────────────────────────
const activeFilter = ref('all')

const filteredUsers = computed(() => {
  if (activeFilter.value === 'all') return users.value
  return users.value.filter(u => u.roles.some(r => r.name === activeFilter.value))
})

const setFilter = (filter) => {
  activeFilter.value = filter
  currentPage.value = 1
}

// ─── PAGINACIÓN ───────────────────────────────────────────
const currentPage = ref(1)
const perPage = ref(8)

const totalPages = computed(() => Math.ceil(filteredUsers.value.length / perPage.value))

const paginatedUsers = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return filteredUsers.value.slice(start, start + perPage.value)
})

const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) currentPage.value = page
}

// ─── USUARIOS ────────────────────────────────────────────
const fetchUsers = async () => {
  try {
    const res = await axios.get('/admin/users')
    users.value = res.data
  } catch (err) {
    error.value = t('users.error_load')
  }
}

const handleAvatarChange = (e) => {
  const file = e.target.files[0]
  if (file) {
    form.value.avatar = file
    const reader = new FileReader()
    reader.onload = (event) => { form.value.avatarPreview = event.target.result }
    reader.readAsDataURL(file)
  }
}

const saveUser = async () => {
  loading.value = true
  error.value = ''

  const formData = new FormData()
  formData.append('name', form.value.name)
  formData.append('email', form.value.email)
  formData.append('phone', form.value.phone || '')
  formData.append('role', form.value.role)

  if (form.value.password) {
    formData.append('password', form.value.password)
    formData.append('password_confirmation', form.value.password_confirmation)
  }

  if (form.value.avatar) {
    formData.append('avatar', form.value.avatar)
  }

  try {
    if (editId.value) {
      await axios.post(`/admin/users/${editId.value}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data', 'X-HTTP-Method-Override': 'PUT' }
      })
    } else {
      await axios.post('/admin/users', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
    }
    resetForm()
    fetchUsers()
  } catch (err) {
    error.value = err.response?.data?.message || t('users.error_save')
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  editId.value = null
  showForm.value = false
  form.value = {
    name: '', email: '', password: '', password_confirmation: '',
    phone: '', role: 'client', avatar: null, avatarPreview: null
  }
}

const openCreateForm = () => {
  editId.value = null
  showForm.value = true
  form.value = {
    name: '', email: '', password: '', password_confirmation: '',
    phone: '', role: 'client', avatar: null, avatarPreview: null
  }
}

const editUser = (user) => {
  showForm.value = true
  editId.value = user.id
  form.value = {
    name: user.name,
    email: user.email,
    password: '',
    password_confirmation: '',
    phone: user.phone || '',
    role: user.roles[0]?.name || 'client',
    avatar: null,
    avatarPreview: user.avatar ? `/storage/${user.avatar}` : null
  }
}

const deleteUser = async (id) => {
  if (!confirm(t('users.confirm_delete'))) return
  try {
    await axios.delete(`/admin/users/${id}`)
    fetchUsers()
  } catch (err) {
    error.value = t('users.error_delete')
  }
}

onMounted(() => {
  fetchUsers()
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})
</script>

<template>
  <div class="p-4 md:p-6 max-w-full mx-auto h-full flex flex-col">
    <h1 class="text-2xl md:text-3xl font-bold mb-6 text-gray-800">{{ t('users.title') }}</h1>

    <div v-if="error" class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
      {{ error }}
    </div>

    <div class="flex flex-col xl:flex-row gap-6 flex-1 min-h-0">

      <!-- ─── LISTA DE USUARIOS ─────────────────────────────── -->
      <div class="flex-1 bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 flex flex-col min-h-0">

        <!-- Header -->
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-800">{{ t('users.list') }}</h2>
          <div class="flex items-center gap-3">
            <span class="text-sm text-gray-400">{{ filteredUsers.length }} usuarios</span>
            <button
              @click="openCreateForm"
              class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition font-medium"
            >
              + {{ t('users.create') }}
            </button>
          </div>
        </div>

        <!-- ─── FILTROS DE ROL ─────────────────────────────── -->
        <div class="px-4 py-3 border-b border-gray-100 flex gap-2 flex-wrap">
          <button
            @click="setFilter('all')"
            :class="['px-3 py-1.5 rounded-lg text-xs font-medium transition border', activeFilter === 'all' ? 'bg-gray-800 text-white border-gray-800' : 'border-gray-300 text-gray-600 hover:bg-gray-50']"
          >
            {{ t('users.all') }}
          </button>
          <button
            @click="setFilter('admin')"
            :class="['px-3 py-1.5 rounded-lg text-xs font-medium transition border', activeFilter === 'admin' ? 'bg-red-600 text-white border-red-600' : 'border-gray-300 text-gray-600 hover:bg-gray-50']"
          >
            {{ t('users.roles.admin') }}
          </button>
          <button
            @click="setFilter('case_manager')"
            :class="['px-3 py-1.5 rounded-lg text-xs font-medium transition border', activeFilter === 'case_manager' ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 text-gray-600 hover:bg-gray-50']"
          >
            {{ t('users.roles.case_manager') }}
          </button>
          <button
            @click="setFilter('client')"
            :class="['px-3 py-1.5 rounded-lg text-xs font-medium transition border', activeFilter === 'client' ? 'bg-green-600 text-white border-green-600' : 'border-gray-300 text-gray-600 hover:bg-gray-50']"
          >
            {{ t('users.roles.client') }}
          </button>
        </div>

        <!-- Tabla -->
        <div class="overflow-x-auto flex-1 min-h-0">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('users.avatar') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('auth.name') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden md:table-cell">{{ t('auth.email') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden lg:table-cell">{{ t('auth.phone') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('users.role') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('users.actions') }}</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="user in paginatedUsers" :key="user.id" class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3">
                  <img
                    :src="user.avatar ? `/storage/${user.avatar}` : defaultAvatars[user.roles[0]?.name] || '/storage/avatars/default.png'"
                    :alt="user.name"
                    class="w-10 h-10 rounded-full object-cover border border-gray-200"
                  >
                </td>
                <td class="px-4 py-3 font-medium text-gray-900">{{ user.name }}</td>
                <td class="px-4 py-3 text-gray-500 hidden md:table-cell">{{ user.email }}</td>
                <td class="px-4 py-3 text-gray-500 hidden lg:table-cell">{{ user.phone || '-' }}</td>
                <td class="px-4 py-3">
                  <span
                    v-for="role in user.roles" :key="role.id"
                    :class="[
                      'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium mr-1',
                      role.name === 'admin'        ? 'bg-red-100 text-red-800'   :
                      role.name === 'case_manager' ? 'bg-blue-100 text-blue-800' :
                                                     'bg-green-100 text-green-800'
                    ]"
                  >
                    {{ t(`users.roles.${role.name}`) }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <button @click="editUser(user)" class="text-indigo-600 hover:text-indigo-900 mr-3 transition-colors">
                    {{ t('users.edit_btn') }}
                  </button>
                  <button @click="deleteUser(user.id)" class="text-red-600 hover:text-red-900 transition-colors">
                    {{ t('users.delete_btn') }}
                  </button>
                </td>
              </tr>
              <tr v-if="paginatedUsers.length === 0">
                <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                  No hay usuarios registrados
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ─── PAGINACIÓN ──────────────────────────────────── -->
        <div v-if="totalPages > 1" class="px-4 py-3 border-t border-gray-200 flex items-center justify-between">
          <span class="text-sm text-gray-500">Página {{ currentPage }} de {{ totalPages }}</span>
          <div class="flex items-center gap-1">
            <button
              @click="goToPage(currentPage - 1)"
              :disabled="currentPage === 1"
              class="px-3 py-1.5 rounded-lg text-sm border border-gray-300 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition"
            >←</button>
            <button
              v-for="page in totalPages" :key="page"
              @click="goToPage(page)"
              :class="['px-3 py-1.5 rounded-lg text-sm border transition', currentPage === page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 hover:bg-gray-50']"
            >{{ page }}</button>
            <button
              @click="goToPage(currentPage + 1)"
              :disabled="currentPage === totalPages"
              class="px-3 py-1.5 rounded-lg text-sm border border-gray-300 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition"
            >→</button>
          </div>
        </div>
      </div>

      <!-- ─── FORMULARIO DESKTOP (>= 1280px) ───────────────── -->
      <div
        v-if="showForm && !isMobile"
        class="animate__animated animate__slideInRight w-full xl:w-[380px] bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 shrink-0 flex flex-col min-h-0"
      >
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-800">
            {{ editId ? t('users.edit') : t('users.create') }}
          </h2>
          <button @click="resetForm" class="text-gray-400 hover:text-gray-600 transition text-xl leading-none">✕</button>
        </div>
        <div class="p-4 overflow-y-auto flex-1">
          <form @submit.prevent="saveUser" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('auth.name') }}</label>
              <input v-model="form.name" type="text" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('auth.email') }}</label>
              <input v-model="form.email" type="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ t('auth.password') }}
                <span v-if="editId" class="text-gray-400 font-normal">{{ t('users.password_hint') }}</span>
              </label>
              <input v-model="form.password" type="password" :required="!editId" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('auth.confirm_password') }}</label>
              <input v-model="form.password_confirmation" type="password" :required="!editId" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('auth.phone') }}</label>
              <input v-model="form.phone" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('users.role') }}</label>
              <select v-model="form.role" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                <option value="admin">{{ t('users.roles.admin') }}</option>
                <option value="case_manager">{{ t('users.roles.case_manager') }}</option>
                <option value="client">{{ t('users.roles.client') }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('users.avatar') }}</label>
              <input type="file" accept="image/*" @change="handleAvatarChange" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
              <div class="mt-3 flex justify-center">
                <img :src="avatarPreviewSrc" alt="Preview" class="w-24 h-24 object-cover rounded-full border-4 border-gray-200 shadow">
              </div>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t">
              <button type="button" @click="resetForm" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-sm">
                {{ t('users.cancel') }}
              </button>
              <button type="submit" :disabled="loading" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 text-sm">
                {{ loading ? t('users.saving') : editId ? t('users.update') : t('users.save') }}
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>

  <!-- ─── MODAL MOBILE + TABLET (< 1280px) ─────────────────── -->
  <Teleport to="body">
    <div v-if="showForm && isMobile" class="fixed inset-0 z-50 flex items-end justify-center">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="resetForm"></div>
      <div class="animate__animated animate__slideInUp relative w-full max-w-2xl bg-white rounded-t-2xl shadow-2xl z-10 max-h-[90vh] flex flex-col">
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-800">
            {{ editId ? t('users.edit') : t('users.create') }}
          </h2>
          <button @click="resetForm" class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
        </div>
        <div class="p-4 overflow-y-auto flex-1">
          <form @submit.prevent="saveUser" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('auth.name') }}</label>
              <input v-model="form.name" type="text" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('auth.email') }}</label>
              <input v-model="form.email" type="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ t('auth.password') }}
                <span v-if="editId" class="text-gray-400 font-normal">{{ t('users.password_hint') }}</span>
              </label>
              <input v-model="form.password" type="password" :required="!editId" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('auth.confirm_password') }}</label>
              <input v-model="form.password_confirmation" type="password" :required="!editId" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('auth.phone') }}</label>
              <input v-model="form.phone" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('users.role') }}</label>
              <select v-model="form.role" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                <option value="admin">{{ t('users.roles.admin') }}</option>
                <option value="case_manager">{{ t('users.roles.case_manager') }}</option>
                <option value="client">{{ t('users.roles.client') }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('users.avatar') }}</label>
              <input type="file" accept="image/*" @change="handleAvatarChange" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
              <div class="mt-3 flex justify-center">
                <img :src="avatarPreviewSrc" alt="Preview" class="w-24 h-24 object-cover rounded-full border-4 border-gray-200 shadow">
              </div>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t mb-4">
              <button type="button" @click="resetForm" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-sm">
                {{ t('users.cancel') }}
              </button>
              <button type="submit" :disabled="loading" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 text-sm">
                {{ loading ? t('users.saving') : editId ? t('users.update') : t('users.save') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Teleport>
</template>