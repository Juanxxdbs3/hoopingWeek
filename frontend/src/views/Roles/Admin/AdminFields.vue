<template>
  <div class="container-fluid py-4">
    <AppBreadcrumb :crumbs="[
      { label: 'Dashboard', to: '/admin/dashboard', icon: 'bi bi-house' },
      { label: 'Escenarios', to: '/admin/fields', icon: 'bi bi-geo-alt-fill', active: true }
    ]" />

    <!-- Header y Filtros -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="fw-bold mb-0 text-dark">Gestión de Escenarios</h2>
        <p class="text-muted small mb-0">Administra las canchas, estadios y pistas disponibles</p>
      </div>
      <button class="btn btn-success shadow-sm" @click="openCreateModal">
        <i class="bi bi-plus-lg me-2"></i>Nuevo Escenario
      </button>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-body py-2">
        <div class="row g-2">
          <div class="col-md-4">
            <input v-model="filters.location" class="form-control form-control-sm" placeholder="Buscar por ubicación..." @keyup.enter="loadFields">
          </div>
          <div class="col-md-3">
            <select v-model="filters.sport" class="form-select form-select-sm" @change="loadFields">
              <option value="">Todos los deportes</option>
              <option value="basketball">Basketball</option>
              <option value="futbol">Fútbol</option>
              <option value="volleyball">Voleibol</option>
            </select>
          </div>
          <div class="col-md-3">
            <select v-model="filters.state" class="form-select form-select-sm" @change="loadFields">
              <option value="">Todos los estados</option>
              <option value="active">Activo</option>
              <option value="maintenance">Mantenimiento</option>
              <option value="inactive">Inactivo</option>
            </select>
          </div>
          <div class="col-md-2">
            <button class="btn btn-primary btn-sm w-100" @click="loadFields">
              <i class="bi bi-search"></i> Buscar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Lista de Campos -->
    <div class="card shadow-sm border-0">
      <div class="card-body p-0">
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary"></div>
        </div>

        <div v-else-if="fields.length === 0" class="text-center text-muted py-5">
          <i class="bi bi-geo-alt fs-1 d-block mb-2 opacity-50"></i>
          No se encontraron escenarios.
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-3">Nombre</th>
                <th>Ubicación</th>
                <th>Deportes</th>
                <th>Capacidad</th>
                <th>Estado</th>
                <th class="text-end pe-3">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="f in fields" :key="f.id">
                <td class="ps-3 fw-bold text-primary">{{ f.name }}</td>
                <td>{{ f.location }}</td>
                <td>
                  <div class="d-flex gap-1 flex-wrap">
                    <span v-for="s in parseSports(f.allowed_sports)" :key="s" class="badge bg-light text-dark border">
                      {{ s }}
                    </span>
                  </div>
                </td>
                <td>{{ f.people_capacity || '-' }}</td>
                <td>
                  <span class="badge" :class="stateBadge(f.state)">{{ f.state }}</span>
                </td>
                <td class="text-end pe-3">
                  <div class="btn-group">
                    <button class="btn btn-sm btn-outline-primary" @click="openEditModal(f)" title="Editar">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button v-if="f.state === 'active'" class="btn btn-sm btn-outline-warning" @click="changeState(f, 'maintenance')" title="Poner en Mantenimiento">
                      <i class="bi bi-cone-striped"></i>
                    </button>
                    <button v-else class="btn btn-sm btn-outline-success" @click="changeState(f, 'active')" title="Activar">
                      <i class="bi bi-check-lg"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" @click="confirmDelete(f)" title="Eliminar">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Crear/Editar -->
    <div class="modal fade" tabindex="-1" :class="{ show: showModal }" style="display: block; background: rgba(0,0,0,0.5);" v-if="showModal">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-light">
            <h5 class="modal-title fw-bold text-primary">
              <i class="bi" :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle-fill'"></i>
              {{ isEditing ? 'Editar Escenario' : 'Nuevo Escenario' }}
            </h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>

          <div class="modal-body">
            <form @submit.prevent="saveField">
              <div class="row g-3">
                <div class="col-md-8">
                  <label class="form-label small fw-bold">Nombre *</label>
                  <input v-model="form.name" class="form-control" required placeholder="Ej: Cancha Principal" />
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Entidad Propietaria</label>
                  <input v-model="form.owner_entity" class="form-control" placeholder="Ej: IDER" />
                </div>
                <div class="col-12">
                  <label class="form-label small fw-bold">Ubicación *</label>
                  <input v-model="form.location" class="form-control" required placeholder="Ej: Plaza de Toros" />
                </div>

                <div class="col-md-3">
                  <label class="form-label small fw-bold">Ancho (m)</label>
                  <input v-model.number="form.width_meters" type="number" class="form-control" />
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold">Largo (m)</label>
                  <input v-model.number="form.length_meters" type="number" class="form-control" />
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold">Capacidad</label>
                  <input v-model.number="form.people_capacity" type="number" class="form-control" />
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold">Superficie</label>
                  <select v-model="form.surface_type" class="form-select">
                    <option value="concrete">Concreto</option>
                    <option value="wood">Madera</option>
                    <option value="synthetic">Sintética</option>
                    <option value="grass">Césped</option>
                    <option value="runite">Pista (Runite)</option>
                  </select>
                </div>

                <div class="col-12">
                  <label class="form-label small fw-bold d-block">Deportes Permitidos *</label>
                  <div class="d-flex gap-3 flex-wrap bg-light p-2 rounded border">
                    <div class="form-check" v-for="opt in sportOptions" :key="opt">
                      <input class="form-check-input" type="checkbox" :value="opt" v-model="form.allowed_sports">
                      <label class="form-check-label">{{ capitalize(opt) }}</label>
                    </div>
                  </div>
                </div>

                <div class="col-12">
                  <label class="form-label small fw-bold">Notas / Descripción</label>
                  <textarea v-model="form.notes" class="form-control" rows="2"></textarea>
                </div>

                <div class="col-md-6">
                  <label class="form-label small fw-bold">Estado Inicial</label>
                  <select v-model="form.state" class="form-select">
                    <option value="active">Activo</option>
                    <option value="maintenance">Mantenimiento</option>
                    <option value="inactive">Inactivo</option>
                  </select>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                  <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" v-model="form.is_open_to_public">
                    <label class="form-check-label fw-bold">Abierto al público general</label>
                  </div>
                </div>
              </div>

              <div v-if="error" class="alert alert-danger mt-3 mb-0">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ error }}
              </div>

              <div class="modal-footer px-0 pb-0 mt-3 border-0">
                <button type="button" class="btn btn-secondary" @click="closeModal">Cancelar</button>
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                  <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                  Guardar
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
import { ref, reactive, onMounted } from 'vue';
import AppBreadcrumb from '@/components/AppBreadcrumb.vue';
import {
  getFields, createField, updateField, changeFieldState, deleteField
} from '@/services/api';

