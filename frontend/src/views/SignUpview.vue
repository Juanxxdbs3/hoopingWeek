<template>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-7">
        <div class="card shadow-sm">
          <div class="card-body">
            <h3 class="card-title mb-2">Crear Cuenta</h3>
            <p class="text-muted mb-4">Ingresa tus datos personales</p>

            <form @submit.prevent="handleRegister" novalidate>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Documento (ID)</label>
                  <input v-model="form.id" required class="form-control" />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Teléfono</label>
                  <input v-model="form.phone" required class="form-control" />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Nombre</label>
                  <input v-model="form.first_name" required class="form-control" />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Apellido</label>
                  <input v-model="form.last_name" required class="form-control" />
                </div>

                <div class="col-md-12">
                  <label class="form-label">Correo</label>
                  <input v-model="form.email" type="email" required class="form-control" />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Fecha de nacimiento</label>
                  <input v-model="form.birth_date" type="date" required class="form-control" />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Estatura (m)</label>
                  <input v-model.number="form.height" step="0.01" type="number" required class="form-control" />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Contraseña</label>
                  <input v-model="form.password" type="password" required class="form-control" />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Tipo de cuenta</label>
                  <!-- Solo opciones seguras para registro público -->
                  <select v-model.number="form.role_id" class="form-select" required>
                    <option :value="1">Atleta</option>
                    <option :value="2">Entrenador</option>
                    <option :value="3">Administrador de cancha</option>
                    <!-- NO mostrar SuperAdmin -->
                  </select>
                </div>

                <div class="col-12">
                  <div v-if="error" class="alert alert-danger py-2">{{ error }}</div>
                  <button :disabled="loading" class="btn btn-success">
                    {{ loading ? 'Creando cuenta...' : 'Crear cuenta' }}
                  </button>
                  <button type="button" class="btn btn-link ms-2" @click="goLogin">¿Ya tienes cuenta? Inicia sesión</button>
                </div>
              </div>
            </form>

          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '../composables/useAuth';

const router = useRouter();
const auth = useAuth();

const form = ref({
  id: '',
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  password: '',
  role_id: 1,
  state_id: 1,
  height: null,
  birth_date: ''
});

const error = ref('');
const loading = ref(false);

const handleRegister = async () => {
  error.value = '';
  loading.value = true;

  // Validación mínima front
  if (!form.value.birth_date) {
    error.value = 'La fecha de nacimiento es requerida';
    loading.value = false;
    return;
  }

  try {
    const resp = await auth.register(form.value);
    if (resp.ok) {
      alert('Cuenta creada con éxito. Puedes iniciar sesión ahora.');
      router.push('/login');
    } else {
      // resp.errors puede ser array o resp.error
      if (resp.errors) error.value = Array.isArray(resp.errors) ? resp.errors.join(', ') : resp.errors;
      else error.value = resp.error || 'Error al registrar';
    }
  } catch (e) {
    error.value = e?.response?.data?.detail || e.message || 'Error inesperado';
  } finally {
    loading.value = false;
  }
};

const goLogin = () => router.push('/login');
</script>

<style scoped>
.card { border-radius: 12px; }
</style>
