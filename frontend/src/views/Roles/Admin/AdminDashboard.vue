<template>
  <div class="admin-layout">
    <AppBreadcrumb :crumbs="[
      { label: 'Dashboard', to: '/admin', icon: 'bi bi-house', active: true }
    ]" />
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="profile-section">
        <div class="avatar">{{ userInitials }}</div>
        <h3 class="user-name">{{ userName }}</h3>
        <p class="user-email">{{ userEmail }}</p>
        <span class="badge-role">super_admin</span>
      </div>
      <button class="btn-logout" @click="logout">Cerrar Sesión</button>
      <nav class="sidebar-nav">
        <router-link to="/admin/dashboard" class="nav-item" active-class="active">
          <i class="bi bi-speedometer2"></i>
          Dashboard
        </router-link>
        <router-link to="/admin/reservations" class="nav-item" active-class="active">
          <i class="bi bi-calendar-check"></i>
          Reservas
        </router-link>
        <router-link to="/admin/teams" class="nav-item" active-class="active">
          <i class="bi bi-people-fill"></i>
          Equipos
        </router-link>
        <router-link to="/admin/users" class="nav-item" active-class="active">
          <i class="bi bi-people"></i>
          Usuarios
        </router-link>
        <router-link to="/admin/manager-shifts" class="nav-item" active-class="active">
          <i class="bi bi-clock-history"></i>
          Turnos Managers
        </router-link>
      </nav>
    </aside>
    <main class="main-content">
      <header class="dashboard-header">
        <div>
          <h1 class="dashboard-title">Panel de Control</h1>
          <p class="dashboard-subtitle">Bienvenido, {{ firstName }}</p>
        </div>
        <div class="header-date">
          <small>Fecha de hoy</small>
          <strong>{{ currentDate }}</strong>
        </div>
      </header>
      <!-- Tarjetas de estadísticas reales -->
      <div class="stats-grid">
        <div class="stat-card stat-primary">
          <div class="stat-icon">
            <i class="bi bi-calendar-check-fill"></i>
          </div>
          <div class="stat-info">
            <h3>{{ stats.total }}</h3>
            <p>Reservas Totales</p>
          </div>
        </div>
        <div class="stat-card stat-warning">
          <div class="stat-icon">
            <i class="bi bi-clock-fill"></i>
          </div>
          <div class="stat-info">
            <h3>{{ stats.pending }}</h3>
            <p>Pendientes</p>
          </div>
        </div>
        <div class="stat-card stat-success">
          <div class="stat-icon">
            <i class="bi bi-check-circle-fill"></i>
          </div>
          <div class="stat-info">
            <h3>{{ stats.approved }}</h3>
            <p>Aprobadas</p>
          </div>
        </div>
        <div class="stat-card stat-danger">
          <div class="stat-icon">
            <i class="bi bi-x-circle-fill"></i>
          </div>
          <div class="stat-info">
            <h3>{{ stats.rejected }}</h3>
            <p>Rechazadas</p>
          </div>
        </div>
      </div>

      <!-- Paneles adicionales -->
      <div class="row" style="gap: 1.5rem 0;">
        <div class="col-12 col-md-6 mb-4">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title mb-3">Campo más usado esta semana</h5>
              <div class="text-muted">(Próximamente)</div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-body d-flex align-items-center justify-content-center text-muted">
              (Panel vacío)
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script>

import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { getReservations, approveReservation, rejectReservation } from '@/services/api';
import AppBreadcrumb from '@/components/AppBreadcrumb.vue';

