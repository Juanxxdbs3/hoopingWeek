<template>
  <div class="container-fluid py-4">
    <div class="row">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h1 class="h3 mb-0">
            <i class="bi bi-people me-2"></i>
            Gestión de Usuarios
          </h1>
          <button class="btn btn-success" @click="toggleCreate">
            <i class="bi bi-plus-circle me-2"></i>
            {{ showCreate ? 'Cancelar' : 'Nuevo Usuario' }}
          </button>
        </div>

        <!-- Formulario de creación -->
        <div v-if="showCreate" class="card shadow-sm mb-4" style="max-height: 70vh; overflow-y: auto;">
          <div class="card-header bg-white sticky-top">
            <h5 class="mb-0">{{ isEditing ? 'Editar Usuario' : 'Crear Usuario' }}</h5>
          </div>
          <div class="card-body">
            <form @submit.prevent="saveUser">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Documento (ID) *</label>
                  <input 
                    v-model.number="form.id" 
                    type="number" 
                    class="form-control" 
                    placeholder="12345678" 
                    required 
                    :disabled="isEditing"
                  />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Email *</label>
                  <input 
                    v-model="form.email" 
                    type="email" 
                    class="form-control" 
                    placeholder="usuario@ejemplo.com" 
                    required 
                  />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Nombre *</label>
                  <input 
                    v-model="form.first_name" 
                    type="text" 
                    class="form-control" 
                    placeholder="Juan" 
                    required 
                  />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Apellido *</label>
                  <input 
                    v-model="form.last_name" 
                    type="text" 
                    class="form-control" 
                    placeholder="Pérez" 
                    required 
                  />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Teléfono *</label>
                  <input 
                    v-model="form.phone" 
                    type="tel" 
                    class="form-control" 
                    placeholder="3001234567" 
                    required 
                  />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Contraseña {{ isEditing ? '(dejar vacío para no cambiar)' : '*' }}</label>
                  <input 
                    v-model="form.password" 
                    type="password" 
                    class="form-control" 
                    placeholder="••••••••" 
                    :required="!isEditing"
                  />
                </div>

                <div class="col-md-4">
                  <label class="form-label">Rol *</label>
                  <select v-model.number="form.role_id" class="form-select" required>
                    <option :value="1">Atleta</option>
                    <option :value="2">Entrenador</option>
                    <option :value="3">Field Manager</option>
                    <option :value="4">Super Admin</option>
                  </select>
                </div>

                <div class="col-md-4">
                  <label class="form-label">Altura (m) <small>(opcional)</small></label>
                  <input 
                    v-model.number="form.height" 
                    type="number" 
                    step="0.01" 
                    class="form-control" 
                    placeholder="1.75" 
                  />
                </div>

                <div class="col-md-4">
                  <label class="form-label">Fecha de Nacimiento</label>
                  <input 
                    v-model="form.birth_date" 
                    type="date" 
                    class="form-control" 
                  />
                </div>
              </div>

              <!-- ✅ Footer fijo dentro del card -->
              <div class="card-footer bg-white border-top mt-4 sticky-bottom">
                <button type="submit" class="btn btn-primary me-2">
                  <i class="bi bi-check-circle me-2"></i>
                  {{ isEditing ? 'Actualizar' : 'Crear' }}
                </button>
                <button type="button" class="btn btn-secondary" @click="resetForm">
                  Cancelar
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Tabla de usuarios -->
        <div class="card shadow-sm">
          <div class="card-header bg-white">
            <h5 class="mb-0">Usuarios Registrados ({{ users.length }})</h5>
          </div>
          <div class="card-body p-0">
            <div v-if="loading" class="text-center p-5">
              <div class="spinner-border text-primary"></div>
            </div>

            <div v-else-if="users.length === 0" class="text-center p-5 text-muted">
              <i class="bi bi-inbox fs-1 d-block mb-3"></i>
              <p class="mb-0">No hay usuarios registrados</p>
            </div>

            <div v-else class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="u in users" :key="u.id">
                    <td><strong>{{ u.id }}</strong></td>
                    <td>{{ u.first_name }} {{ u.last_name }}</td>
                    <td>{{ u.email }}</td>
                    <td>{{ u.phone }}</td>
                    <td>
                      <span class="badge" :class="roleBadge(u.role_id)">
                        {{ roleLabel(u.role_id) }}
                      </span>
                    </td>
                    <td>
                      <span class="badge" :class="u.state_id === 1 ? 'bg-success' : 'bg-secondary'">
                        {{ u.state_id === 1 ? 'Activo' : 'Inactivo' }}
                      </span>
                    </td>
                    <td class="text-center">
                      <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" @click="loadUser(u.id)" title="Editar">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-danger" @click="deleteUser(u.id)" title="Eliminar">
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
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { getUsers, getUserById, register, updateUser, deleteUser as deleteUserApi } from '@/services/api';

