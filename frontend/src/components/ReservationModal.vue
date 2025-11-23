<template>
  <!-- Modal backdrop -->
  <div class="modal fade" id="reservationModal" tabindex="-1" aria-labelledby="reservationModalLabel" aria-hidden="true" ref="modalRef">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="reservationModalLabel">
            <i class="bi bi-plus-circle me-2"></i>
            Nueva Reserva
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        
        <div class="modal-body">
          <form @submit.prevent="handleSubmit">
            <!-- Campo -->
            <div class="mb-3">
              <label class="form-label">Campo *</label>
              <select v-model="form.field_id" class="form-select" required>
                <option value="">Selecciona un campo</option>
                <option v-for="field in fields" :key="field.id" :value="field.id">
                  {{ field.name }} - {{ field.location }}
                </option>
              </select>
            </div>

            <!-- Solicitante (búsqueda) -->
            <UserSearch 
              label="Solicitante *" 
              placeholder="ID o Email del solicitante"
              @user-found="onUserFound"
              @user-cleared="form.applicant_id = null"
            />

            <!-- Tipo de Actividad -->
            <div class="mb-3">
              <label class="form-label">Tipo de Actividad *</label>
              <select v-model="form.activity_type" class="form-select" required @change="updateDurationHint">
                <option value="practice_individual">Práctica Individual</option>
                <option value="practice_group">Práctica Grupal</option>
                <option value="match_friendly">Partido Amistoso</option>
                <option value="match_official">Partido Oficial</option>
                <!-- Nota: El tipo 'match_championship' se gestiona desde el panel de campeonatos -->
              </select>
              <small class="text-muted d-block mt-1">
                <i class="bi bi-info-circle"></i> {{ durationHint }}
              </small>
            </div>

            <!-- Dinámico: Participantes para práctica grupal (checkboxes con scroll) -->
            <div v-if="form.activity_type === 'practice_group'" class="mb-3">
              <label class="form-label">Participantes (solo atletas) *</label>
              <div class="checkbox-list-scroll">
                <div v-for="athlete in athletes" :key="athlete.id" class="form-check">
                  <input class="form-check-input" type="checkbox" :id="'athlete-' + athlete.id"
                    :value="athlete.id" v-model="form.group_participants">
                  <label class="form-check-label" :for="'athlete-' + athlete.id">
                    {{ athlete.first_name }} {{ athlete.last_name }} (ID: {{ athlete.id }})
                  </label>
                </div>
              </div>
              <small class="text-muted">Puedes seleccionar varios atletas.</small>
            </div>

            <!-- Dinámico: Equipos para partido amistoso (club) -->
            <div v-if="form.activity_type === 'match_friendly'" class="row mb-3">
              <div class="col-md-6 mb-2">
                <label class="form-label">Equipo 1 *</label>
                <select v-model="form.team1_id" class="form-select" required @change="fetchTeamMembers(form.team1_id, 1)">
                  <option value="">Selecciona equipo</option>
                  <option v-for="team in teams" :key="team.id" :value="team.id">
                    {{ team.name }} (ID: {{ team.id }})
                  </option>
                </select>
                <div v-if="form.team1_id && team1Members.length" class="checkbox-list-scroll mt-2">
                  <div class="form-check" v-for="member in team1Members" :key="member.id">
                    <input class="form-check-input" type="checkbox" :id="'team1-member-' + member.id"
                      :value="member.id" v-model="selectedTeam1Members">
                    <label class="form-check-label" :for="'team1-member-' + member.id">
                      {{ member.first_name }} {{ member.last_name }} (ID: {{ member.id }})
                    </label>
                  </div>
                </div>
              </div>
              <div class="col-md-6 mb-2">
                <label class="form-label">Equipo 2 *</label>
                <select v-model="form.team2_id" class="form-select" required @change="fetchTeamMembers(form.team2_id, 2)">
                  <option value="">Selecciona equipo</option>
                  <option v-for="team in teams" :key="team.id" :value="team.id">
                    {{ team.name }} (ID: {{ team.id }})
                  </option>
                </select>
                <div v-if="form.team2_id && team2Members.length" class="checkbox-list-scroll mt-2">
                  <div class="form-check" v-for="member in team2Members" :key="member.id">
                    <input class="form-check-input" type="checkbox" :id="'team2-member-' + member.id"
                      :value="member.id" v-model="selectedTeam2Members">
                    <label class="form-check-label" :for="'team2-member-' + member.id">
                      {{ member.first_name }} {{ member.last_name }} (ID: {{ member.id }})
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Dinámico: Equipos para partido oficial (selección, sin checkboxes) -->
            <div v-if="form.activity_type === 'match_official'" class="row mb-3">
              <div class="col-md-6 mb-2">
                <label class="form-label">Equipo 1 *</label>
                <select v-model="form.team1_id" class="form-select" required @change="fetchTeamMembers(form.team1_id, 1)">
                  <option value="">Selecciona equipo</option>
                  <option v-for="team in teams" :key="team.id" :value="team.id">
                    {{ team.name }} (ID: {{ team.id }})
                  </option>
                </select>
                <div v-if="form.team1_id && team1Members.length" class="member-list-scroll mt-2">
                  <div class="form-text mb-1">Miembros:</div>
                  <ul class="list-group list-group-flush">
                    <li v-for="member in team1Members" :key="member.id" class="list-group-item py-1 px-2">
                      {{ member.first_name }} {{ member.last_name }} (ID: {{ member.id }})
                    </li>
                  </ul>
                </div>
              </div>
              <div class="col-md-6 mb-2">
                <label class="form-label">Equipo 2 *</label>
                <select v-model="form.team2_id" class="form-select" required @change="fetchTeamMembers(form.team2_id, 2)">
                  <option value="">Selecciona equipo</option>
                  <option v-for="team in teams" :key="team.id" :value="team.id">
                    {{ team.name }} (ID: {{ team.id }})
                  </option>
                </select>
                <div v-if="form.team2_id && team2Members.length" class="member-list-scroll mt-2">
                  <div class="form-text mb-1">Miembros:</div>
                  <ul class="list-group list-group-flush">
                    <li v-for="member in team2Members" :key="member.id" class="list-group-item py-1 px-2">
                      {{ member.first_name }} {{ member.last_name }} (ID: {{ member.id }})
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- Dinámico: Campeonato -->
            <div v-if="form.activity_type === 'match_championship'" class="row mb-3">
              <div class="col-md-6">
                <label class="form-label">Campeonato *</label>
                <select v-model="form.championship_id" class="form-select" required>
                  <option value="">Selecciona campeonato</option>
                  <option v-for="champ in championships" :key="champ.id" :value="champ.id">
                    {{ champ.name }} (ID: {{ champ.id }})
                  </option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Equipo *</label>
                <select v-model="form.championship_team_id" class="form-select" required>
                  <option value="">Selecciona equipo</option>
                  <option v-for="team in teams" :key="team.id" :value="team.id">
                    {{ team.name }} (ID: {{ team.id }})
                  </option>
                </select>
              </div>
            </div>

            <!-- Fechas -->
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Inicio *</label>
                <input type="datetime-local" v-model="form.start_datetime" class="form-control" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Fin *</label>
                <input type="datetime-local" v-model="form.end_datetime" class="form-control" required>
              </div>
            </div>

            <!-- Notas -->
            <div class="mb-3">
              <label class="form-label">Notas <small class="text-muted">(opcional)</small></label>
              <textarea v-model="form.notes" class="form-control" rows="3" 
                placeholder="Información adicional sobre la reserva..."></textarea>
            </div>

            <div v-if="error" class="alert alert-danger mb-0">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>
              {{ error }}
            </div>
          </form>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-circle me-2"></i>
            Cancelar
          </button>
          <button 
            type="button" 
            class="btn btn-primary" 
            @click="handleSubmit" 
            :disabled="submitting || !form.applicant_id"
          >
            <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
            <i v-else class="bi bi-check-circle me-2"></i>
            Crear Reserva
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>

