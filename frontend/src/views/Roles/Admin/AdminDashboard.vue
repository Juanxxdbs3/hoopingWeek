<template>
  <div class="container-fluid p-0">
    <div class="d-flex">
      <!-- SIDEBAR -->
      <div class="menu">
        <div class="menu-container">
          <div class="profile-container text-center">
            <img 
              :src="`https://ui-avatars.com/api/?name=${user.first_name}+${user.last_name}&background=667eea&color=fff`" 
              alt="Profile" 
              class="profile-img"
            />
            <p class="profile-title mt-2">{{ user.first_name }} {{ user.last_name }}</p>
            <p class="profile-subtitle">{{ user.email }}</p>
            <span class="badge bg-primary mb-3">{{ user.role_name }}</span>
            <button @click="logout" class="logout-btn btn btn-outline-danger btn-sm w-100">
              Cerrar Sesión
            </button>
          </div>
          
          <div class="menu-btn menu-active mt-4">
            <i class="bi bi-speedometer2 me-2"></i>
            <p class="menu-text d-inline">Dashboard</p>
          </div>
        </div>
      </div>

      <!-- CONTENIDO PRINCIPAL -->
      <div class="dash-body p-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h2 class="heading-main12 mb-1">Panel de Control</h2>
            <p class="text-muted">Bienvenido, {{ user.first_name }}</p>
          </div>
          <div class="text-end">
            <small class="text-muted">Fecha de hoy</small>
            <p class="mb-0 fw-bold">{{ currentDate }}</p>
          </div>
        </div>
        
        <!-- ESTADÍSTICAS -->
        <div class="row g-3 mt-2">
          <div class="col-md-3">
            <div class="dashboard-card">
              <div class="card-icon bg-primary">
                <i class="bi bi-calendar-check"></i>
              </div>
              <div>
                <div class="card-number">{{ stats.total }}</div>
                <div class="card-label">Reservas Totales</div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="dashboard-card">
              <div class="card-icon bg-warning">
                <i class="bi bi-clock-history"></i>
              </div>
              <div>
                <div class="card-number">{{ stats.pending }}</div>
                <div class="card-label">Pendientes</div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="dashboard-card">
              <div class="card-icon bg-success">
                <i class="bi bi-check-circle"></i>
              </div>
              <div>
                <div class="card-number">{{ stats.approved }}</div>
                <div class="card-label">Aprobadas</div>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="dashboard-card">
              <div class="card-icon bg-danger">
                <i class="bi bi-x-circle"></i>
              </div>
              <div>
                <div class="card-number">{{ stats.rejected }}</div>
                <div class="card-label">Rechazadas</div>
              </div>
            </div>
          </div>
        </div>

        <!-- TABLA DE RESERVAS -->
        <div class="mt-5">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Listado de Reservas</h4>
            <button class="btn btn-primary btn-sm" @click="fetchReservations">
              <i class="bi bi-arrow-clockwise me-1"></i>
              Actualizar
            </button>
          </div>
          
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
          </div>
          
          <div v-else class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>ID</th>
                  <th>Solicitante</th>
                  <th>Campo</th>
                  <th>Fecha Inicio</th>
                  <th>Tipo</th>
                  <th>Estado</th>
                  <th class="text-center">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="res in reservations" :key="res.id">
                  <td><strong>#{{ res.id }}</strong></td>
                  <td>{{ res.applicant_id }}</td>
                  <td>Campo {{ res.field_id }}</td>
                  <td>{{ formatDate(res.start_datetime) }}</td>
                  <td><span class="badge bg-info">{{ res.activity_type }}</span></td>
                  <td>
                    <span :class="getStatusClass(res.status)">
                      {{ getStatusLabel(res.status) }}
                    </span>
                  </td>
                  <td class="text-center">
                    <div v-if="res.status === 'pending'" class="btn-group btn-group-sm">
                      <button 
                        @click="approveReservation(res.id)" 
                        class="btn btn-success"
                        title="Aprobar"
                      >
                        <i class="bi bi-check-lg"></i>
                      </button>
                      <button 
                        @click="rejectReservation(res.id)" 
                        class="btn btn-danger"
                        title="Rechazar"
                      >
                        <i class="bi bi-x-lg"></i>
                      </button>
                    </div>
                    <span v-else class="text-muted">-</span>
                  </td>
                </tr>
                <tr v-if="reservations.length === 0">
                  <td colspan="7" class="text-center py-4 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    No hay reservas registradas
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../../services/api';
import { useAuth } from '../../../composables/useAuth';

