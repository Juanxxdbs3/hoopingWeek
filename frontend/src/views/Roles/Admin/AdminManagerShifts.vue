<template>
  <div>
    <h2>Manager Shifts</h2>
    <form @submit.prevent="createShift">
      <input v-model.number="payload.manager_id" placeholder="manager_id" required />
      <input v-model.number="payload.field_id" placeholder="field_id" required />
      <input v-model.number="payload.day_of_week" type="number" min="0" max="6" placeholder="day_of_week" required />
      <input v-model="payload.start_time" placeholder="08:00:00" required />
      <input v-model="payload.end_time" placeholder="16:00:00" required />
      <input type="checkbox" v-model="payload.active" /> Active
      <input v-model="payload.note" placeholder="note" />
      <button type="submit">Crear turno</button>
    </form>

    <h3>Turnos existentes</h3>
    <ul>
      <li v-for="s in shifts" :key="s.id">
        ID {{s.id}} manager {{s.manager_id}} campo {{s.field_id}} dia {{s.day_of_week}} {{s.start_time}}-{{s.end_time}}
        <button @click="removeShift(s.id)">Eliminar</button>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import api from '@/services/api';

const shifts = ref([]);
const payload = ref({
  manager_id: null, field_id: null, day_of_week: 1, start_time: '08:00:00', end_time: '16:00:00', active: true, note: ''
});

async function loadShifts(){
  try {
    const res = await api.get('/api/manager-shifts', { params: { limit: 200 } });
    shifts.value = res.data?.manager_shifts?.data || [];
  } catch (e) { alert('Error al cargar turnos: ' + e.message); }
}

async function createShift(){
  try {
    const res = await api.post('/api/manager-shifts', payload.value);
    if(res.data?.ok) {
      alert('Turno creado');
      payload.value = { manager_id:null, field_id:null, day_of_week:1, start_time:'08:00:00', end_time:'16:00:00', active:true, note:'' };
      await loadShifts();
    } else alert('Error: ' + JSON.stringify(res.data));
  } catch (e) {
    alert('Error creando turno: ' + (e.response?.data?.detail || e.message));
  }
}

async function removeShift(id){
  if(!confirm('Eliminar turno ' + id + '?')) return;
  try {
    const res = await api.delete(`/api/manager-shifts/${id}`);
    if(res.data?.ok) {
      alert('Eliminado');
      await loadShifts();
    } else alert('Falló: ' + JSON.stringify(res.data));
  } catch (e) { alert('Error: ' + e.message); }
}

loadShifts();
</script>