export default {
  name: 'AdminDashboard',
  components: { AppBreadcrumb },
  setup() {
    const router = useRouter();
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    const reservations = ref([]);
    const loading = ref(false);

    const userName = computed(() => `${user.first_name || ''} ${user.last_name || ''}`);
    const userEmail = computed(() => user.email || '');
    const firstName = computed(() => user.first_name || 'Admin');
    const userInitials = computed(() => {
      const first = user.first_name?.[0] || '';
      const last = user.last_name?.[0] || '';
      return (first + last).toUpperCase() || 'BM';
    });

    const currentDate = computed(() => {
      return new Date().toLocaleDateString('es-ES', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
      });
    });

    const stats = computed(() => {
      return {
        total: reservations.value.length,
        pending: reservations.value.filter(r => r.status === 'pending').length,
        approved: reservations.value.filter(r => r.status === 'approved').length,
        rejected: reservations.value.filter(r => r.status === 'rejected').length,
      };
    });

    const loadReservations = async () => {
      loading.value = true;
      try {
        const response = await getReservations();
        reservations.value = response.data.reservations?.data || response.data.data || [];
      } catch (error) {
        console.error('Error cargando reservas:', error);
      } finally {
        loading.value = false;
      }
    };

    const approve = async (id) => {
      if (!confirm('¿Aprobar esta reserva?')) return;
      try {
        await approveReservation(id);
        alert('Reserva aprobada');
        loadReservations();
      } catch (error) {
        alert('Error al aprobar: ' + (error.response?.data?.detail || 'Error desconocido'));
      }
    };

    const reject = async (id) => {
      const reason = prompt('Motivo del rechazo:');
      if (!reason) return;
      try {
        await rejectReservation(id, reason);
        alert('Reserva rechazada');
        loadReservations();
      } catch (error) {
        alert('Error al rechazar: ' + (error.response?.data?.detail || 'Error desconocido'));
      }
    };

    const logout = () => {
      localStorage.removeItem('access_token');
      localStorage.removeItem('user');
      router.push('/login');
    };

    const formatDate = (datetime) => {
      return new Date(datetime).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      });
    };

    const formatType = (type) => {
      const map = {
        practice_individual: 'Práctica',
        practice_group: 'Grupal',
        match_friendly: 'Amistoso',
        match_championship: 'Campeonato',
      };
      return map[type] || type;
    };

    const typeBadge = (type) => {
      const map = {
        match_championship: 'bg-info',
        match_friendly: 'bg-primary',
        practice_group: 'bg-success',
        practice_individual: 'bg-secondary',
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
    });

    return {
      userName,
      userEmail,
      firstName,
      userInitials,
      currentDate,
      reservations,
      loading,
      stats,
      loadReservations,
      approve,
      reject,
      logout,
      formatDate,
      formatType,
      typeBadge,
      statusBadge,
    };
  },
};
</script>

<style scoped>
.admin-layout {
  display: flex;
  min-height: 100vh;
  background: #f8f9fa;
}

.sidebar {
  width: 280px;
  background: white;
  border-right: 1px solid #e0e0e0;
  padding: 2rem 0;
  position: fixed;
  height: 100vh;
  overflow-y: auto;
}

.profile-section {
  text-align: center;
  padding: 0 1.5rem 1.5rem;
  border-bottom: 1px solid #e0e0e0;
  margin-bottom: 1rem;
}

.avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: #6366f1;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  font-weight: bold;
  margin: 0 auto 1rem;
}

.user-name {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0.5rem 0 0.25rem;
}

.user-email {
  font-size: 0.85rem;
  color: #6c757d;
  margin: 0;
}

.badge-role {
  display: inline-block;
  background: #6366f1;
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  margin-top: 0.5rem;
}

.btn-logout {
  width: calc(100% - 3rem);
  margin: 1rem 1.5rem;
  padding: 0.6rem;
  background: #dc3545;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-logout:hover {
  background: #c82333;
}

.sidebar-nav {
  padding: 0 1rem;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  color: #495057;
  text-decoration: none;
  border-radius: 8px;
  margin-bottom: 0.5rem;
  transition: all 0.2s;
}

.nav-item:hover {
  background: #f1f3f5;
  color: #6366f1;
}

.nav-item.active {
  background: #6366f1;
  color: white;
}

.main-content {
  margin-left: 280px;
  flex: 1;
  padding: 2rem 0 2rem 0;
  min-width: 0;
}
.dashboard-header {
  display: flex;
  justify-content: flex-start;
  align-items: center;
  margin-bottom: 2rem;
  gap: 2rem;
}
.dashboard-title {
  font-size: 1.75rem;
  font-weight: 600;
  margin: 0;
  text-align: left;
}
.dashboard-subtitle {
  color: #6c757d;
  margin: 0.25rem 0 0;
  text-align: left;
}
.header-date {
  text-align: right;
  margin-left: auto;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.dashboard-title {
  font-size: 1.75rem;
  font-weight: 600;
  margin: 0;
}

.dashboard-subtitle {
  color: #6c757d;
  margin: 0.25rem 0 0;
}

.header-date {
  text-align: right;
}

.header-date small {
  display: block;
  color: #6c757d;
  font-size: 0.85rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  gap: 1rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.stat-primary .stat-icon {
  background: #e3f2fd;
  color: #1976d2;
}

.stat-warning .stat-icon {
  background: #fff3e0;
  color: #f57c00;
}

.stat-success .stat-icon {
  background: #e8f5e9;
  color: #388e3c;
}

.stat-danger .stat-icon {
  background: #ffebee;
  color: #d32f2f;
}

.stat-info h3 {
  font-size: 2rem;
  font-weight: 700;
  margin: 0 0 0.25rem;
}

.stat-info p {
  margin: 0;
  color: #6c757d;
  font-size: 0.9rem;
}

.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.card-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e0e0e0;
  background: white;
}
</style>