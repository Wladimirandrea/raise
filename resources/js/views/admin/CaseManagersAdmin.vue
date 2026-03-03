<template>
  <div class="cm-page">

    <!-- ─── Header ──────────────────────────────────────────── -->
    <div class="page-header">
      <div>
        <h1 class="page-title">{{ $t('case_managers.title') }}</h1>
        <p class="page-subtitle">{{ $t('case_managers.subtitle') }}</p>
      </div>
    </div>

    <!-- ─── Layout: Lista CM + Panel detalle ─────────────────── -->
    <div class="main-layout" :class="{ 'panel-open': selectedCM }">

      <!-- ── Lista de Case Managers ── -->
      <div class="cm-list-col">
        <div class="cm-list-card">

          <div class="card-header">
            <h2>{{ $t('case_managers.list_title') }}</h2>
            <span class="badge-count">{{ caseManagers.length }}</span>
          </div>

          <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
          </div>

          <div v-else-if="caseManagers.length === 0" class="empty-state">
            <p>{{ $t('case_managers.no_managers') }}</p>
          </div>

          <div v-else class="cm-list">
            <div
              v-for="cm in caseManagers"
              :key="cm.id"
              class="cm-item"
              :class="{ active: selectedCM?.id === cm.id }"
              @click="selectCM(cm)"
            >
              <img :src="'/storage/' + cm.avatar" class="cm-avatar" />
              <div class="cm-info">
                <span class="cm-name">{{ cm.name }}</span>
                <span class="cm-email">{{ cm.email }}</span>
              </div>
              <div class="cm-meta">
                <span class="clients-badge" :class="cm.clients_count > 0 ? 'has-clients' : 'no-clients'">
                  {{ $t('case_managers.clients', { count: cm.clients_count }) }}
                </span>
                <span class="status-dot" :class="cm.is_active ? 'active' : 'inactive'"></span>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- ── Panel detalle del CM seleccionado ── -->
      <Transition name="slide-panel">
        <div v-if="selectedCM" class="detail-panel">

          <!-- Header del panel -->
          <div class="panel-header">
            <div class="panel-cm-info">
              <img :src="'/storage/' + selectedCM.avatar" class="panel-avatar" />
              <div>
                <h3>{{ selectedCM.name }}</h3>
                <span>{{ selectedCM.email }}</span>
              </div>
            </div>
            <button class="panel-close" @click="selectedCM = null">✕</button>
          </div>

          <!-- Tabs -->
          <div class="tabs">
            <button
              class="tab"
              :class="{ active: activeTab === 'assigned' }"
              @click="activeTab = 'assigned'"
            >
              {{ $t('case_managers.assigned_tab') }}
              <span class="tab-badge">{{ assignedClients.length }}</span>
            </button>
            <button
              class="tab"
              :class="{ active: activeTab === 'unassigned' }"
              @click="activeTab = 'unassigned'; loadUnassigned()"
            >
              {{ $t('case_managers.unassigned_tab') }}
              <span class="tab-badge neutral">{{ unassignedClients.length }}</span>
            </button>
          </div>

          <!-- Tab: Clientes asignados -->
          <div v-if="activeTab === 'assigned'" class="tab-content">

            <div v-if="assignedClients.length === 0" class="empty-tab">
              <div class="empty-icon">👤</div>
              <p>{{ $t('case_managers.no_assigned') }}</p>
              <button class="btn-outline" @click="activeTab = 'unassigned'; loadUnassigned()">
                {{ $t('case_managers.assign_btn') }}
              </button>
            </div>

            <div v-else class="clients-list">
              <div v-for="client in assignedClients" :key="client.id" class="client-item">
                <img :src="'/storage/' + client.avatar" class="client-avatar" />
                <div class="client-info">
                  <span class="client-name">{{ client.name }}</span>
                  <span class="client-email">{{ client.email }}</span>
                </div>
                <div class="client-actions">
                  <select
                    class="reassign-select"
                    @change="reassignClient(client.id, $event.target.value); $event.target.value = ''"
                    :title="$t('case_managers.reassign')"
                  >
                    <option value="">{{ $t('case_managers.reassign') }}</option>
                    <option v-for="cm in otherCaseManagers" :key="cm.id" :value="cm.id">
                      {{ cm.name }}
                    </option>
                  </select>
                  <button
                    class="btn-unassign"
                    @click="unassignClient(client.id)"
                    :title="$t('case_managers.unassign')"
                  >✕</button>
                </div>
              </div>
            </div>

          </div>

          <!-- Tab: Sin asignar -->
          <div v-if="activeTab === 'unassigned'" class="tab-content">

            <div v-if="loadingUnassigned" class="loading-state">
              <div class="spinner"></div>
            </div>

            <div v-else-if="unassignedClients.length === 0" class="empty-tab">
              <div class="empty-icon">✅</div>
              <p>{{ $t('case_managers.no_unassigned') }}</p>
            </div>

            <div v-else>
              <div class="select-all-bar">
                <label class="checkbox-label">
                  <input
                    type="checkbox"
                    :checked="selectedClientIds.length === unassignedClients.length"
                    @change="toggleSelectAll"
                  />
                  {{ $t('case_managers.select_all', { count: unassignedClients.length }) }}
                </label>
                <button
                  v-if="selectedClientIds.length > 0"
                  class="btn-assign"
                  :disabled="submitting"
                  @click="assignSelected"
                >
                  {{ submitting
                    ? $t('case_managers.assigning')
                    : $t('case_managers.assign_selected', { count: selectedClientIds.length }) }}
                </button>
              </div>

              <div class="clients-list">
                <div
                  v-for="client in unassignedClients"
                  :key="client.id"
                  class="client-item selectable"
                  :class="{ selected: selectedClientIds.includes(client.id) }"
                  @click="toggleClientSelection(client.id)"
                >
                  <input
                    type="checkbox"
                    :checked="selectedClientIds.includes(client.id)"
                    @click.stop
                    @change="toggleClientSelection(client.id)"
                  />
                  <img :src="'/storage/' + client.avatar" class="client-avatar" />
                  <div class="client-info">
                    <span class="client-name">{{ client.name }}</span>
                    <span class="client-email">{{ client.email }}</span>
                  </div>
                </div>
              </div>
            </div>

          </div>

        </div>
      </Transition>

    </div>

  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'CaseManagersAdmin',

  data() {
    return {
      caseManagers: [],
      loading: false,

      selectedCM: null,
      activeTab: 'assigned',

      assignedClients: [],
      unassignedClients: [],
      loadingUnassigned: false,

      selectedClientIds: [],
      submitting: false,
    }
  },

  computed: {
    otherCaseManagers() {
      return this.caseManagers.filter(cm => cm.id !== this.selectedCM?.id)
    },
  },

  async mounted() {
    await this.loadCaseManagers()
  },

  methods: {
    async loadCaseManagers() {
      this.loading = true
      try {
        const { data } = await axios.get('/admin/case-managers')
        this.caseManagers = data
      } catch (e) { console.error(e) }
      finally { this.loading = false }
    },

    async selectCM(cm) {
      this.selectedCM = cm
      this.activeTab = 'assigned'
      this.selectedClientIds = []
      this.unassignedClients = []
      await Promise.all([
        this.loadAssignedClients(),
        this.loadUnassigned(),
      ])
    },

    async loadAssignedClients() {
      try {
        const { data } = await axios.get(`/admin/case-managers/${this.selectedCM.id}/clients`)
        this.assignedClients = data.clients
      } catch (e) { console.error(e) }
    },

    async loadUnassigned() {
      this.loadingUnassigned = true
      this.selectedClientIds = []
      try {
        const { data } = await axios.get('/admin/case-managers/unassigned-clients')
        this.unassignedClients = data
      } catch (e) { console.error(e) }
      finally { this.loadingUnassigned = false }
    },

    async assignSelected() {
      this.submitting = true
      try {
        await axios.post(`/admin/case-managers/${this.selectedCM.id}/assign`, {
          client_ids: this.selectedClientIds,
        })
        await this.loadCaseManagers()
        this.selectedCM = this.caseManagers.find(cm => cm.id === this.selectedCM.id)
        await this.loadAssignedClients()
        await this.loadUnassigned()
        this.selectedClientIds = []
      } catch (e) { console.error(e) }
      finally { this.submitting = false }
    },

    async unassignClient(clientId) {
      try {
        await axios.delete(`/admin/case-managers/${this.selectedCM.id}/unassign`, {
          data: { client_id: clientId },
        })
        await this.loadCaseManagers()
        this.selectedCM = this.caseManagers.find(cm => cm.id === this.selectedCM.id)
        await this.loadAssignedClients()
        await this.loadUnassigned()
      } catch (e) { console.error(e) }
    },

    async reassignClient(clientId, newCaseManagerId) {
      if (!newCaseManagerId) return
      try {
        await axios.patch('/admin/case-managers/reassign', {
          client_id: clientId,
          new_case_manager_id: newCaseManagerId,
        })
        await this.loadCaseManagers()
        this.selectedCM = this.caseManagers.find(cm => cm.id === this.selectedCM.id)
        await this.loadAssignedClients()
        await this.loadUnassigned()
      } catch (e) { console.error(e) }
    },

    toggleClientSelection(id) {
      const idx = this.selectedClientIds.indexOf(id)
      if (idx === -1) this.selectedClientIds.push(id)
      else this.selectedClientIds.splice(idx, 1)
    },

    toggleSelectAll() {
      if (this.selectedClientIds.length === this.unassignedClients.length) {
        this.selectedClientIds = []
      } else {
        this.selectedClientIds = this.unassignedClients.map(c => c.id)
      }
    },
  },
}
</script>

