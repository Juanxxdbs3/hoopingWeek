<template>
  <div class="field-list">
    <div class="card p-3 mb-3">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
          <h5 class="mb-0">Escenarios</h5>
          <small class="text-muted">Selecciona un campo</small>
        </div>
        <button class="btn btn-sm btn-primary" @click="$emit('open-create')">
          Crear Reserva
        </button>
      </div>

      <div v-if="loadingFields" class="text-center py-3">
        <div class="spinner-border spinner-border-sm" role="status"></div>
      </div>

      <ul v-if="fields.length" class="list-group">
        <li v-for="f in fields" :key="f.id" class="list-group-item d-flex justify-content-between align-items-center"
            :class="{ active: selected && selected.id === f.id }" @click="selectField(f)">
          <div>
            <div class="fw-bold">{{ f.name || ('Campo #' + f.id) }}</div>
            <div class="small text-muted">{{ f.locality || f.location || '' }}</div>
          </div>
          <div class="text-end">
            <small class="text-muted">Slots: {{ f.slots_count || '-' }}</small>
          </div>
        </li>
      </ul>

      <div v-if="!fields.length && !loadingFields" class="text-muted mt-3">
        No hay escenarios disponibles.
      </div>
    </div>

    <div v-if="selected" class="card p-3">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
          <h6 class="mb-0">Reservas — {{ selected.name || 'Campo ' + selected.id }}</h6>
          <small class="text-muted">Fecha: {{ dateFilter }}</small>
        </div>

        <div>
          <input type="date" v-model="dateFilter" class="form-control form-control-sm" @change="loadReservations" />
        </div>
      </div>

      <div v-if="loadingReservations" class="text-center py-3">
        <div class="spinner-border spinner-border-sm"></div>
      </div>

      <ul v-if="reservations.length" class="list-group">
        <li v-for="r in reservations" :key="r.id" class="list-group-item">
          <div class="d-flex justify-content-between">
            <div>
              <div class="fw-bold">{{ r.activity_type }} — {{ r.status }}</div>
              <div class="small text-muted">{{ r.start_datetime }} → {{ r.end_datetime }}</div>
              <div class="small">{{ r.notes || '' }}</div>
            </div>
            <div class="text-end">
              <small class="text-muted">Priority: {{ r.priority }}</small>
            </div>
          </div>
        </li>
      </ul>

      <div v-if="!reservations.length && !loadingReservations" class="text-muted">
        No hay reservas para la fecha seleccionada.
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/services/api';

const emits = defineEmits(['select-field', 'open-create']);

const fields = ref([]);
const loadingFields = ref(false);
const selected = ref(null);

const reservations = ref([]);
const loadingReservations = ref(false);
const dateFilter = ref(new Date().toISOString().slice(0,10)); // yyyy-mm-dd

async function loadFields() {
  loadingFields.value = true;
  try {
    const resp = await api.get('/api/fields');
    if (resp.data?.ok) {
      // ajustar shape
      fields.value = resp.data.fields?.data || resp.data.data || resp.data || [];
    } else {
      fields.value = [];
    }
  } catch (e) {
    console.warn('No /api/fields', e?.response?.status || e.message);
    fields.value = [];
  } finally {
    loadingFields.value = false;
  }
}

function selectField(f) {
  selected.value = f;
  emits('select-field', f);
  loadReservations();
}

async function loadReservations() {
  if (!selected.value) return;
  loadingReservations.value = true;
  try {
    const resp = await api.get('/api/reservations', { params: { field_id: selected.value.id, status: 'approved', limit: 100, offset: 0 } });
    if (resp.data?.ok) {
      // adaptando shape posible
      reservations.value = resp.data.reservations?.data || resp.data.data || resp.data?.reservations || [];
    } else {
      reservations.value = [];
    }
  } catch (e) {
    console.error('Error cargando reservas', e);
    reservations.value = [];
  } finally {
    loadingReservations.value = false;
  }
}

onMounted(async () => {
  await loadFields();
  // si hay al menos uno, seleccionamos el primero
  if (fields.value.length) {
    selectField(fields.value[0]);
  }
});
</script>

<style scoped>
.field-list .list-group-item { cursor: pointer; }
.field-list .list-group-item.active { background: var(--bs-primary); color: white; }
</style>