export default {
  name: 'AdminFields',
  components: { AppBreadcrumb },
  setup() {
    const fields = ref([]);
    const loading = ref(false);
    const filters = reactive({ location: '', sport: '', state: '' });

    const showModal = ref(false);
    const isEditing = ref(false);
    const submitting = ref(false);
    const error = ref('');

    const sportOptions = ['basketball', 'futbol', 'volleyball', 'tennis', 'athletism'];

    const form = reactive({
      id: null,
      name: '',
      location: '',
      width_meters: null,
      length_meters: null,
      surface_type: 'concrete',
      allowed_sports: [],
      people_capacity: null,
      state: 'active',
      is_open_to_public: true,
      owner_entity: '',
      notes: ''
    });

    const loadFields = async () => {
      loading.value = true;
      try {
        const params = { limit: 100 };
        if (filters.location) params.location = filters.location;
        if (filters.sport) params.sport = filters.sport;
        if (filters.state) params.state = filters.state;

        const res = await getFields(params);
        fields.value = res.data?.fields?.data || res.data?.data || [];
      } catch (e) {
        console.error('Error cargando fields:', e);
      } finally {
        loading.value = false;
      }
    };

    const parseSports = (jsonOrArray) => {
      if (!jsonOrArray) return [];
      if (Array.isArray(jsonOrArray)) return jsonOrArray;
      try { return JSON.parse(jsonOrArray); } catch { return []; }
    };

    const stateBadge = (state) => {
      const map = { active: 'bg-success', maintenance: 'bg-warning text-dark', inactive: 'bg-secondary' };
      return map[state] || 'bg-secondary';
    };

    const openCreateModal = () => {
      isEditing.value = false;
      Object.assign(form, {
        id: null, name: '', location: '', width_meters: null, length_meters: null,
        surface_type: 'concrete', allowed_sports: [], people_capacity: null,
        state: 'active', is_open_to_public: true, owner_entity: '', notes: ''
      });
      error.value = '';
      showModal.value = true;
    };

    const openEditModal = (f) => {
      isEditing.value = true;
      Object.assign(form, {
        id: f.id,
        name: f.name,
        location: f.location,
        width_meters: f.width_meters,
        length_meters: f.length_meters,
        surface_type: f.surface_type || 'concrete',
        allowed_sports: parseSports(f.allowed_sports),
        people_capacity: f.people_capacity,
        state: f.state,
        is_open_to_public: Boolean(f.is_open_to_public),
        owner_entity: f.owner_entity,
        notes: f.notes
      });
      error.value = '';
      showModal.value = true;
    };

    const closeModal = () => { showModal.value = false; };

    const saveField = async () => {
      submitting.value = true;
      error.value = '';
      try {
        const payload = { ...form };
        if (!Array.isArray(payload.allowed_sports)) payload.allowed_sports = [];

        if (isEditing.value) {
          await updateField(form.id, payload);
        } else {
          await createField(payload);
        }
        closeModal();
        await loadFields();
      } catch (e) {
        error.value = e.response?.data?.detail || e.message || 'Error al guardar';
      } finally {
        submitting.value = false;
      }
    };

    const changeState = async (field, newState) => {
      try {
        await changeFieldState(field.id, newState); // firma: (id, state)
        await loadFields();
      } catch (e) {
        alert('Error cambiando estado: ' + (e.response?.data?.detail || e.message));
      }
    };

    const confirmDelete = async (field) => {
      if (!confirm(`¿Estás seguro de eliminar "${field.name}"? Esta acción es irreversible si forzamos.`)) return;
      try {
        await deleteField(field.id);
        await loadFields();
      } catch (e) {
        if (e.response?.status === 409) {
          alert('⛔ No se puede eliminar: tiene reservas asociadas. Desactívalo en su lugar.');
        } else {
          alert('Error eliminando campo: ' + (e.response?.data?.detail || e.message));
        }
      }
    };

    const capitalize = (s) => s ? s.charAt(0).toUpperCase() + s.slice(1) : s;

    onMounted(() => {
      loadFields();
    });

    return {
      fields, loading, filters, loadFields,
      showModal, isEditing, submitting, error, form,
      openCreateModal, openEditModal, closeModal, saveField,
      changeState, confirmDelete,
      parseSports, stateBadge, sportOptions, capitalize
    };
  }
};
</script>

<style scoped>
.modal-dialog { max-width: 800px; }
</style>
