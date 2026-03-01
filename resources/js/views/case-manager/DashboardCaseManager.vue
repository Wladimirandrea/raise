<script setup>
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
</script>

<template>
  <div class="min-h-screen bg-teal-700 text-white p-8">
    <div class="max-w-7xl mx-auto">
      <h1 class="text-5xl font-bold mb-6 text-center">
        Dashboard Case Manager
      </h1>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="bg-teal-800 p-8 rounded-2xl shadow-xl">
          <h2 class="text-3xl font-semibold mb-4">Turnos de Hoy</h2>
          <p class="text-6xl font-bold">0</p>
          <p class="mt-2 text-teal-200">Próximo turno: 10:00 AM</p>
        </div>

        <div class="bg-teal-800 p-8 rounded-2xl shadow-xl">
          <h2 class="text-3xl font-semibold mb-4">Clientes Atendidos</h2>
          <p class="text-6xl font-bold">0</p>
          <p class="mt-2 text-teal-200">Esta semana</p>
        </div>
      </div>

      <p class="mt-12 text-center text-teal-200 text-xl">
        ¡Bienvenido de nuevo, {{ auth.userName }}! Hora de brillar
      </p>
    </div>
  </div>
</template>