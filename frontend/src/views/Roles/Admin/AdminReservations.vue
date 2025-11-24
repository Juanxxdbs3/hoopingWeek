<template>
  <div class="container-fluid py-4">
    <AppBreadcrumb :crumbs="[
      { label: 'Dashboard', to: '/admin/dashboard', icon: 'bi bi-house' },
      { label: 'Reservas', to: '/admin/reservations', icon: 'bi bi-calendar-check', active: true }
    ]" />

    <div class="d-flex flex-wrap align-items-start justify-content-between mb-3 gap-3">
      <div>
        <h2 class="fw-bold mb-2">Gestión de Reservas</h2>
        <button class="btn btn-success mb-3" @click="openReservationModal">
          <i class="bi bi-plus-circle"></i> Nueva Reserva
        </button>
        <div class="d-flex gap-2 mb-3">
          <select v-model="filterStatus" class="form-select form-select-sm" style="width: 140px;">
            <option value="">Estado</option>
            <option value="pending">Pendientes</option>
            <option value="approved">Aprobadas</option>
            <option value="rejected">Rechazadas</option>
            <option value="cancelled">Canceladas</option>
          </select>
          <select v-model="filterField" class="form-select form-select-sm" style="width: 140px;">
            <option value="">Campo</option>
            <option v-for="field in fields" :key="field.id" :value="field.id">
              {{ field.name }}
            </option>
          </select>
          <button class="btn btn-primary btn-sm" @click="refreshAll">
            <i class="bi bi-arrow-clockwise"></i> Actualizar
          </button>
        </div>
      </div>
      <div style="min-width:340px; max-width:420px; flex:1;">
        <ChampionshipPanel
          :championships="championships"
          :loading="loadingChamps"
          @create="openChampionshipModal"
          @edit="selectChampionship"
          @approve="approveChampionship"
          @reject="rejectChampionship"
          @delete="deleteChampionship"
        />
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <ReservationList
          :reservations="filteredReservations"
          :loading="loading"
          :getFieldName="getFieldName"
          :formatDate="formatDate"
          :formatActivityType="formatActivityType"
          :activityTypeBadge="activityTypeBadge"
          :statusBadge="statusBadge"
        >
          <template #actions="{ reservation }">
            <div class="btn-group btn-group-sm" role="group">
              <button v-if="reservation.status === 'pending'" class="btn btn-success" @click="approve(reservation.id)" title="Aprobar">
                <i class="bi bi-check-circle"></i>
              </button>
              <button v-if="reservation.status === 'pending'" class="btn btn-danger" @click="reject(reservation.id)" title="Rechazar">
                <i class="bi bi-x-circle"></i>
              </button>
            </div>
          </template>
        </ReservationList>
      </div>
    </div>

    <!-- Modal de reserva (componente externo debe contener element id=reservationModal) -->
    <ReservationModal ref="reservationModal" @reservation-saved="refreshAll" />

    <!-- Modal para crear campeonato (v-if) -->
    <div class="modal fade" tabindex="-1" :class="{ show: showChampModal }" style="display: block;" v-if="showChampModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-trophy me-2"></i>Nuevo Campeonato</h5>
            <button type="button" class="btn-close" @click="showChampModal = false"></button>
          </div>
          <form @submit.prevent="handleCreateChampionship">
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Nombre *</label>
                <input v-model="champForm.name" class="form-control" required />
              </div>
              <div class="mb-3">
                <label class="form-label">Deporte *</label>
                <input v-model="champForm.sport" class="form-control" required />
              </div>
              <div class="mb-3">
                <label class="form-label">Fecha de inicio *</label>
                <input v-model="champForm.start_date" type="date" class="form-control" required />
              </div>
              <div class="mb-3">
                <label class="form-label">Fecha de fin *</label>
                <input v-model="champForm.end_date" type="date" class="form-control" required />
              </div>
              <div v-if="champError" class="alert alert-danger">{{ champError }}</div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="showChampModal = false">Cancelar</button>
              <button type="submit" class="btn btn-primary" :disabled="champSubmitting">
                <span v-if="champSubmitting" class="spinner-border spinner-border-sm me-2"></span>
                Crear Campeonato
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal para matches del campeonato seleccionado -->
    <div class="modal fade" tabindex="-1" :class="{ show: showMatchesModal }" style="display: block;" v-if="showMatchesModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-list-task me-2"></i>Matches de {{ selectedChamp?.name }}</h5>
            <button type="button" class="btn-close" @click="showMatchesModal = false"></button>
          </div>
          <div class="modal-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="mb-0">Partidos programados</h6>
              <button class="btn btn-outline-primary btn-sm" @click="showAddMatchModal = true">
                <i class="bi bi-plus-circle"></i> Agregar Match
              </button>
            </div>

            <div v-if="matches.length === 0" class="text-center text-muted py-3">
              <i class="bi bi-inbox fs-1 d-block mb-2"></i>
              No hay partidos registrados para este campeonato.
            </div>

            <div v-else>
              <table class="table table-sm align-middle">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Campo</th>
                    <th>Equipo 1</th>
                    <th>Equipo 2</th>
                    <th>Estado Reserva</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, idx) in matches" :key="item.match.id">
                    <td>{{ idx + 1 }}</td>
                    <td>
                      <span v-if="item.reservation && item.reservation.start_datetime">
                        {{ formatDate(item.reservation.start_datetime) }}
                      </span>
                      <span v-else class="text-muted">-</span>
                    </td>
                    <td>
                      <span v-if="item.reservation && item.reservation.field_id">
                        {{ getFieldName(item.reservation.field_id) }}
                      </span>
                      <span v-else class="text-muted">-</span>
                    </td>
                    <td>
                      <span v-if="item.team1 && item.team1.name">{{ item.team1.name }}</span>
                      <span v-else class="text-muted">Equipo #{{ item.match.team1_id }}</span>
                    </td>
                    <td>
                      <span v-if="item.team2 && item.team2.name">{{ item.team2.name }}</span>
                      <span v-else class="text-muted">Equipo #{{ item.match.team2_id }}</span>
                    </td>
                    <td>
                      <span v-if="item.reservation && item.reservation.status">
                        <span :class="statusBadge(item.reservation.status)">
                          {{ item.reservation.status }}
                        </span>
                      </span>
                      <span v-else class="text-muted">-</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showMatchesModal = false">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal para agregar match -->
    <div class="modal fade" tabindex="-1" :class="{ show: showAddMatchModal }" style="display: block;" v-if="showAddMatchModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Agregar Match</h5>
            <button type="button" class="btn-close" @click="showAddMatchModal = false"></button>
          </div>
          <form @submit.prevent="handleAddMatch">
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Campo *</label>
                <select v-model="matchForm.field_id" class="form-select" required>
                  <option value="">Selecciona campo</option>
                  <option v-for="f in fields" :key="f.id" :value="f.id">{{ f.name }}</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Equipo 1 *</label>
                <select v-model="matchForm.team1_id" class="form-select" required>
                  <option value="">Selecciona equipo</option>
                  <option v-for="team in champTeams" :key="team.id" :value="team.id">
                    {{ team.name }}
                  </option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Equipo 2 *</label>
                <select v-model="matchForm.team2_id" class="form-select" required>
                  <option value="">Selecciona equipo</option>
                  <option v-for="team in champTeams" :key="team.id" :value="team.id">
                    {{ team.name }}
                  </option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Fecha y hora *</label>
                <input v-model="matchForm.start_datetime" type="datetime-local" class="form-control" required />
              </div>
              <div class="mb-3">
                <label class="form-label">Duración (horas)</label>
                <input v-model="matchForm.duration" type="number" class="form-control" min="1" max="6" required />
              </div>
              <div v-if="matchError" class="alert alert-danger">{{ matchError }}</div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="showAddMatchModal = false">Cancelar</button>
              <button type="submit" class="btn btn-primary" :disabled="matchSubmitting">
                <span v-if="matchSubmitting" class="spinner-border spinner-border-sm me-2"></span>
                Agregar Match
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import { ref, computed, onMounted, reactive } from 'vue';
import {
  getReservations,
  getFields,
  getChampionships,
  createChampionship,
  getChampionshipMatchesEnriched,
  createChampionshipMatch,
  getTeams,
  approveReservation,
  rejectReservation,
  deleteChampionship as apiDeleteChampionship,
  updateChampionship,
} from '@/services/api';
import ReservationModal from '@/components/ReservationModal.vue';
import ReservationList from '@/components/ReservationList.vue';
import ChampionshipPanel from '@/components/ChampionshipPanel.vue';
import AppBreadcrumb from '@/components/AppBreadcrumb.vue';

