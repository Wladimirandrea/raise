<template>
  <div class="card-wrapper">

    <!-- Accent line top -->
    <div class="card-accent"></div>

    <!-- Role badge -->
    <div class="role-badge">
      <span class="role-dot"></span>
      <span>{{ role }}</span>
    </div>

    <!-- Action buttons -->
    <div class="action-buttons">
      <button class="action-btn view-btn" @click="$emit('view')" title="Ver">
        <span class="btn-inner">👁</span>
      </button>
      <button class="action-btn edit-btn" @click="$emit('edit')" title="Editar">
        <span class="btn-inner">✏️</span>
      </button>
      <button class="action-btn delete-btn" @click="$emit('delete')" title="Eliminar">
        <span class="btn-inner">🗑</span>
      </button>
    </div>

    <!-- Avatar -->
    <div class="avatar-container">
      <div class="avatar-ring">
        <img :src="avatar || '/storage/avatars/default.png'" :alt="name" class="avatar-img" />
      </div>
    </div>

    <!-- Name plate -->
    <div class="name-plate">
      <span class="plate-name">{{ name.toUpperCase() }}</span>
      <span class="plate-divider"></span>
    </div>

  </div>
</template>

<script setup>
defineProps({
  name:   { type: String, default: 'Pedro Perez' },
  role:   { type: String, default: 'Cliente' },
  avatar: { type: String, default: null },
})

defineEmits(['view', 'edit', 'delete'])
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@300;400;500&display=swap');

/* ─── CARD ─────────────────────────────────── */
.card-wrapper {
  position: relative;
  width: 260px;
  height: 340px;
  background: linear-gradient(160deg, #0f172a 0%, #1e293b 60%, #0f172a 100%);
  border-radius: 16px;
  overflow: hidden;
  box-shadow:
    0 0 0 1px rgba(148, 163, 184, 0.08),
    0 24px 48px rgba(0, 0, 0, 0.6),
    0 4px 16px rgba(0, 0, 0, 0.4);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  padding-bottom: 28px;
  user-select: none;
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.card-wrapper:hover {
  transform: translateY(-4px);
  box-shadow:
    0 0 0 1px rgba(99, 179, 237, 0.2),
    0 32px 56px rgba(0, 0, 0, 0.7),
    0 4px 16px rgba(0, 0, 0, 0.4);
}

/* Noise texture overlay */
.card-wrapper::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
  pointer-events: none;
  z-index: 0;
}

/* ─── ACCENT LINE ───────────────────────────── */
.card-accent {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, #3b82f6, #06b6d4, #3b82f6);
  background-size: 200% 100%;
  animation: shimmer 3s linear infinite;
  z-index: 2;
}

@keyframes shimmer {
  0%   { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* ─── ROLE BADGE ────────────────────────────── */
.role-badge {
  position: absolute;
  top: 18px;
  right: 16px;
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 20px;
  padding: 4px 12px 4px 8px;
  backdrop-filter: blur(8px);
  z-index: 2;
}

.role-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #34d399;
  box-shadow: 0 0 6px #34d399;
  animation: pulse-dot 2s ease-in-out infinite;
}

@keyframes pulse-dot {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: 0.6; transform: scale(0.85); }
}

.role-badge span:last-child {
  font-family: 'DM Sans', sans-serif;
  font-size: 11px;
  font-weight: 500;
  color: #94a3b8;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}

/* ─── ACTION BUTTONS ────────────────────────── */
.action-buttons {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-60%);
  display: flex;
  flex-direction: column;
  gap: 10px;
  z-index: 2;
}

.action-btn {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 15px;
  transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
  backdrop-filter: blur(8px);
}

.action-btn:hover {
  transform: translateY(-2px);
}

.action-btn:active {
  transform: scale(0.93);
}

.view-btn {
  background: rgba(59, 130, 246, 0.15);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
}
.view-btn:hover {
  border-color: rgba(59, 130, 246, 0.4);
  box-shadow: 0 6px 16px rgba(59, 130, 246, 0.35);
}

.edit-btn {
  background: rgba(234, 179, 8, 0.12);
  box-shadow: 0 4px 12px rgba(234, 179, 8, 0.15);
}
.edit-btn:hover {
  border-color: rgba(234, 179, 8, 0.35);
  box-shadow: 0 6px 16px rgba(234, 179, 8, 0.3);
}

.delete-btn {
  background: rgba(239, 68, 68, 0.12);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15);
}
.delete-btn:hover {
  border-color: rgba(239, 68, 68, 0.35);
  box-shadow: 0 6px 16px rgba(239, 68, 68, 0.3);
}

/* ─── AVATAR ────────────────────────────────── */
.avatar-container {
  margin-bottom: 16px;
  z-index: 1;
}

.avatar-ring {
  width: 110px;
  height: 110px;
  border-radius: 50%;
  padding: 2px;
  background: linear-gradient(135deg, #3b82f6, #06b6d4);
  box-shadow: 0 0 24px rgba(59, 130, 246, 0.3);
}

.avatar-img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
  object-position: top;
  border: 2px solid #0f172a;
}

/* ─── NAME PLATE ────────────────────────────── */
.name-plate {
  width: 80%;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  z-index: 1;
}

.plate-name {
  font-family: 'DM Serif Display', serif;
  font-size: 16px;
  color: #f1f5f9;
  letter-spacing: 2px;
  text-align: center;
  text-shadow: 0 2px 8px rgba(0,0,0,0.5);
}

.plate-divider {
  display: block;
  width: 40px;
  height: 1px;
  background: linear-gradient(90deg, transparent, #3b82f6, transparent);
}
</style>