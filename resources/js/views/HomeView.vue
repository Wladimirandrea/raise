<template>
  <div class="max-w-4xl mx-auto mt-10 text-center">
    <h1 class="text-4xl font-bold mb-6">Bienvenido a Raise</h1>
    <p class="text-xl mb-8">Sistema de gestión de citas con notificaciones en tiempo real</p>
    
    <div class="space-x-4">
      <router-link 
        to="/register" 
        class="bg-blue-600 text-white px-8 py-4 rounded-lg hover:bg-blue-700"
      >
        Registrarse
      </router-link>
      <router-link 
        to="/login" 
        class="bg-gray-600 text-white px-8 py-4 rounded-lg hover:bg-gray-700"
      >
        Iniciar sesión
      </router-link>
    </div>
  </div>
</template>