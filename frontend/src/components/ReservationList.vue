<template>
  <div class="card shadow-sm h-100">
    <div class="card-header bg-white d-flex align-items-center justify-content-between">
      <h5 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Reservas</h5>
      <span class="badge bg-primary">{{ reservations.length }}</span>
    </div>
    <div class="card-body p-0" style="max-height: 60vh; overflow-y: auto;">
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary"></div>
      </div>
      <div v-else-if="reservations.length === 0" class="text-center py-5 text-muted">
        <i class="bi bi-inbox fs-1 d-block mb-3"></i>
        <p class="mb-0">No hay reservas para mostrar</p>
      </div>
      <div v-else class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Campo</th>
              <th>Tipo</th>
              <th>Inicio</th>
              <th>Fin</th>
              <th>Duración</th>
              <th>Solicitante</th>
              <th>Estado</th>
              <th class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="res in reservations" :key="res.id">
              <td><strong>#{{ res.id }}</strong></td>
              <td>{{ getFieldName(res.field_id) }}</td>
              <td>
                <span class="badge" :class="activityTypeBadge(res.activity_type)">
                  {{ formatActivityType(res.activity_type) }}
                </span>
              </td>
              <td>{{ formatDate(res.start_datetime) }}</td>
              <td>{{ formatDate(res.end_datetime) }}</td>
              <td>{{ res.duration_hours }}h</td>
              <td>User #{{ res.applicant_id }}</td>
              <td>
                <span class="badge" :class="statusBadge(res.status)">
                  {{ res.status.toUpperCase() }}
                </span>
              </td>
              <td class="text-center">
                <slot name="actions" :reservation="res"></slot>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ReservationList',
  props: {
    reservations: { type: Array, required: true },
    loading: { type: Boolean, default: false },
    getFieldName: { type: Function, required: true },
    formatDate: { type: Function, required: true },
    formatActivityType: { type: Function, required: true },
    activityTypeBadge: { type: Function, required: true },
    statusBadge: { type: Function, required: true }
  }
};
</script>

<style scoped>
.card-body {
  padding: 0;
}
.table {
  width: 100%;
  border-collapse: collapse;
}
.table th,
.table td {
  padding: 8px;
  border: 1px solid #ddd;
}
</style>