import { ref, reactive, onMounted } from 'vue';
import { getFields, createReservation, getUsers, getTeams, getChampionships, getTeamMembers } from '@/services/api';
import UserSearch from './UserSearch.vue';
import { Modal } from 'bootstrap';

export default {
  name: 'ReservationModal',
  components: { UserSearch },
  emits: ['reservation-saved'],
  setup(props, { emit }) {
    const modalRef = ref(null);
    const modalInstance = ref(null); // ✅ Guardar instancia del modal

    const fields = ref([]);
    const submitting = ref(false);
    const error = ref(null);


    // Nuevos datos para selects dinámicos
    const athletes = ref([]); // Solo atletas
    const teams = ref([]);
    const championships = ref([]);

    // Para selección de miembros de equipo (por equipo)
    const team1Members = ref([]);
    const team2Members = ref([]);
    const selectedTeam1Members = ref([]);
    const selectedTeam2Members = ref([]);


    const form = reactive({
      field_id: '',
      applicant_id: null,
      activity_type: 'practice_individual',
      start_datetime: '',
      end_datetime: '',
      notes: '',
      // Dinámicos:
      group_participants: [], // ids de atletas
      team1_id: '',
      team2_id: '',
      championship_id: '',
      championship_team_id: '',
    });
    // Buscar miembros de equipo por ID
    const fetchTeamMembers = async (teamId, target) => {
      if (!teamId) {
        if (target === 1) team1Members.value = [];
        if (target === 2) team2Members.value = [];
        return;
      }
      try {
        const res = await getTeams(); // fallback por si no hay endpoint directo
        // Si existe endpoint directo:
        // const res = await getTeamMembers(teamId);
        // team1Members.value = res.data.members || [];
        //
        // Por ahora, simula con getTeams y filtra:
        if (target === 1) {
          const resp = await getTeamMembers(teamId);
          team1Members.value = resp.data.members || [];
        }
        if (target === 2) {
          const resp = await getTeamMembers(teamId);
          team2Members.value = resp.data.members || [];
        }
      } catch (e) {
        if (target === 1) team1Members.value = [];
        if (target === 2) team2Members.value = [];
      }
    };

    const durationHint = ref('Duración exacta: 1 hora');
    const durationHints = {
      practice_individual: 'Duración exacta: 1 hora',
      practice_group: 'Entre 1 y 2 horas',
      match_friendly: 'Entre 1 y 6 horas',
      match_championship: 'Sin límite de duración',
    };

    const updateDurationHint = () => {
      durationHint.value = durationHints[form.activity_type] || '';
    };

    // Cargar atletas (solo rol atleta)
    const loadAthletes = async () => {
      try {
        const res = await getUsers({ role_id: 1, limit: 100 });
        athletes.value = (res.data.users?.data || res.data.data || []).filter(u => u.role_id === 1);
      } catch (e) {
        athletes.value = [];
      }
    };
    const loadTeams = async () => {
      try {
        const res = await getTeams({ limit: 100 });
        teams.value = res.data.teams?.data || res.data.data || [];
      } catch (e) {
        teams.value = [];
      }
    };
    const loadChampionships = async () => {
      try {
        const res = await getChampionships();
        championships.value = res.data.championships?.data || res.data.data || [];
      } catch (e) {
        championships.value = [];
      }
    };

    const onUserFound = (user) => {
      form.applicant_id = user.id;
      // Limpiar campos dinámicos
      form.group_participants = [];
      form.team1_id = '';
      form.team2_id = '';
      form.championship_id = '';
      form.championship_team_id = '';
      console.log('✅ Usuario seleccionado:', user.id, user.email);
    };

    const loadFields = async () => {
      try {
        const response = await getFields();
        fields.value = response.data.fields?.data || response.data.data || [];
        console.log('✅ Campos cargados:', fields.value.length);
      } catch (err) {
        console.error('❌ Error cargando campos:', err);
        error.value = 'No se pudieron cargar los campos disponibles';
      }
    };

    const handleSubmit = async () => {
      // Validaciones generales
      if (!form.applicant_id) {
        error.value = 'Debes buscar y seleccionar un solicitante';
        return;
      }
      if (!form.field_id) {
        error.value = 'Debes seleccionar un campo';
        return;
      }
      // Validaciones según tipo
      if (form.activity_type === 'practice_group') {
        if (!form.group_participants.length) {
          error.value = 'Selecciona al menos un atleta para la práctica grupal';
          return;
        }
      }
      // Validación para partidos (friendly, official, championship)
      const isMatch = ["match_friendly", "match_official", "match_championship"].includes(form.activity_type);
      if (isMatch) {
        if (!form.team1_id || !form.team2_id) {
          error.value = 'Selecciona ambos equipos para el partido';
          return;
        }
        if (form.team1_id === form.team2_id) {
          error.value = 'Los equipos deben ser diferentes';
          return;
        }
          // Para partido oficial no se seleccionan jugadores manualmente
          if (form.activity_type !== 'match_official') {
            if (!selectedTeam1Members.value.length || !selectedTeam2Members.value.length) {
              error.value = 'Selecciona los jugadores convocados de ambos equipos';
              return;
            }
          }
      }
      if (form.activity_type === 'match_championship' && !form.championship_id) {
        error.value = 'Selecciona el campeonato';
        return;
      }

      error.value = null;
      submitting.value = true;

      try {
        // Construir payload según tipo
        // --> Mapeo: enviar 'match_championship' cuando el usuario escogió 'match_official'
        // helper: formatea una fecha local (input datetime-local) a 'YYYY-MM-DD HH:MM:SS'
        const formatDateTime = (dtStr) => {
          try {
            const d = new Date(dtStr);
            if (isNaN(d.getTime())) {
              // Fallback: try manual replace
              const s = String(dtStr).replace('T', ' ');
              return s.includes(':') && s.split(':').length === 2 ? s + ':00' : s;
            }
            const pad = (n) => String(n).padStart(2, '0');
            return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:00`;
          } catch (e) {
            return String(dtStr).replace('T', ' ') + ':00';
          }
        };

        const payloadActivityType = form.activity_type; // enviar lo que seleccionó el usuario (incluye match_official)

        const payload = {
          field_id: Number(form.field_id),
          applicant_id: Number(form.applicant_id),
          activity_type: payloadActivityType,
          start_datetime: formatDateTime(form.start_datetime),
          end_datetime: formatDateTime(form.end_datetime),
          notes: form.notes || null,
        };
        if (form.activity_type === 'practice_group') {
          payload.participants = form.group_participants.map(id => ({
            participant_id: Number(id),
            participant_type: 'individual'
          }));
        }
        if (form.activity_type === 'match_friendly' || form.activity_type === 'match_official' || form.activity_type === 'match_championship') {
          payload.team1_id = Number(form.team1_id);
          payload.team2_id = Number(form.team2_id);
          // Para friendly sí enviamos jugadores seleccionados de ambos equipos
          if (form.activity_type === 'match_friendly') {
            payload.participants_team1 = selectedTeam1Members.value.map(id => ({ participant_id: Number(id), participant_type: 'team_member' }));
            payload.participants_team2 = selectedTeam2Members.value.map(id => ({ participant_id: Number(id), participant_type: 'team_member' }));
          }
          // Para official / championship el backend puede esperar distinto; aquí solo enviamos teams y championship_id si aplica
          if (form.activity_type === 'match_championship' && form.championship_id) {
            payload.championship_id = Number(form.championship_id);
          }
        }
    // Limpiar selección de miembros si se cambia el equipo
    const onTeamMemberChange = (team) => {
      // Si se cambia el equipo, limpiar los seleccionados
      if (team === 1 && !form.team1_id) selectedTeam1Members.value = [];
      if (team === 2 && !form.team2_id) selectedTeam2Members.value = [];
    };

        console.log('📤 Enviando reserva:', payload);
        const response = await createReservation(payload);
        if (response.data && response.data.ok === true) {
          closeModal();
          // Reset form y miembros
          Object.assign(form, {
            field_id: '',
            applicant_id: null,
            activity_type: 'practice_individual',
            start_datetime: '',
            end_datetime: '',
            notes: '',
            group_participants: [],
            team1_id: '',
            team2_id: '',
            championship_id: '',
            championship_team_id: '',
          });
          team1Members.value = [];
          team2Members.value = [];
          selectedTeam1Members.value = [];
          selectedTeam2Members.value = [];
          error.value = null;
          setTimeout(() => {
            emit('reservation-saved');
          }, 300);
        } else {
          const msg = response.data?.message || 'Error desconocido al crear reserva';
          error.value = msg;
          console.error('❌ ok=false o ausente:', msg);
        }
      } catch (err) {
        console.error('❌ Error creando reserva:', err);
        const detail = err.response?.data?.detail;
        const message = err.response?.data?.message;
        const errors = err.response?.data?.errors;
        if (detail) {
          error.value = detail;
        } else if (message) {
          error.value = message;
        } else if (errors && errors.length > 0) {
          error.value = errors.join(', ');
        } else {
          error.value = 'Error al guardar reserva';
        }
      } finally {
        submitting.value = false;
      }
    };

    onMounted(() => {
      loadFields();
      loadAthletes();
      loadTeams();
      loadChampionships();
      updateDurationHint();
      if (modalRef.value) {
        modalInstance.value = new Modal(modalRef.value);
      }
    });

    const closeModal = () => {
      // ✅ Limpiar backdrop manualmente
      if (modalInstance.value) {
        modalInstance.value.hide();
      } else if (modalRef.value) {
        const modal = new Modal(modalRef.value);
        modal.hide();
      }
      
      // ✅ FORZAR LIMPIEZA DEL BACKDROP
      setTimeout(() => {
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
          backdrop.remove();
        }
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
      }, 300);
    };


    onMounted(() => {
      loadFields();
      updateDurationHint();
      
      // ✅ Crear instancia del modal al montar el componente
      if (modalRef.value) {
        modalInstance.value = new Modal(modalRef.value);
      }
    });

    return {
      modalRef,
      fields,
      form,
      durationHint,
      error,
      submitting,
      updateDurationHint,
      onUserFound,
      handleSubmit,
      // Nuevos datos para selects dinámicos
      athletes,
      teams,
      championships,
      // Para equipos y miembros
      team1Members,
      team2Members,
      selectedTeam1Members,
      selectedTeam2Members,
      fetchTeamMembers,
    };
  },
};
</script>

<style scoped>
/* Listas con scroll para checkboxes y miembros */
.checkbox-list-scroll {
  max-height: 180px;
  overflow-y: auto;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 0.5rem 1rem;
  background: #fafbfc;
}
.member-list-scroll {
  max-height: 180px;
  overflow-y: auto;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  background: #fafbfc;
}
/* ✅ CONFIAR EN BOOTSTRAP - Solo ajustes de altura y scroll */

.modal-dialog {
  max-height: 90vh;
}

.modal-content {
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  background-color: #fff;
  border-radius: 0.5rem;
  border: none;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.modal-body {
  overflow-y: auto;
  max-height: calc(90vh - 130px); 
}

.modal {
  z-index: 1055;
}

.modal-backdrop {
  z-index: 1050;
}

.form-label {
  font-weight: 500;
  margin-bottom: 0.5rem;
}

.form-control:focus,
.form-select:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
}

textarea.form-control {
  resize: vertical;
}
</style>