const users = ref([]);
const loading = ref(false);
const showCreate = ref(false);
const isEditing = ref(false);

const form = ref({
  id: null,
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  password: '',
  role_id: 1,
  height: null,
  birth_date: ''
});

const loadUsers = async () => {
  loading.value = true;
  try {
    const res = await getUsers({ limit: 200 });
    users.value = res.data?.users?.data || res.data?.users || [];
  } catch (e) {
    console.error('Error cargando usuarios:', e);
    alert('Error al cargar usuarios: ' + (e.response?.data?.detail || e.message));
  } finally {
    loading.value = false;
  }
};

const saveUser = async () => {
  try {
    if (isEditing.value) {
      const payload = { ...form.value };
      if (!payload.password) delete payload.password;
      
      await updateUser(form.value.id, payload);
      alert('✓ Usuario actualizado');
    } else {
      await register(form.value);
      alert('✓ Usuario creado');
    }
    
    resetForm();
    await loadUsers();
  } catch (e) {
    console.error('Error guardando usuario:', e);
    alert('Error: ' + (e.response?.data?.detail || e.message));
  }
};

const loadUser = async (id) => {
  try {
    const res = await getUserById(id);
    const u = res.data?.user;
    if (u) {
      form.value = {
        id: u.id,
        first_name: u.first_name,
        last_name: u.last_name,
        email: u.email,
        phone: u.phone,
        password: '',
        role_id: u.role_id,
        height: u.height,
        birth_date: u.birth_date
      };
      isEditing.value = true;
      showCreate.value = true;
    }
  } catch (e) {
    alert('Error: ' + (e.response?.data?.detail || e.message));
  }
};

const deleteUser = async (id) => {
  if (!confirm(`¿Eliminar usuario ${id}?`)) return;
  
  try {
    await deleteUserApi(id);
    alert('✓ Usuario eliminado');
    await loadUsers();
  } catch (e) {
    alert('Error: ' + (e.response?.data?.detail || e.message));
  }
};

const toggleCreate = () => {
  showCreate.value = !showCreate.value;
  if (!showCreate.value) resetForm();
};

const resetForm = () => {
  form.value = {
    id: null,
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    password: '',
    role_id: 1,
    height: null,
    birth_date: ''
  };
  isEditing.value = false;
  showCreate.value = false;
};

const roleLabel = (roleId) => {
  const labels = {
    1: 'Atleta',
    2: 'Entrenador',
    3: 'Field Manager',
    4: 'Super Admin'
  };
  return labels[roleId] || 'Desconocido';
};

const roleBadge = (roleId) => {
  const badges = {
    1: 'bg-info',
    2: 'bg-primary',
    3: 'bg-warning text-dark',
    4: 'bg-danger'
  };
  return badges[roleId] || 'bg-secondary';
};

loadUsers();
</script>

<style scoped>
.sticky-top {
  position: sticky;
  top: 0;
  z-index: 10;
  background: white;
}

.sticky-bottom {
  position: sticky;
  bottom: 0;
  z-index: 10;
}

.card-footer {
  padding: 1rem 1.5rem;
}
</style>
