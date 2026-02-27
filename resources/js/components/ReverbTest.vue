<script setup>
import { onMounted, onUnmounted, ref } from 'vue';

const messages = ref([]);

onMounted(() => {
    window.Echo.channel('test-channel')
        .listen('.test.notification', (e) => {
            messages.value.push(e.message);
            console.log('Evento recibido:', e);
        });
});

onUnmounted(() => {
    window.Echo.leave('test-channel');
});
</script>

<template>
  <div class="p-4 bg-gray-100 rounded">
    <h2 class="text-xl font-bold">Prueba Reverb - Raise</h2>
    <p>Escuchando en canal público 'test-channel'...</p>
    
    <ul class="mt-4 space-y-2">
      <li v-for="(msg, index) in messages" :key="index" class="p-2 bg-white rounded shadow">
        {{ msg }}
      </li>
    </ul>
  </div>
</template>