const router = useRouter();
const auth = useAuth();

const user = ref({});
const reservations = ref([]);
const loading = ref(false);

const currentDate = computed(() => {
  return new Date().toLocaleDateString('es-ES', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
});

const stats = computed(() => {
  return {
    total: reservations.value.length,
    pending: reservations.value.filter(r => r.status === 'pending').length,
    approved: reservations.value.filter(r => r.status === 'approved').length,
    rejected: reservations.value.filter(r => r.status === 'rejected').length
  };
});

onMounted(async () => {
  const storedUser = auth.getUser();
  if (!storedUser) {
    router.push('/login');
    return;
  }
  user.value = storedUser;
  await fetchReservations();
});

const fetchReservations = async () => {
  loading.value = true;
  try {
    const resp = await api.get('/api/reservations', {
      params: { limit: 100, offset: 0 }
    });
    
    if (resp.data && resp.data.ok) {
      reservations.value = resp.data.reservations?.data || resp.data.reservations || [];
    } else {
      reservations.value = [];
    }
  } catch (e) {
    console.error("Error cargando reservas:", e?.response?.data || e);
    alert('Error al cargar las reservas');
  } finally {
    loading.value = false;
  }
};

const approveReservation = async (id) => {
  if (!confirm('¿Aprobar esta reserva?')) return;
  
  try {
    await api.patch(`/api/reservations/${id}/approve`, {
      approver_id: user.value.id,
      note: "Aprobado desde Dashboard Web"
    });
    await fetchReservations();
  } catch (error) {
    const msg = error?.response?.data?.detail || error.message;
    alert('Error al aprobar: ' + msg);
  }
};

const rejectReservation = async (id) => {
  const reason = prompt('Motivo del rechazo:');
  if (!reason) return;
  
  try {
    await api.patch(`/api/reservations/${id}/reject`, {
      approver_id: user.value.id,
      rejection_reason: reason
    });
    await fetchReservations();
  } catch (error) {
    const msg = error?.response?.data?.detail || error.message;
    alert('Error al rechazar: ' + msg);
  }
};

const logout = () => {
  if (confirm('¿Cerrar sesión?')) {
    auth.logout();
  }
};

const getStatusClass = (status) => {
  const classes = {
    'approved': 'badge bg-success',
    'rejected': 'badge bg-danger',
    'pending': 'badge bg-warning text-dark',
    'cancelled': 'badge bg-secondary'
  };
  return classes[status] || 'badge bg-secondary';
};

const getStatusLabel = (status) => {
  const labels = {
    'approved': 'Aprobada',
    'rejected': 'Rechazada',
    'pending': 'Pendiente',
    'cancelled': 'Cancelada'
  };
  return labels[status] || status;
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleString('es-ES', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>

<style scoped>
.menu {
  width: 280px;
  background: white;
  min-height: 100vh;
  border-right: 1px solid #e2e8f0;
  padding: 20px;
}

.profile-img {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid #667eea;
}

.profile-title {
  font-size: 18px;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 4px;
}

.profile-subtitle {
  font-size: 13px;
  color: #718096;
}

.menu-btn {
  padding: 12px 16px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.menu-active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.dash-body {
  flex: 1;
  background-color: #f7fafc;
  min-height: 100vh;
}

.heading-main12 {
  font-size: 28px;
  font-weight: 700;
  color: #2d3748;
}

.dashboard-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  transition: transform 0.2s, box-shadow 0.2s;
}

.dashboard-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.card-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: white;
}

.card-number {
  font-size: 32px;
  font-weight: 700;
  color: #2d3748;
}

.card-label {
  font-size: 14px;
  color: #718096;
}

.table {
  background: white;
  border-radius: 12px;
  overflow: hidden;
}

.table thead {
  background-color: #f7fafc;
  font-weight: 600;
  font-size: 14px;
  color: #4a5568;
}

.table tbody tr {
  transition: background-color 0.2s;
}

.table tbody tr:hover {
  background-color: #f7fafc;
}
</style>