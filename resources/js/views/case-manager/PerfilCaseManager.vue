<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'

const { t }   = useI18n()
const auth    = useAuthStore()
const loading = ref(false)
const success = ref('')
const error   = ref('')

const form = ref({
  name:     '',
  email:    '',
  phone:    '',
  password: '',
  password_confirmation: '',
  avatar:   null,
  avatarPreview: null,
})

onMounted(() => {
  form.value.name  = auth.user?.name  || ''
  form.value.email = auth.user?.email || ''
  form.value.phone = auth.user?.phone || ''
  form.value.avatarPreview = auth.user?.avatar ? `/storage/${auth.user.avatar}` : null
})

const handleAvatarChange = (e) => {
  const file = e.target.files[0]
  if (file) {
    form.value.avatar = file
    const reader = new FileReader()
    reader.onload = (ev) => { form.value.avatarPreview = ev.target.result }
    reader.readAsDataURL(file)
  }
}

const saveProfile = async () => {
  loading.value = true
  error.value   = ''
  success.value = ''

  const formData = new FormData()
  formData.append('name',  form.value.name)
  formData.append('email', form.value.email)
  formData.append('phone', form.value.phone || '')

  if (form.value.password) {
    formData.append('password', form.value.password)
    formData.append('password_confirmation', form.value.password_confirmation)
  }
  if (form.value.avatar) {
    formData.append('avatar', form.value.avatar)
  }

  try {
    const { data } = await axios.post('/case-manager/profile', formData, {
      headers: { 'Content-Type': 'multipart/form-data', 'X-HTTP-Method-Override': 'PUT' }
    })
    auth.user = data.user
    success.value = t('cm.profile.saved')
    form.value.password = ''
    form.value.password_confirmation = ''
    setTimeout(() => success.value = '', 3000)
  } catch (e) {
    error.value = e.response?.data?.message || t('cm.profile.error')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="perfil-cm">

    <div class="page-header">
      <div>
        <h1 class="page-title">{{ t('cm.profile.title') }}</h1>
        <p class="page-subtitle">{{ t('cm.profile.subtitle') }}</p>
      </div>
    </div>

    <div class="profile-layout">

      <!-- Avatar card -->
      <div class="avatar-card">
        <div class="avatar-wrapper">
          <img
            :src="form.avatarPreview || '/storage/avatars/default.png'"
            class="profile-avatar"
            alt="Avatar"
          />
          <label class="avatar-edit-btn" for="avatar-input">📷</label>
          <input
            id="avatar-input"
            type="file"
            accept="image/*"
            class="hidden"
            @change="handleAvatarChange"
          />
        </div>
        <h2 class="profile-name">{{ auth.userName }}</h2>
        <span class="profile-role">Case Manager</span>
      </div>

      <!-- Form card -->
      <div class="form-card">
        <div v-if="success" class="alert-success">✅ {{ success }}</div>
        <div v-if="error" class="alert-error">❌ {{ error }}</div>

        <div class="form-section">
          <h3 class="form-section-title">{{ t('cm.profile.personal_info') }}</h3>

          <div class="form-group">
            <label>{{ t('auth.name') }}</label>
            <input v-model="form.name" type="text" class="form-input" />
          </div>
          <div class="form-group">
            <label>{{ t('auth.email') }}</label>
            <input v-model="form.email" type="email" class="form-input" />
          </div>
          <div class="form-group">
            <label>{{ t('auth.phone') }}</label>
            <input v-model="form.phone" type="text" class="form-input" />
          </div>
        </div>

        <div class="form-section">
          <h3 class="form-section-title">{{ t('cm.profile.change_password') }}</h3>
          <p class="form-hint">{{ t('users.password_hint') }}</p>

          <div class="form-group">
            <label>{{ t('auth.password') }}</label>
            <input v-model="form.password" type="password" class="form-input" />
          </div>
          <div class="form-group">
            <label>{{ t('auth.confirm_password') }}</label>
            <input v-model="form.password_confirmation" type="password" class="form-input" />
          </div>
        </div>

        <div class="form-actions">
          <button class="btn-save" :disabled="loading" @click="saveProfile">
            {{ loading ? t('users.saving') : t('cm.profile.save') }}
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.perfil-cm { padding: 24px; max-width: 1000px; margin: 0 auto; font-family: 'Segoe UI', sans-serif; }
.page-header { margin-bottom: 24px; }
.page-title { font-size: 1.6rem; font-weight: 700; color: #1e293b; margin: 0; }
.page-subtitle { font-size: 0.88rem; color: #64748b; margin: 4px 0 0; }
.profile-layout { display: grid; grid-template-columns: 240px 1fr; gap: 20px; }
@media (max-width: 700px) { .profile-layout { grid-template-columns: 1fr; } }

/* Avatar card */
.avatar-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; display: flex; flex-direction: column; align-items: center; gap: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.06); height: fit-content; }
.avatar-wrapper { position: relative; }
.profile-avatar { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #ccfbf1; }
.avatar-edit-btn { position: absolute; bottom: 0; right: 0; background: #0d9488; color: #fff; border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; cursor: pointer; border: 2px solid #fff; }
.hidden { display: none; }
.profile-name { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0; text-align: center; }
.profile-role { font-size: 0.78rem; color: #0d9488; font-weight: 600; background: #f0fdfa; padding: 3px 10px; border-radius: 20px; }

/* Form card */
.form-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
.form-section { margin-bottom: 24px; }
.form-section-title { font-size: 0.9rem; font-weight: 700; color: #1e293b; margin: 0 0 16px; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9; }
.form-hint { font-size: 0.78rem; color: #94a3b8; margin: -10px 0 12px; }
.form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; }
.form-group label { font-size: 0.78rem; font-weight: 600; color: #64748b; }
.form-input { border: 1px solid #e2e8f0; border-radius: 8px; padding: 9px 12px; font-size: 0.9rem; color: #1e293b; outline: none; transition: border-color .2s; }
.form-input:focus { border-color: #0d9488; }
.form-actions { display: flex; justify-content: flex-end; padding-top: 16px; border-top: 1px solid #f1f5f9; }
.btn-save { background: #0d9488; color: #fff; border: none; border-radius: 8px; padding: 10px 28px; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: background .2s; }
.btn-save:hover { background: #0f766e; }
.btn-save:disabled { opacity: .6; cursor: not-allowed; }
.alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 10px 14px; border-radius: 8px; font-size: 0.85rem; margin-bottom: 16px; }
.alert-error { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 10px 14px; border-radius: 8px; font-size: 0.85rem; margin-bottom: 16px; }
</style>