export default {
  name: 'AdminReservations',
  components: {
    ReservationModal,
    ReservationList,
    ChampionshipPanel,
    AppBreadcrumb
  },
  setup() {
    const reservations = ref([]);
    const loading = ref(false);
    const fields = ref([]);
    const championships = ref([]);
    const loadingChamps = ref(false);

    // Filtros
    const filterStatus = ref('');
    const filterField = ref('');

    // Modales y forms
    const showChampModal = ref(false);
    const showMatchesModal = ref(false);
    const showAddMatchModal = ref(false);
    const selectedChamp = ref(null);

    const champForm = reactive({ name: '', sport: '', start_date: '', end_date: '' });
    const champError = ref('');
    const champSubmitting = ref(false);

    // Matches
    const matches = ref([]);
    const champTeams = ref([]);
    const matchForm = reactive({ team1_id: '', team2_id: '', start_datetime: '', duration: 6 });
    const matchError = ref('');
    const matchSubmitting = ref(false);

    // Utilitarios
    const getFieldName = (id) => {
      const field = fields.value.find(f => f.id === id);
      return field ? `${field.name}` : `Campo #${id}`;
    };
    const formatDate = (dt) => {
      if (!dt) return '';
      const d = new Date(dt);
      return d.toLocaleString('es-ES', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    };
    const formatActivityType = (type) => {
      const map = {
        practice_individual: 'Práctica Individual',
        practice_group: 'Práctica Grupal',
        match_friendly: 'Partido Amistoso',
        match_official: 'Partido Oficial',
        match_championship: 'Campeonato'
      };
      return map[type] || type;
    };
    const activityTypeBadge = (type) => {
      const map = {
        practice_individual: 'bg-info text-white',
        practice_group: 'bg-primary text-white',
        match_friendly: 'bg-success text-white',
        match_official: 'bg-warning text-dark',
        match_championship: 'bg-secondary text-white'
      };
      return 'badge ' + (map[type] || 'bg-secondary');
    };
    const statusBadge = (status) => {
      const map = {
        pending: 'bg-warning text-dark',
        approved: 'bg-success text-white',
        rejected: 'bg-danger text-white',
        cancelled: 'bg-secondary text-white'
      };
      return 'badge ' + (map[status] || 'bg-secondary');
    };

    // Carga de datos
    const loadReservations = async () => {
      loading.value = true;
      try {
        const params = { limit: 100, offset: 0 };
        if (filterStatus.value) params.status = filterStatus.value;
        if (filterField.value) params.field_id = filterField.value;
        const res = await getReservations(params);
        reservations.value = res.data.reservations?.data || res.data.data || [];
      } catch (e) {
        console.error('Error cargando reservas:', e);
      } finally {
        loading.value = false;
      }
    };
    const loadFields = async () => {
      try {
        const res = await getFields();
        fields.value = res.data.fields?.data || res.data.data || [];
      } catch (e) {
        console.error('Error cargando campos:', e);
      }
    };
    const loadChampionships = async () => {
      loadingChamps.value = true;
      try {
        const res = await getChampionships({ limit: 100, offset: 0 });
        championships.value = res.data.championships?.data || res.data.data || [];
      } catch (e) {
        console.error('Error cargando campeonatos:', e);
      } finally {
        loadingChamps.value = false;
      }
    };

    const filteredReservations = computed(() => {
      return reservations.value.filter(r => {
        const statusOk = !filterStatus.value || r.status === filterStatus.value;
        const fieldOk = !filterField.value || r.field_id == filterField.value;
        const notChampionship = r.activity_type !== 'match_championship';
        return statusOk && fieldOk && notChampionship;
      });
    });

    // Abrir modal de reserva
    const openReservationModal = () => {
      const el = document.getElementById('reservationModal');
      if (!el) {
        console.warn('Reservation modal element (#reservationModal) no encontrado en DOM.');
        return;
      }
      import('bootstrap').then(({ Modal }) => {
        Modal.getOrCreateInstance(el).show();
      }).catch(err => {
        console.error('Error al cargar Bootstrap Modal:', err);
      });
    };

    // Abrir modal de campeonato (v-if approach)
    const openChampionshipModal = async () => {
      try {
        const bootstrap = await import('bootstrap');
        const { Modal } = bootstrap;
        const resEl = document.getElementById('reservationModal');
        if (resEl) {
          const resInst = Modal.getInstance(resEl);
          if (resInst) resInst.hide();
        }
      } catch (e) {
        // ignore
      }
      champForm.name = '';
      champForm.sport = '';
      champForm.start_date = '';
      champForm.end_date = '';
      champError.value = '';
      showChampModal.value = true;
    };

    const approveChampionship = async (champ) => {
      if (!confirm('¿Confirmar aprobación del campeonato "' + champ.name + '"?')) return;
      try {
        await updateChampionship(champ.id, { status: 'planning' });
        await loadChampionships();
        alert('✓ Campeonato aprobado (planning)');
      } catch (e) {
        console.error('Error aprobando campeonato', e);
        alert('Error aprobando campeonato: ' + (e.response?.data?.detail || e.message));
      }
    };

    const rejectChampionship = async (champ) => {
      const reason = prompt('Motivo para rechazar el campeonato (opcional):');
      try {
        await updateChampionship(champ.id, { status: 'cancelled' });
        await loadChampionships();
        alert('✓ Campeonato rechazado');
      } catch (e) {
        console.error('Error rechazando campeonato', e);
        alert('Error rechazando campeonato: ' + (e.response?.data?.detail || e.message));
      }
    };

    // Crear campeonato
    const handleCreateChampionship = async () => {
      champError.value = '';
      champSubmitting.value = true;
      try {
        const user = JSON.parse(localStorage.getItem('user') || '{}');
        const payload = {
          name: champForm.name,
          organizer_id: user.id || null,
          sport: champForm.sport,
          start_date: champForm.start_date,
          end_date: champForm.end_date,
          status: 'planning'
        };
        const res = await createChampionship(payload);
        if (res.data && res.data.ok) {
          showChampModal.value = false;
          await loadChampionships();
        } else {
          champError.value = res.data?.message || 'Error al crear campeonato';
        }
      } catch (e) {
        champError.value = e.response?.data?.detail || e.message || 'Error al crear campeonato';
      } finally {
        champSubmitting.value = false;
      }
    };

    // Seleccionar campeonato y cargar matches/teams
    const selectChampionship = async (champ) => {
      selectedChamp.value = champ;
      showMatchesModal.value = true;
      await loadChampionshipMatches(champ.id);
      await loadChampionshipTeams(champ.id);
    };

    const loadChampionshipTeams = async (champId) => {
      try {
        const res = await getTeams({ limit: 200 });
        champTeams.value = res.data.teams?.data || res.data.data || [];
      } catch (e) {
        champTeams.value = [];
      }
    };

    // --- USAR endpoint enriquecido que implementaste en el broker ---
    const loadChampionshipMatches = async (champId) => {
      try {
        const res = await getChampionshipMatchesEnriched(champId);
        // Aceptar varias formas de respuesta:
        const raw = res.data?.matches?.data || res.data?.matches || res.data?.data || [];
        // raw expected: [{ match: {...}, reservation: {...}?, team1: {...}?, team2: {...}? }, ...]
        matches.value = Array.isArray(raw) ? raw : [];
      } catch (e) {
        console.error('Error cargando matches del championship:', e);
        matches.value = [];
      }
    };

    // Crear match: orquestado en el broker (crea reserva + match atómicamente)
    const handleAddMatch = async () => {
      matchError.value = '';
      matchSubmitting.value = true;
      try {
        if (!matchForm.field_id) throw new Error('Selecciona un campo para el match');
        if (!matchForm.team1_id || !matchForm.team2_id) throw new Error('Selecciona ambos equipos');

        const startDt = matchForm.start_datetime;
        const start = new Date(startDt);
        const pad = n => String(n).padStart(2, '0');
        const formatDateTime = (d) => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:00`;

        const payload = {
          field_id: Number(matchForm.field_id),
          team1_id: Number(matchForm.team1_id),
          team2_id: Number(matchForm.team2_id),
          start_datetime: formatDateTime(start),
          duration: matchForm.duration || 1,
          notes: `Match for championship ${selectedChamp.value?.id || ''}`
        };

        const res = await createChampionshipMatch(selectedChamp.value.id, payload);
        if (res.data && res.data.ok) {
          showAddMatchModal.value = false;
          matchForm.team1_id = matchForm.team2_id = matchForm.start_datetime = '';
          matchForm.duration = 6;
          matchForm.field_id = '';
          await loadChampionshipMatches(selectedChamp.value.id);
        } else {
          throw new Error(res.data?.message || 'Error creando match');
        }
      } catch (e) {
        matchError.value = e.response?.data?.detail || e.message || 'Error al crear match';
      } finally {
        matchSubmitting.value = false;
      }
    };

    const deleteChampionship = async (champ) => {
      if (!confirm(`Eliminar el campeonato "${champ.name}" y todos sus partidos/reservas asociados? Esta acción NO se puede deshacer.`)) return;
      try {
        await apiDeleteChampionship(champ.id);
        if (selectedChamp.value && selectedChamp.value.id === champ.id) {
          selectedChamp.value = null;
          showMatchesModal.value = false;
        }
        await loadChampionships();
        alert('✓ Campeonato eliminado correctamente');
      } catch (e) {
        console.error('Error eliminando championship:', e);
        alert('Error eliminando campeonato: ' + (e.response?.data?.detail || e.message || e));
      }
    };

    // Approve / Reject reservations (list)
    const approve = async (id) => {
      if (!confirm('¿Confirmar aprobación de esta reserva?')) return;
      try {
        await approveReservation(id);
        alert('✓ Reserva aprobada correctamente');
        loadReservations();
      } catch (error) {
        alert('Error: ' + (error.response?.data?.detail || error.message || 'No se pudo aprobar'));
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
        if (error.response?.status === 422) {
          alert('El motivo de rechazo debe tener al menos 10 caracteres.');
        } else {
          alert('Error: ' + (error.response?.data?.detail || error.message || 'No se pudo rechazar'));
        }
      }
    };

    const refreshAll = () => {
      loadReservations();
      loadChampionships();
    };

    onMounted(() => {
      loadFields();
      loadReservations();
      loadChampionships();
    });

    return {
      // datos
      reservations,
      loading,
      fields,
      championships,
      loadingChamps,
      // filtros
      filterStatus,
      filterField,
      filteredReservations,
      // utilitarios
      getFieldName,
      formatDate,
      formatActivityType,
      activityTypeBadge,
      statusBadge,
      // acciones y modales
      openReservationModal,
      refreshAll,
      openChampionshipModal,
      approveChampionship,
      rejectChampionship,
      // create championship
      showChampModal,
      champForm,
      champError,
      champSubmitting,
      handleCreateChampionship,
      // matches
      showMatchesModal,
      showAddMatchModal,
      selectedChamp,
      matches,
      champTeams,
      matchForm,
      matchError,
      matchSubmitting,
      selectChampionship,
      handleAddMatch,
      deleteChampionship,
      // approve/reject (list)
      approve,
      reject
    };
  }
};
</script>

<style scoped>
.container-fluid {
  max-width: 1400px;
}
.card {
  margin-bottom: 1.5rem;
}
</style>
