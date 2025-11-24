<template>
  <div class="container-fluid py-4">
    <AppBreadcrumb :crumbs="[
      { label: 'Dashboard', to: '/admin/dashboard', icon: 'bi bi-house' },
      { label: 'Equipos', to: '/admin/teams', icon: 'bi bi-people-fill', active: true }
    ]" />

    <!-- Header y Filtros -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
      <div>
        <h2 class="fw-bold mb-0 text-dark">Gestión de Equipos</h2>
        <p class="text-muted small mb-0">Administra los clubes y selecciones registrados</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <!-- Filtro Frontend (Nombre) -->
        <div class="input-group input-group-sm" style="width: 200px;">
          <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
          <input v-model="filters.name" class="form-control" placeholder="Buscar por nombre..." />
        </div>
        
        <!-- Filtros Backend -->
        <select v-model="filters.sport" class="form-select form-select-sm" style="width: 130px;">
          <option value="">Deporte</option>
          <option value="basketball">Basketball</option>
          <option value="futbol">Fútbol</option>
          <option value="volleyball">Voleibol</option>
        </select>
        
        <input v-model.number="filters.trainer_id" class="form-control form-control-sm" placeholder="ID Entrenador" style="width: 120px;" type="number" />
        
        <button class="btn btn-primary btn-sm shadow-sm" @click="loadTeams">
          <i class="bi bi-filter"></i> Aplicar
        </button>
        <button class="btn btn-success btn-sm shadow-sm ms-2" @click="openCreateModal">
          <i class="bi bi-plus-lg me-1"></i>Nuevo Equipo
        </button>
      </div>
    </div>

    <!-- Lista de Equipos (Componente) -->
    <!-- Pasamos los equipos filtrados por nombre en el frontend -->
    <TeamList 
      :teams="filteredTeams" 
      :loading="loading" 
      @edit="openEditModal"
      @delete="confirmDelete"
    />

    <!-- Modal: Crear / Editar Equipo -->
    <div class="modal fade" tabindex="-1" :class="{ show: showModal }" style="display: block; background: rgba(0,0,0,0.5);" v-if="showModal">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-light">
            <h5 class="modal-title fw-bold text-primary">
              <i class="bi" :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle-fill'"></i>
              {{ isEditing ? 'Editar Equipo' : 'Nuevo Equipo' }}
            </h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          
          <div class="modal-body">
            <form @submit.prevent="saveTeam">
              <!-- Sección Datos del Equipo -->
              <h6 class="fw-bold text-secondary mb-3">Información General</h6>
              <div class="row g-3 mb-4">
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Nombre del Equipo *</label>
                  <input v-model="teamForm.name" class="form-control" required placeholder="Ej: Los Tigres" />
                </div>
                <div class="col-md-3">
                  <!--
                   //*TODO
                  /*
                  Actualmente solo está mostrando los 3 deportes disponibles en la bd, podríamos hacer un endpoint que haga fetch de los deportes disponibes en data-layer y hacer que esta lista se actualice dinámicamente.
                  */
                  -->
                 
                  <label class="form-label small fw-bold">Deporte *</label>
                  <select v-model="teamForm.sport" class="form-select" required>
                    <option value="">Selecciona</option>
                    <option value="basketball">Basketball</option>
                    <option value="futbol">Fútbol</option>
                    <option value="volleyball">Voleibol</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold">Tipo</label>
                  <select v-model="teamForm.type" class="form-select">
                    <option value="club">Club</option>
                    <option value="seleccion">Selección</option>
                    <option value="informal">Informal</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Entrenador (ID)</label>
                  <input v-model.number="teamForm.trainer_id" type="number" class="form-control" placeholder="ID del usuario entrenador" />
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-bold">Localidad</label>
                  <input v-model="teamForm.locality" class="form-control" placeholder="Ej: Cartagena" />
                </div>
              </div>

              <!-- Sección Miembros (Solo visible al editar) -->
              <div v-if="isEditing && teamForm.id" class="bg-light p-3 rounded border">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6 class="fw-bold text-secondary mb-0">Miembros del Equipo</h6>
                  <span class="badge bg-secondary">{{ members.length }} atletas</span>
                </div>

                <!-- Formulario Añadir Miembro -->
                <div class="input-group mb-3">
                  <input v-model.number="newMember.athlete_id" type="number" class="form-control" placeholder="ID del Atleta" />
                  <input v-model="newMember.join_date" type="date" class="form-control" style="max-width: 150px;" />
                  <button type="button" class="btn btn-outline-primary" @click="addMember" :disabled="addingMember || !newMember.athlete_id">
                    <span v-if="addingMember" class="spinner-border spinner-border-sm"></span>
                    <i v-else class="bi bi-person-plus"></i> Añadir
                  </button>
                </div>

                <!-- Lista de Miembros -->
                <div style="max-height: 200px; overflow-y: auto;" class="bg-white border rounded">
                  <div v-if="members.length === 0" class="text-center text-muted py-3 small">
                    No hay miembros registrados.
                  </div>
                  <ul v-else class="list-group list-group-flush">
                    <li v-for="m in members" :key="m.id" class="list-group-item d-flex justify-content-between align-items-center py-2">
                      <div>
                        <i class="bi bi-person-circle text-muted me-2"></i>
                        <span class="fw-bold text-dark">
                          {{ m.user_name || ('Atleta #' + m.athlete_id) }}
                        </span>
                        <small class="text-muted ms-2">
                          (Desde: {{ m.join_date }})
                        </small>
                      </div>
                      <button type="button" class="btn btn-sm btn-link text-danger p-0" @click="removeMember(m.athlete_id)">
                        <i class="bi bi-trash"></i>
                      </button>
                    </li>
                  </ul>
                </div>
              </div>
              
              <div v-else class="alert alert-info d-flex align-items-center mt-3" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i>
                <div>Guarda el equipo primero para poder añadir miembros.</div>
              </div>

              <div v-if="teamError" class="alert alert-danger mt-3 mb-0">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ teamError }}
              </div>

              <div class="modal-footer px-0 pb-0 mt-3 border-0">
                <button type="button" class="btn btn-secondary" @click="closeModal">Cancelar</button>
                <button type="submit" class="btn btn-primary" :disabled="savingTeam">
                  <span v-if="savingTeam" class="spinner-border spinner-border-sm me-2"></span>
                  {{ isEditing ? 'Guardar Cambios' : 'Crear Equipo' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue';
import AppBreadcrumb from '@/components/AppBreadcrumb.vue';
import TeamList from '@/components/TeamList.vue'; // Importar el nuevo componente
import {
  getTeams,
  getTeamById,
  createTeam,
  updateTeam,
  deleteTeam,
  getTeamMembers,
  addTeamMember,
  removeTeamMember
} from '@/services/api';

export default {
  name: 'AdminTeams',
  components: { AppBreadcrumb, TeamList },
  setup() {
    // Estado
    const teams = ref([]);
    const loading = ref(false);
    const filters = reactive({ name: '', sport: '', trainer_id: null });

    // Modal y Formularios
    const showModal = ref(false);
    const isEditing = ref(false);
    const savingTeam = ref(false);
    const teamError = ref('');
    
    const teamForm = reactive({
      id: null,
      name: '',
      sport: '',
      type: 'club',
      trainer_id: null,
      locality: ''
    });

    // Miembros
    const members = ref([]);
    const addingMember = ref(false);
    const newMember = reactive({ 
      athlete_id: null, 
      join_date: new Date().toISOString().split('T')[0] // ✅ Fecha actual por defecto
    });

    // --- Lógica de Filtrado Mixto ---
    const filteredTeams = computed(() => {
      // Filtramos por nombre en el cliente porque el backend no tiene búsqueda 'LIKE' para nombre
      if (!filters.name) return teams.value;
      const term = filters.name.toLowerCase();
      return teams.value.filter(t => t.name.toLowerCase().includes(term));
    });

    // --- Cargar Equipos ---
    const loadTeams = async () => {
      loading.value = true;
      try {
        // Enviamos sport y trainer_id al backend
        const params = { limit: 100 };
        if (filters.sport) params.sport = filters.sport;
        if (filters.trainer_id) params.trainer_id = filters.trainer_id;
        
        const res = await getTeams(params);
        teams.value = res.data?.teams?.data || res.data?.data || [];
      } catch (e) {
        console.error('Error cargando equipos:', e);
      } finally {
        loading.value = false;
      }
    };

    // --- Gestión de Modales ---
    const openCreateModal = () => {
      isEditing.value = false;
      teamForm.id = null;
      teamForm.name = '';
      teamForm.sport = '';
      teamForm.type = 'club';
      teamForm.trainer_id = null;
      teamForm.locality = '';
      members.value = []; // Limpiar miembros
      teamError.value = '';
      showModal.value = true;
    };

    const openEditModal = async (team) => {
      isEditing.value = true;
      teamError.value = '';
      // Llenar formulario base
      teamForm.id = team.id;
      teamForm.name = team.name;
      teamForm.sport = team.sport;
      teamForm.type = team.type;
      teamForm.trainer_id = team.trainer_id;
      teamForm.locality = team.locality;
      
      // Cargar miembros
      await loadTeamMembers(team.id);
      showModal.value = true;
    };

    const closeModal = () => {
      showModal.value = false;
      teamError.value = '';
      // Resetear fecha default para la próxima
      newMember.join_date = new Date().toISOString().split('T')[0];
      newMember.athlete_id = null;
    };

    // --- Guardar Equipo ---
    const saveTeam = async () => {
      savingTeam.value = true;
      teamError.value = '';
      try {
        const payload = { ...teamForm };
        // Limpiar nulos
        if (!payload.trainer_id) delete payload.trainer_id;

        let res;
        if (isEditing.value) {
          res = await updateTeam(teamForm.id, payload);
        } else {
          res = await createTeam(payload);
        }

        if (res.data?.ok) {
          await loadTeams();
          // Si estábamos creando, no cerramos inmediatamente para permitir agregar miembros si se desea, 
          // pero la UX estándar es cerrar.
          closeModal();
        } else {
          throw new Error(res.data?.message || 'Error al guardar');
        }
      } catch (e) {
        teamError.value = e.response?.data?.detail || e.message;
      } finally {
        savingTeam.value = false;
      }
    };

    // --- Borrar Equipo ---
    const confirmDelete = async (team) => {
      if (!confirm(`¿Estás seguro de eliminar el equipo "${team.name}"?`)) return;
      try {
        await deleteTeam(team.id);
        await loadTeams();
      } catch (e) {
        console.error(e);
        // ✅ Manejo del error de FK (Integrity Constraint)
        if (e.response && e.response.status === 500) {
          alert("⛔ No se puede eliminar este equipo porque tiene partidos, reservas o miembros asociados.");
        } else {
          alert("Error al eliminar el equipo: " + (e.response?.data?.detail || e.message));
        }
      }
    };

    // --- Gestión de Miembros ---
    const loadTeamMembers = async (teamId) => {
      members.value = [];
      try {
        const res = await getTeamMembers(teamId);
        // Normalización robusta de datos: soportar varias formas que devuelve el broker
        // Ejemplos aceptados:
        // 1) { ok: true, members: { data: [...] } }
        // 2) { ok: true, members: [...] }
        // 3) { ok: true, data: [...] }
        const rawMembers = res.data?.members?.data || res.data?.members || res.data?.data || [];
        // DEBUG: mostrar en consola la forma real recibida (se puede quitar luego)
        console.debug('loadTeamMembers -> rawMembers:', rawMembers);

        members.value = rawMembers.map(m => ({
          id: m.id, // ID de la membresía
          athlete_id: m.athlete_id || m.user_id, // ID del usuario
          user_name: m.user_name || (m.first_name ? (m.first_name + ' ' + (m.last_name || '')) : ('Atleta #' + (m.athlete_id || m.user_id))),
          join_date: m.join_date
        }));
      } catch (e) {
        console.warn("No se pudieron cargar los miembros", e);
      }
    };

    const addMember = async () => {
      addingMember.value = true;
      teamError.value = '';
      try {
        const payload = {
          athlete_id: newMember.athlete_id,
          join_date: newMember.join_date
        };
        const res = await addTeamMember(teamForm.id, newMember.athlete_id); // API espera solo ID o payload? Revisar API.js
        // Revisando tu api.js: addTeamMember toma (teamId, athleteId) y envía { athlete_id: ... }
        // NO está enviando join_date en api.js actualmente.
        
        if (res.data?.ok) {
          await loadTeamMembers(teamForm.id);
          newMember.athlete_id = null; // Limpiar input
        }
      } catch (e) {
        const msg = e.response?.data?.detail || e.message;
        if (msg.includes("Integrity constraint")) {
           teamError.value = "Este usuario no existe o ya pertenece al equipo.";
        } else {
           teamError.value = "Error al añadir miembro: " + msg;
        }
      } finally {
        addingMember.value = false;
      }
    };

    const removeMember = async (athleteId) => {
      if(!confirm("¿Remover atleta del equipo?")) return;
      try {
        await removeTeamMember(teamForm.id, athleteId);
        await loadTeamMembers(teamForm.id);
      } catch (e) {
        alert("Error removiendo miembro");
      }
    };

    onMounted(() => {
      loadTeams();
    });

    return {
      // Estado
      loading, filteredTeams, filters,
      showModal, isEditing, teamForm, savingTeam, teamError,
      members, newMember, addingMember,
      // Métodos
      loadTeams, openCreateModal, openEditModal, closeModal,
      saveTeam, confirmDelete,
      addMember, removeMember
    };
  }
};
</script>