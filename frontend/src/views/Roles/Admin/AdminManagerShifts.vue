<template>
  <div class="container-fluid py-3">
    <AppBreadcrumb
      :crumbs="[{ label: 'Dashboard', to: '/admin', icon: 'bi bi-house' }, { label: 'Turnos Managers', to: '/admin/manager-shifts', icon: 'bi bi-clock-history', active: true }]" />
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="mb-0"><i class="bi bi-clock-history me-2"></i>Turnos de Managers</h2>
      <button class="btn btn-primary" @click="openModal()">
        <i class="bi bi-plus-circle me-2"></i>Nuevo Turno
      </button>
    </div>
    <div class="row mb-3">
      <div class="col-md-4">
        <label class="form-label">Filtrar por campo</label>
        <select v-model="selectedFieldId" class="form-select">
          <option value="">Todos los campos</option>
          <option v-for="f in fields" :key="f.id" :value="f.id">{{ f.name }} - {{ f.location }}</option>
        </select>
      </div>
    </div>
    <div class="card">
      <div class="card-body p-0">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Día</th>
              <th>Horario</th>
              <th>Turno</th>
              <th>Manager</th>
              <th>Campo</th>
              <th>Nota</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="day in days">
              <tr v-for="shift in shiftsByDay[day.idx]" :key="shift.id">
                <td>{{ day.name }}</td>
                <td>{{ shift.start_time }} - {{ shift.end_time }}</td>
                <td>
                  <span class="badge bg-info" v-if="isMorning(shift.start_time)">Matutino</span>
                  <span class="badge bg-secondary" v-else>Tarde</span>
                </td>
                <td>{{ getManagerName(shift.manager_id) }}</td>
                <td>{{ getFieldName(shift.field_id) }}</td>
                <td>
                  <span v-if="shift.note" :title="shift.note">
                    <i class="bi bi-info-circle"></i>
                  </span>
                </td>
                <td>
                  <button class="btn btn-sm btn-outline-primary me-1" @click="openModal(shift)"><i
                      class="bi bi-pencil"></i></button>
                  <button class="btn btn-sm btn-outline-danger" @click="removeShift(shift.id)"><i
                      class="bi bi-trash"></i></button>
                </td>
              </tr>
              <tr v-if="!shiftsByDay[day.idx] || shiftsByDay[day.idx].length === 0">
                <td>{{ day.name }}</td>
                <td colspan="6" class="text-muted">Sin turnos</td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>
    <ManagerShiftModal :show="showModal" :isEdit="!!editShift" :initialData="editShift" :fields="fields"
      :managers="managers" @saved="onModalSaved" @closed="closeModal" />
  </div>
</template>


<script setup>

import { ref, computed, onMounted, watch } from 'vue';
import { getFields, getUsers, getManagerShifts, createManagerShift, updateManagerShift, deleteManagerShift } from '@/services/api';
import AppBreadcrumb from '@/components/AppBreadcrumb.vue';
import ManagerShiftModal from '@/components/ManagerShiftModal.vue';

// Registrar el componente breadcrumb
defineExpose({ AppBreadcrumb });
const fields = ref([]);
const managers = ref([]);
const shifts = ref([]);
const selectedFieldId = ref('');
const showModal = ref(false);
const editShift = ref(null);

const days = [
  { idx: 0, name: 'Domingo' },
  { idx: 1, name: 'Lunes' },
  { idx: 2, name: 'Martes' },
  { idx: 3, name: 'Miércoles' },
  { idx: 4, name: 'Jueves' },
  { idx: 5, name: 'Viernes' },
  { idx: 6, name: 'Sábado' },
];

const shiftsByDay = computed(() => {
  const map = {};
  for (const d of days) map[d.idx] = [];
  for (const s of shifts.value) {
    if (map[s.day_of_week]) map[s.day_of_week].push(s);
  }
  // Ordenar por hora de inicio
  for (const d of days) map[d.idx].sort((a, b) => a.start_time.localeCompare(b.start_time));
  return map;
});

function isMorning(startTime) {
  // Matutino si inicia antes de 13:00
  if (!startTime) return false;
  const [h] = startTime.split(':').map(Number);
  return h < 13;
}

function getManagerName(id) {
  const m = managers.value.find(u => u.id === id);
  return m ? `${m.first_name} ${m.last_name}` : `ID ${id}`;
}
function getFieldName(id) {
  const f = fields.value.find(f => f.id === id);
  return f ? f.name : `ID ${id}`;
}

async function loadFields() {
  const res = await getFields();
  fields.value = res.data.fields?.data || res.data.data || [];
}
async function loadManagers() {
  const res = await getUsers({ role_id: 3, limit: 100 });
  // Filtrar por role_id=3 explícitamente por si la API no filtra bien
  managers.value = (res.data.users?.data || res.data.data || []).filter(u => u.role_id === 3);
}
async function loadShifts() {
  const params = { limit: 200 };
  if (selectedFieldId.value) params.field_id = selectedFieldId.value;
  const res = await getManagerShifts(params);
  shifts.value = res.data.manager_shifts?.data || res.data.data || [];
}

function openModal(shift = null) {
  editShift.value = shift ? { ...shift } : null;
  showModal.value = true;
}
function closeModal() {
  showModal.value = false;
  editShift.value = null;
}
async function onModalSaved(data) {
  try {
    // Forzar ids a number
    const payload = {
      ...data,
      manager_id: Number(data.manager_id),
      field_id: Number(data.field_id),
      day_of_week: Number(data.day_of_week),
    };
    if (editShift.value) {
      await updateManagerShift(editShift.value.id, payload);
      alert('Turno actualizado');
    } else {
      await createManagerShift(payload);
      alert('Turno creado');
    }
    closeModal();
    await loadShifts();
  } catch (e) {
    alert(e.response?.data?.detail || e.message || 'Error guardando turno');
  }
}
async function removeShift(id) {
  if (!confirm('¿Eliminar turno?')) return;
  try {
    await deleteManagerShift(id);
    await loadShifts();
  } catch (e) {
    alert(e.response?.data?.detail || e.message || 'Error eliminando turno');
  }
}

onMounted(async () => {
  await loadFields();
  await loadManagers();
  await loadShifts();
});

watch(selectedFieldId, loadShifts);
</script>

<style scoped>
.table th,
.table td {
  vertical-align: middle;
}
</style>
