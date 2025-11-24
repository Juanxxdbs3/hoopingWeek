<template>
  <div class="card shadow-sm h-100">
    <div class="card-header bg-white d-flex align-items-center justify-content-between">
      <h5 class="mb-0"><i class="bi bi-trophy me-2"></i>Campeonatos</h5>
      <button class="btn btn-outline-primary btn-sm" @click="$emit('create')">
        <i class="bi bi-plus-circle"></i> Nuevo
      </button>
    </div>
    <div class="card-body p-2" style="max-height: 350px; overflow-y: auto;">
      <div v-if="loading" class="text-center py-4">
        <div class="spinner-border text-primary"></div>
      </div>
      <div v-else-if="championships.length === 0" class="text-center text-muted py-4">
        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
        No hay campeonatos
      </div>
      <ul v-else class="list-group list-group-flush">
        <li v-for="champ in championships" :key="champ.id" class="list-group-item d-flex justify-content-between align-items-center">
          <span>
            <strong>{{ champ.name }}</strong><br>
            <small class="text-muted">{{ champ.start_date }} - {{ champ.end_date }}</small>
          </span>
          <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" @click="$emit('edit', champ)">
              <i class="bi bi-list-task"></i> Matches
            </button>

            <!-- Solo PENDING muestra approve + reject -->
            <template v-if="champ.status === 'pending'">
              <button class="btn btn-sm btn-success" @click="$emit('approve', champ)">
                <i class="bi bi-check-circle"></i>
              </button>
              <button class="btn btn-sm btn-danger" @click="$emit('reject', champ)">
                <i class="bi bi-x-circle"></i>
              </button>
            </template>

            <!-- Si NO está pending: mostrar eliminar -->
            <template v-else>
              <button class="btn btn-sm btn-outline-danger" @click="$emit('delete', champ)">
                <i class="bi bi-trash"></i>
              </button>
            </template>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ChampionshipPanel',
  props: {
    championships: { type: Array, required: true },
    loading: { type: Boolean, default: false }
  },
  emits: ['create', 'edit', 'approve', 'reject', 'delete']
};
</script>

<style scoped>
.card-body {
  padding: 0.5rem 0.5rem 0 0.5rem;
}
</style>
