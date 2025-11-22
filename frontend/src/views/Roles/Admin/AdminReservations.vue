<template>
  <div class="container-fluid py-4">
    <div class="row">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h1 class="h3 mb-0">
            <i class="bi bi-calendar-check me-2"></i>
            Gestión de Reservas
          </h1>
          <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#reservationModal">
            <i class="bi bi-plus-circle me-2"></i>Nueva Reserva
          </button>
        </div>

        <!-- Filtros -->
        <div class="card shadow-sm mb-4">
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label">Estado</label>
                <select v-model="filters.status" class="form-select" @change="loadReservations">
                  <option value="">Todos</option>
                  <option value="pending">Pendientes</option>
                  <option value="approved">Aprobadas</option>
                  <option value="rejected">Rechazadas</option>
                  <option value="cancelled">Canceladas</option>
                </select>
              </div>
              
              <div class="col-md-3">
                <label class="form-label">Campo</label>
                <select v-model="filters.field_id" class="form-select" @change="loadReservations">
                  <option value="">Todos</option>
                  <option v-for="field in fields" :key="field.id" :value="field.id">
                    {{ field.name }}
                  </option>
                </select>
              </div>

              <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-primary w-100 d-block" @click="loadReservations">
                  <i class="bi bi-arrow-clockwise me-2"></i>Actualizar
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabla -->
        <div class="card shadow-sm">
          <div class="card-header bg-white">
            <h5 class="mb-0">Reservas ({{ reservations.length }})</h5>
          </div>
          
          <div class="card-body p-0">
            <div v-if="loading" class="text-center p-5">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
              </div>
            </div>

            <div v-else-if="reservations.length === 0" class="text-center p-5 text-muted">
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
                      <div class="btn-group btn-group-sm" role="group">
                        <button 
                          v-if="res.status === 'pending'" 
                          class="btn btn-success"
                          @click="approve(res.id)"
                          title="Aprobar"
                        >
                          <i class="bi bi-check-circle"></i>
                        </button>
                        <button 
                          v-if="res.status === 'pending'" 
                          class="btn btn-danger"
                          @click="reject(res.id)"
                          title="Rechazar"
                        >
                          <i class="bi bi-x-circle"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de creación -->
    <ReservationModal @reservation-saved="onReservationSaved" />
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue';
import { getReservations, getFields, approveReservation, rejectReservation } from '@/services/api';
import ReservationModal from '@/components/ReservationModal.vue';

export default {
  name: 'AdminReservations',
  components: { ReservationModal },
  setup() {
    const reservations = ref([]);
    const fields = ref([]);
    const loading = ref(false);
    
    const filters = reactive({
      status: '',
      field_id: '',
    });

    const loadReservations = async () => {
      loading.value = true;
      try {
        // ✅ FILTRAR PARAMS VACÍOS
        const params = { limit: 100, offset: 0 };
        
        if (filters.status && filters.status !== '') {
          params.status = filters.status;
        }
        if (filters.field_id && filters.field_id !== '') {
          params.field_id = filters.field_id;
        }
        
        const response = await getReservations(params);
        reservations.value = response.data.reservations?.data || response.data.data || [];
      } catch (error) {
        console.error('Error cargando reservas:', error);
        alert('Error al cargar reservas: ' + (error.response?.data?.detail || error.message));
      } finally {
        loading.value = false;
      }
    };

    const loadFields = async () => {
      try {
        const response = await getFields();
        fields.value = response.data.fields?.data || response.data.data || [];
      } catch (error) {
        console.error('Error cargando campos:', error);
      }
    };

    const getFieldName = (fieldId) => {
      const field = fields.value.find(f => f.id === fieldId);
      return field ? field.name : `Campo #${fieldId}`;
    };

    const approve = async (id) => {
      if (!confirm('¿Confirmar aprobación de esta reserva?')) return;
      try {
        await approveReservation(id);
        alert('✓ Reserva aprobada correctamente');
        loadReservations();
      } catch (error) {
        alert('Error: ' + (error.response?.data?.detail || 'No se pudo aprobar'));
      }
    };

    const reject = async (id) => {
      const reason = prompt('Motivo del rechazo:');
      if (!reason) return;
      try {
        await rejectReservation(id, reason);
        alert('✓ Reserva rechazada');
        loadReservations();
      } catch (error) {
        alert('Error: ' + (error.response?.data?.detail || 'No se pudo rechazar'));
      }
    };

    const onReservationSaved = () => {
      alert('✓ Reserva creada exitosamente');
      loadReservations();
    };

    const formatDate = (datetime) => {
      return new Date(datetime).toLocaleString('es-CO', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
      });
    };

    const formatActivityType = (type) => {
      const map = {
        practice_individual: 'Práctica Individual',
        practice_group: 'Práctica Grupal',
        match_friendly: 'Partido Amistoso',
        match_championship: 'Campeonato',
      };
      return map[type] || type;
    };

    const activityTypeBadge = (type) => {
      const map = {
        practice_individual: 'bg-info text-dark',
        practice_group: 'bg-primary',
        match_friendly: 'bg-warning text-dark',
        match_championship: 'bg-danger',
      };
      return map[type] || 'bg-secondary';
    };

    const statusBadge = (status) => {
      const map = {
        pending: 'bg-warning text-dark',
        approved: 'bg-success',
        rejected: 'bg-danger',
        cancelled: 'bg-secondary',
      };
      return map[status] || 'bg-secondary';
    };

    onMounted(() => {
      loadReservations();
      loadFields();
    });

    return {
      reservations,
      fields,
      loading,
      filters,
      loadReservations,
      getFieldName,
      approve,
      reject,
      onReservationSaved,
      formatDate,
      formatActivityType,
      activityTypeBadge,
      statusBadge,
    };
  },
};
</script>

<style scoped>
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