<style scoped>
.cm-page {
  padding: 24px;
  max-width: 1400px;
  margin: 0 auto;
  font-family: 'Segoe UI', sans-serif;
  color: #1e293b;
}

.page-header { margin-bottom: 24px; }
.page-title { font-size: 1.75rem; font-weight: 700; margin: 0; }
.page-subtitle { color: #64748b; margin: 4px 0 0; font-size: 0.88rem; }

.main-layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: 20px;
  transition: grid-template-columns .35s ease;
}
.main-layout.panel-open {
  grid-template-columns: 380px 1fr;
}

.cm-list-card {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 3px rgba(0,0,0,.06);
  overflow: hidden;
}

.card-header {
  display: flex; align-items: center; gap: 10px;
  padding: 18px 20px; border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
}
.card-header h2 { font-size: 1rem; font-weight: 700; margin: 0; }
.badge-count {
  background: #eff6ff; color: #3b82f6; border-radius: 20px;
  padding: 2px 10px; font-size: 0.78rem; font-weight: 600;
}

.cm-list { padding: 8px; }
.cm-item {
  display: flex; align-items: center; gap: 12px;
  padding: 12px; border-radius: 10px; cursor: pointer;
  transition: background .15s; border: 2px solid transparent;
}
.cm-item:hover { background: #f8fafc; }
.cm-item.active { background: #eff6ff; border-color: #bfdbfe; }

.cm-avatar {
  width: 44px; height: 44px; border-radius: 50%;
  object-fit: cover; border: 2px solid #e2e8f0; flex-shrink: 0;
}
.cm-info { flex: 1; min-width: 0; }
.cm-name { display: block; font-weight: 600; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cm-email { display: block; font-size: 0.78rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.cm-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; flex-shrink: 0; }
.clients-badge { font-size: 0.72rem; font-weight: 600; padding: 2px 8px; border-radius: 20px; }
.clients-badge.has-clients { background: #d1fae5; color: #065f46; }
.clients-badge.no-clients  { background: #f1f5f9; color: #64748b; }

.status-dot { width: 8px; height: 8px; border-radius: 50%; }
.status-dot.active   { background: #10b981; }
.status-dot.inactive { background: #cbd5e1; }

.detail-panel {
  background: #fff; border-radius: 12px; border: 1px solid #e2e8f0;
  box-shadow: 0 4px 20px rgba(0,0,0,.08);
  display: flex; flex-direction: column;
  overflow: hidden; height: fit-content; position: sticky; top: 20px;
}

.panel-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 18px 20px;
  background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
  color: #fff;
}
.panel-cm-info { display: flex; align-items: center; gap: 12px; }
.panel-avatar {
  width: 48px; height: 48px; border-radius: 50%;
  object-fit: cover; border: 2px solid rgba(255,255,255,.4);
}
.panel-cm-info h3 { margin: 0; font-size: 1rem; font-weight: 700; }
.panel-cm-info span { font-size: 0.82rem; opacity: .85; }

.panel-close {
  background: rgba(255,255,255,.15); border: none; color: #fff;
  border-radius: 6px; width: 30px; height: 30px; font-size: 0.9rem;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  transition: background .2s;
}
.panel-close:hover { background: rgba(255,255,255,.3); }

.tabs { display: flex; border-bottom: 1px solid #e2e8f0; background: #f8fafc; }
.tab {
  flex: 1; padding: 12px 16px; border: none; background: none;
  font-size: 0.875rem; font-weight: 600; color: #64748b;
  cursor: pointer; display: flex; align-items: center;
  justify-content: center; gap: 8px;
  border-bottom: 2px solid transparent; transition: all .2s;
}
.tab:hover { color: #1e293b; }
.tab.active { color: #3b82f6; border-bottom-color: #3b82f6; background: #fff; }

.tab-badge { background: #3b82f6; color: #fff; border-radius: 20px; padding: 1px 7px; font-size: 0.72rem; }
.tab-badge.neutral { background: #e2e8f0; color: #64748b; }

.tab-content { padding: 16px; max-height: 500px; overflow-y: auto; }

.empty-tab {
  display: flex; flex-direction: column; align-items: center;
  gap: 10px; padding: 32px 16px; color: #64748b; text-align: center;
}
.empty-icon { font-size: 2rem; }
.empty-tab p { margin: 0; font-size: 0.875rem; }

.btn-outline {
  background: none; border: 1px solid #3b82f6; color: #3b82f6;
  border-radius: 8px; padding: 8px 16px; font-size: 0.85rem;
  cursor: pointer; font-weight: 600; transition: all .2s;
}
.btn-outline:hover { background: #eff6ff; }

.clients-list { display: flex; flex-direction: column; gap: 4px; }
.client-item {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 12px; border-radius: 8px;
  border: 1px solid transparent; transition: background .15s;
}
.client-item:hover { background: #f8fafc; }
.client-item.selectable { cursor: pointer; }
.client-item.selected { background: #eff6ff; border-color: #bfdbfe; }

.client-avatar {
  width: 36px; height: 36px; border-radius: 50%;
  object-fit: cover; border: 2px solid #e2e8f0; flex-shrink: 0;
}
.client-info { flex: 1; min-width: 0; }
.client-name { display: block; font-weight: 600; font-size: 0.875rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.client-email { display: block; font-size: 0.76rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.client-actions { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
.reassign-select {
  border: 1px solid #e2e8f0; border-radius: 6px;
  padding: 4px 8px; font-size: 0.78rem; color: #475569;
  background: #fff; cursor: pointer; max-width: 130px;
}
.btn-unassign {
  background: none; border: 1px solid #fca5a5; color: #ef4444;
  border-radius: 6px; width: 28px; height: 28px; font-size: 0.75rem;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  transition: all .2s;
}
.btn-unassign:hover { background: #fee2e2; }

.select-all-bar {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 12px; background: #f8fafc;
  border-radius: 8px; margin-bottom: 10px; border: 1px solid #e2e8f0;
}
.checkbox-label {
  display: flex; align-items: center; gap: 8px;
  font-size: 0.85rem; font-weight: 600; color: #475569; cursor: pointer;
}
.btn-assign {
  background: #3b82f6; color: #fff; border: none;
  border-radius: 8px; padding: 7px 14px;
  font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: background .2s;
}
.btn-assign:hover { background: #1e40af; }
.btn-assign:disabled { opacity: .6; cursor: not-allowed; }

.loading-state { display: flex; justify-content: center; padding: 32px; }
.spinner {
  width: 28px; height: 28px; border: 3px solid #e2e8f0;
  border-top-color: #3b82f6; border-radius: 50%;
  animation: spin .7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.slide-panel-enter-active { transition: all .3s ease; }
.slide-panel-leave-active { transition: all .25s ease; }
.slide-panel-enter-from   { opacity: 0; transform: translateX(20px); }
.slide-panel-leave-to     { opacity: 0; transform: translateX(20px); }
</style>