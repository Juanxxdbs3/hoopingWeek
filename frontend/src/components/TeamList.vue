<template>
  <div class="card shadow-sm border-0">
    <div class="card-body p-0">
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
      </div>

      <div v-else-if="teams.length === 0" class="text-center text-muted py-5">
        <i class="bi bi-people fs-1 d-block mb-2 opacity-50"></i>
        <p class="mb-0">No se encontraron equipos.</p>
      </div>

      <div v-else class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-3">Nombre</th>
              <th>Deporte</th>
              <th>Tipo</th>
              <th>Entrenador (ID)</th>
              <th>Localidad</th>
              <th class="text-end pe-3">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="team in teams" :key="team.id">
              <td class="ps-3 fw-bold text-primary">{{ team.name }}</td>
              <td>
                <span class="badge bg-light text-dark border">{{ team.sport }}</span>
              </td>
              <td>{{ team.type }}</td>
              <td>
                <span v-if="team.trainer_id" class="badge bg-secondary">ID: {{ team.trainer_id }}</span>
                <span v-else class="text-muted">-</span>
              </td>
              <td>{{ team.locality || '-' }}</td>
              <td class="text-end pe-3">
                <button class="btn btn-sm btn-outline-secondary me-2" @click="$emit('edit', team)" title="Editar">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" @click="$emit('delete', team)" title="Eliminar">
                  <i class="bi bi-trash"></i>
                </button>
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
  name: 'TeamList',
  props: {
    teams: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false }
  },
  emits: ['edit', 'delete']
};
</script>